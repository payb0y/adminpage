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
      <button
        v-if="canCreate"
        type="button"
        class="projects-kpi__new-btn"
        @click.stop="$emit('create-project')"
      >+ New</button>
    </div>

    <!-- Hero number -->
    <div
      class="projects-kpi__hero projects-kpi__hero--clickable"
      @click="$emit('filter-projects', '')"
    >
      <span class="projects-kpi__hero-value iz-figure">{{ total }}</span>
      <span class="projects-kpi__hero-label">Total Projects</span>
    </div>

    <!-- Status chips: one per project status, each a drill-down -->
    <div v-if="total > 0" class="projects-kpi__chips">
      <button
        v-for="seg in segments"
        :key="seg.key"
        type="button"
        class="projects-kpi__chip"
        @click="$emit('filter-projects', seg.statusLabel)"
      >
        <span class="iz-badge" :class="seg.badgeClass">
          <strong>{{ seg.value }}</strong> {{ seg.label }}
        </span>
      </button>
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
    canCreate: {
      type: Boolean,
      default: false,
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
    total: function () {
      return this.active + this.waiting + this.onHold + this.done;
    },
    segments: function () {
      // Tone is an explicit property, never `'iz-badge--' + key` — a class
      // built from data silently emits one that may not exist. 'On Hold' uses
      // the neutral .iz-badge base, which is also the fallback tone.
      return [
        {
          key: "active",
          label: "Active",
          statusLabel: "Active",
          value: this.active,
          badgeClass: "iz-badge--success",
        },
        {
          key: "waiting",
          label: "W.o.c.",
          statusLabel: "Waiting on Customer",
          value: this.waiting,
          badgeClass: "iz-badge--warning",
        },
        {
          key: "on_hold",
          label: "On Hold",
          statusLabel: "On Hold",
          value: this.onHold,
          badgeClass: "",
        },
        {
          key: "done",
          label: "Done",
          statusLabel: "Done",
          value: this.done,
          badgeClass: "iz-badge--accent",
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
  /* --iz-accent-text is for sitting on the SOLID accent (it is white); the
     on-tint companion is --iz-accent-bg-text, which is what .iz-badge--accent
     uses. Pairing the tint with the wrong one renders the glyph invisible. */
  background-color: var(--iz-accent-bg);
  color: var(--iz-accent-bg-text);
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

/* ── Status chips ──
   Chrome (tint + text colour + geometry) is the theme's .iz-badge primitive;
   only the row layout is local. The button is deliberately chrome-less: the
   badge is the whole visual. Selectors are qualified on `button` because NC
   core styles bare elements at 0,1,1 and outranks a plain class. */
.projects-kpi__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

button.projects-kpi__chip {
  padding: 0;
  margin: 0;
  border: 0;
  background: transparent;
  border-radius: var(--iz-radius-sm);
  cursor: pointer;
  font: inherit;
  -webkit-appearance: none;
  appearance: none;
}

.projects-kpi__chip .iz-badge {
  font-variant-numeric: tabular-nums;
}

.projects-kpi__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  text-align: center;
  padding: 12px 0;
}

.projects-kpi__header {
  position: relative;
}
.projects-kpi__new-btn {
  margin-left: auto;
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  background: #4a90d9;
  border: none;
  padding: 4px 10px;
  border-radius: 8px;
  cursor: pointer;
}
.projects-kpi__new-btn:hover {
  background: #357ec7;
}
</style>
