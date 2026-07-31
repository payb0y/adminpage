<template>
  <div class="tasks-kpi">
    <div class="tasks-kpi__header">
      <div class="tasks-kpi__icon">
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
          <path d="M9 11l3 3L22 4" />
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
        </svg>
      </div>
      <span class="tasks-kpi__title">Tasks</span>
    </div>

    <!-- Hero figure. The active count used to sit in the donut's centre; it
         moved up here so all four KPI cards state their headline number in the
         same place, which is what lets the strip be scanned across. -->
    <div class="tasks-kpi__hero">
      <span class="tasks-kpi__hero-value iz-figure">{{ inProgress }}</span>
      <span class="tasks-kpi__hero-label">
        Active<template v-if="avgDays"> · {{ avgDays }} avg days</template>
      </span>
    </div>

    <div class="tasks-kpi__body">
      <!-- Left column: Donut -->
      <div class="tasks-kpi__left">
        <div class="tasks-kpi__chart-wrap">
          <div v-if="hasData" class="tasks-kpi__chart">
            <canvas ref="chartCanvas" width="160" height="160"></canvas>
          </div>
          <div v-else class="tasks-kpi__chart-empty">
            <span>No tasks</span>
          </div>
        </div>
      </div>

      <!-- Right column: Legend -->
      <div class="tasks-kpi__details">
        <div class="tasks-kpi__legend">
          <div
            v-for="seg in segments"
            :key="seg.key"
            class="tasks-kpi__legend-item"
            @click="$emit('filter-tasks', seg.filterType, seg.filterValue)"
          >
            <span
              class="tasks-kpi__legend-dot"
              :style="{ backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]) }"
            ></span>
            <span class="tasks-kpi__legend-label">{{ seg.label }}</span>
            <span class="tasks-kpi__legend-value">{{ seg.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Oldest task — full-width footer -->
    <div
      v-if="oldestTask"
      class="tasks-kpi__oldest"
      :title="
        (oldestTask.fullTitle || oldestTask.taskTitle) +
        ' — Opened ' +
        oldestTask.createdAt
      "
      @click.stop="$emit('goto-oldest-task', oldestTask)"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="13"
        height="13"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      <span class="tasks-kpi__oldest-text">
        Oldest: <strong>{{ oldestTask.taskTitle }}</strong>
        <span class="tasks-kpi__oldest-age"
          >({{ formatAge(oldestTask.ageDays) }})</span
        >
        <span class="tasks-kpi__oldest-project"
          >in {{ oldestTask.projectName }}</span
        >
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

Chart.register(DoughnutController, ArcElement, Tooltip);

export default {
  name: "TasksKpiCard",
  props: {
    kpi: {
      type: Object,
      required: true,
    },
  },
  data: function () {
    return {
      chart: null,
    };
  },
  computed: {
    metricsMap: function () {
      var map = {};
      (this.kpi.metrics || []).forEach(function (m) {
        map[m.label] = m.value;
      });
      return map;
    },
    overdue: function () {
      return parseInt(this.metricsMap["Overdue"], 10) || 0;
    },
    today: function () {
      return parseInt(this.metricsMap["Today"], 10) || 0;
    },
    upcoming: function () {
      return parseInt(this.metricsMap["Upcoming"], 10) || 0;
    },
    inProgress: function () {
      return parseInt(this.metricsMap["In Progress"], 10) || 0;
    },
    nonDue: function () {
      return parseInt(this.metricsMap["Non Due"], 10) || 0;
    },
    avgDays: function () {
      var val = this.metricsMap["Avg Days Active"] || "0d";
      return val.replace("d", "");
    },
    segments: function () {
      return [
        {
          key: "overdue",
          label: "Overdue",
          value: this.overdue,
          colorToken: ["--iz-danger", "#c9314a"],
          filterType: "due",
          filterValue: "overdue",
        },
        {
          key: "today",
          label: "Today",
          value: this.today,
          colorToken: ["--iz-cat-4", "#d98a2b"],
          filterType: "due",
          filterValue: "today",
        },
        {
          key: "upcoming",
          label: "Upcoming",
          value: this.upcoming,
          colorToken: ["--iz-cat-3", "#2f9e8f"],
          filterType: "due",
          filterValue: "nextSevenDays",
        },
        {
          key: "nondue",
          label: "Non Due",
          value: this.nonDue,
          colorToken: ["--iz-text-muted", "#9a94a2"],
          filterType: "due",
          filterValue: "nodue",
        },
      ];
    },
    oldestTask: function () {
      var raw = this.kpi.oldestTask || null;
      if (raw && raw.taskTitle) {
        var title = raw.taskTitle;
        return Object.assign({}, raw, {
          taskTitle:
            title.length > 10 ? title.slice(0, 10).trimEnd() + "\u2026" : title,
          fullTitle: title,
        });
      }
      return raw;
    },
    hasData: function () {
      return this.overdue + this.today + this.upcoming + this.nonDue > 0;
    },
  },
  mounted: function () {
    if (this.hasData) {
      this.renderChart();
    }
  },
  beforeDestroy: function () {
    if (this.chart) {
      this.chart.destroy();
    }
  },
  watch: {
    kpi: function () {
      if (this.chart) {
        this.chart.destroy();
      }
      if (this.hasData) {
        var self = this;
        this.$nextTick(function () {
          self.renderChart();
        });
      }
    },
  },
  methods: {
    /* Resolve theme tokens at render time rather than hardcoding hexes: the
       chart then follows the In Zicht palette, and light/dark automatically,
       instead of drifting from it. getComputedStyle resolves the var() chain,
       so --iz-cat-1 comes back as a real colour. */
    themeColor: function (name, fallback) {
      if (!this.$el) return fallback;
      var v = getComputedStyle(this.$el).getPropertyValue(name);
      return (v && v.trim()) || fallback;
    },
    formatAge: function (days) {
      if (!days || days < 1) return "Today";
      if (days === 1) return "1 day";
      if (days < 7) return days + " days";
      var weeks = Math.floor(days / 7);
      if (weeks < 5) return weeks + (weeks === 1 ? " week" : " weeks");
      var months = Math.floor(days / 30);
      if (months < 12) return months + (months === 1 ? " month" : " months");
      var years = Math.floor(days / 365);
      return years + (years === 1 ? " year" : " years");
    },
    renderChart: function () {
      var ctx = this.$refs.chartCanvas.getContext("2d");
      var self = this;
      var colors = this.segments.map(function (s) {
        return self.themeColor(s.colorToken[0], s.colorToken[1]);
      });
      var values = this.segments.map(function (s) {
        return s.value;
      });
      var labels = this.segments.map(function (s) {
        return s.label;
      });

      this.chart = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: labels,
          datasets: [
            {
              data: values,
              backgroundColor: colors,
              borderColor: self.themeColor("--iz-surface", "#ffffff"),
              borderWidth: 3,
              hoverOffset: 4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          cutout: "65%",
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: self.themeColor("--iz-text", "#1a1a2e"),
              titleFont: { size: 12, weight: "600" },
              bodyFont: { size: 11 },
              padding: 8,
              cornerRadius: 6,
            },
          },
          layout: { padding: 4 },
        },
      });
    },
  },
};
</script>

<style scoped>
.tasks-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.tasks-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.tasks-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tasks-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: color-mix(in oklab, var(--chart-4) 12%, transparent);
  color: var(--chart-4);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.tasks-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

/* ── Body layout ── */
/* Hero figure — same treatment across all four KPI cards: .iz-figure numeral
   at 36px with a muted label beside it. */
.tasks-kpi__hero {
  display: flex;
  align-items: baseline;
  gap: 8px;
  /* Same box as the Projects hero, which carries this padding for its
     click target. Without it that card's numeral sits 4px lower than
     the rest and the figure row stops being a row. */
  padding: 4px 8px;
  margin: -4px -8px;
}

.tasks-kpi__hero-value {
  font-size: 36px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.tasks-kpi__hero-label {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
}

.tasks-kpi__body {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.tasks-kpi__left {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.tasks-kpi__chart-wrap {
  flex-shrink: 0;
}

.tasks-kpi__chart {
  position: relative;
  width: 112px;
  height: 112px;
}

.tasks-kpi__chart canvas {
  width: 112px !important;
  height: 112px !important;
}

.tasks-kpi__chart-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}

.tasks-kpi__chart-center-value {
  display: block;
  font-size: 22px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.tasks-kpi__chart-center-label {
  display: block;
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 2px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.tasks-kpi__chart-empty {
  width: 112px;
  height: 112px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--color-border, #e5e7eb);
  border-radius: 50%;
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
}

/* ── Details column ── */
.tasks-kpi__details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

.tasks-kpi__legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tasks-kpi__legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 3px 6px;
  border-radius: 6px;
  transition: background 0.15s;
}

.tasks-kpi__legend-item:hover {
  background: var(--accent-bg);
}

.tasks-kpi__legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.tasks-kpi__legend-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  flex: 1;
}

.tasks-kpi__legend-value {
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

/* ── Secondary stat ── */
.tasks-kpi__secondary {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 8px;
  color: var(--color-text-secondary, #6b7280);
  width: 100%;
}

.tasks-kpi__secondary-text {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

.tasks-kpi__secondary-text strong {
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

/* ── Oldest task (full-width footer) ── */
.tasks-kpi__oldest {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  background: var(--color-badge-danger-bg);
  border-radius: 8px;
  color: var(--color-badge-warning-text);
  cursor: pointer;
  transition: background 0.15s;
  min-width: 0;
  overflow: hidden;
  margin-top: auto;
}

.tasks-kpi__oldest:hover {
  background: var(--color-badge-danger-bg);
}

.tasks-kpi__oldest svg {
  flex-shrink: 0;
  margin-top: 1px;
  color: var(--color-danger);
}

.tasks-kpi__oldest-text {
  font-size: 11px;
  line-height: 1.4;
  color: var(--color-badge-warning-text);
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tasks-kpi__oldest-text strong {
  font-weight: 600;
  color: var(--color-badge-warning-text);
  font-size: 10px;
}

.tasks-kpi__oldest-age {
  font-weight: 500;
  font-size: 10px;
  color: var(--color-danger);
}

.tasks-kpi__oldest-project {
  color: var(--color-badge-warning-text);
  font-style: italic;
  font-size: 10px;
}
</style>
