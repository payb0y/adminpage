<template>
  <div class="projects-kpi">
    <div class="projects-kpi__header">
      <div class="projects-kpi__icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path
            d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
          />
        </svg>
      </div>
      <span class="projects-kpi__title">Projects</span>
      <span
        v-if="withIssue > 0"
        class="projects-kpi__badge projects-kpi__badge--danger"
        @click="$emit('filter-projects', 'On Hold')"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="12"
          height="12"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path
            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
          />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        {{ withIssue }} {{ withIssue === 1 ? "issue" : "issues" }}
      </span>
    </div>

    <!-- Hero number -->
    <div
      class="projects-kpi__hero projects-kpi__hero--clickable"
      @click="$emit('filter-projects', '')"
    >
      <span class="projects-kpi__hero-value">{{ total }}</span>
      <span class="projects-kpi__hero-label">Total Projects</span>
    </div>

    <!-- Stacked bar -->
    <div v-if="total > 0" class="projects-kpi__bar-container">
      <div class="projects-kpi__bar">
        <div
          v-for="seg in segments"
          :key="seg.key"
          class="projects-kpi__bar-segment"
          :style="{ width: seg.pct + '%', backgroundColor: seg.color }"
          :title="seg.label + ': ' + seg.value"
          @click="$emit('filter-projects', seg.statusLabel)"
        ></div>
      </div>
      <div class="projects-kpi__legend">
        <div
          v-for="seg in segments"
          :key="seg.key"
          class="projects-kpi__legend-item"
          @click="$emit('filter-projects', seg.statusLabel)"
        >
          <span
            class="projects-kpi__legend-dot"
            :style="{ backgroundColor: seg.color }"
          ></span>
          <span class="projects-kpi__legend-text">
            {{ seg.label }}
            <strong>{{ seg.value }}</strong>
          </span>
        </div>
      </div>
    </div>
    <div v-else class="projects-kpi__empty">No projects yet</div>
  </div>
</template>

<script>
export default {
  name: "ProjectsKpiCard",
  props: {
    kpi: {
      type: Object,
      required: true,
    },
  },
  computed: {
    metricsMap: function () {
      var map = {};
      (this.kpi.metrics || []).forEach(function (m) {
        map[m.label] = parseInt(m.value, 10) || 0;
      });
      return map;
    },
    active: function () {
      return this.metricsMap["Active"] || 0;
    },
    waiting: function () {
      return this.metricsMap["W.o.c."] || 0;
    },
    onHold: function () {
      return this.metricsMap["On Hold"] || 0;
    },
    done: function () {
      return this.metricsMap["Done"] || 0;
    },
    withIssue: function () {
      return this.metricsMap["With Issue"] || 0;
    },
    total: function () {
      return this.active + this.waiting + this.onHold + this.done;
    },
    segments: function () {
      var t = this.total || 1;
      return [
        {
          key: "active",
          label: "Active",
          statusLabel: "Active",
          value: this.active,
          color: "#22C55E",
          pct: (this.active / t) * 100,
        },
        {
          key: "waiting",
          label: "W.o.c.",
          statusLabel: "Waiting on Customer",
          value: this.waiting,
          color: "#F59E0B",
          pct: (this.waiting / t) * 100,
        },
        {
          key: "on_hold",
          label: "On Hold",
          statusLabel: "On Hold",
          value: this.onHold,
          color: "#94A3B8",
          pct: (this.onHold / t) * 100,
        },
        {
          key: "done",
          label: "Done",
          statusLabel: "Done",
          value: this.done,
          color: "#4A90D9",
          pct: (this.done / t) * 100,
        },
      ];
    },
  },
};
</script>

<style scoped>
.projects-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px 24px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.projects-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.projects-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.projects-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: rgba(74, 144, 217, 0.1);
  color: #4a90d9;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.projects-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.projects-kpi__badge {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 20px;
}

.projects-kpi__badge--danger {
  background: var(--color-badge-danger-bg, #fde8e8);
  color: var(--color-badge-danger-text, #b91c1c);
}

.projects-kpi__hero {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.projects-kpi__hero--clickable {
  cursor: pointer;
  border-radius: 8px;
  padding: 4px 8px;
  margin: -4px -8px;
  transition: background 0.15s;
}

.projects-kpi__hero--clickable:hover {
  background: #f0f4ff;
}

.projects-kpi__hero-value {
  font-size: 36px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.projects-kpi__hero-label {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
}

/* ── Stacked Bar ── */
.projects-kpi__bar-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.projects-kpi__bar {
  display: flex;
  height: 10px;
  border-radius: 6px;
  overflow: hidden;
  background: var(--color-border, #e5e7eb);
}

.projects-kpi__bar-segment {
  min-width: 4px;
  transition: width 0.4s ease;
  cursor: pointer;
}

.projects-kpi__bar-segment:hover {
  opacity: 0.8;
}

.projects-kpi__bar-segment:first-child {
  border-radius: 6px 0 0 6px;
}

.projects-kpi__bar-segment:last-child {
  border-radius: 0 6px 6px 0;
}

.projects-kpi__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.projects-kpi__legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  padding: 2px 6px;
  border-radius: 6px;
  transition: background 0.15s;
}

.projects-kpi__legend-item:hover {
  background: #f0f4ff;
}

.projects-kpi__legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.projects-kpi__legend-text {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

.projects-kpi__legend-text strong {
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin-left: 2px;
}

.projects-kpi__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  text-align: center;
  padding: 12px 0;
}
</style>
