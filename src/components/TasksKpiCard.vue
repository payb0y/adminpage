<template>
  <div class="iz-kpi tasks-kpi">
    <div class="iz-kpi__header tasks-kpi__header">
      <div class="iz-kpi__icon tasks-kpi__icon">
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
      <span class="iz-kpi__title tasks-kpi__title">{{ t("Tasks") }}</span>
    </div>

    <!-- Hero figure. The active count used to sit in the donut's centre; it
         moved up here so all four KPI cards state their headline number in the
         same place, which is what lets the strip be scanned across. -->
    <div class="iz-kpi__hero tasks-kpi__hero">
      <span class="iz-kpi__hero-value tasks-kpi__hero-value iz-figure">{{ inProgress }}</span>
      <span class="iz-kpi__hero-label tasks-kpi__hero-label">
        {{ t("Active") }}<template v-if="avgDays"> · {{ t("{count} avg days", { count: avgDays }) }}</template>
      </span>
    </div>

    <div class="iz-kpi__body tasks-kpi__body">
      <!-- Left column: Donut -->
      <div class="tasks-kpi__left">
        <div class="tasks-kpi__chart-wrap">
          <div v-if="hasData" class="iz-kpi__chart tasks-kpi__chart">
            <canvas ref="chartCanvas" width="160" height="160"></canvas>
          </div>
          <div v-else class="tasks-kpi__chart-empty">
            <span>{{ t("No tasks") }}</span>
          </div>
        </div>
      </div>

      <!-- Right column: Legend -->
      <div class="iz-kpi__details tasks-kpi__details">
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
      class="iz-kpi__footer tasks-kpi__oldest"
      :title="
        t('{title} — Opened {date}', {
          title: oldestTask.fullTitle || oldestTask.taskTitle,
          date: oldestTask.createdAt,
        })
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
        <template v-for="(part, i) in oldestParts">
          <strong v-if="part.kind === 'task'" :key="i">{{ oldestTask.taskTitle }}</strong>
          <span v-else-if="part.kind === 'age'" :key="i" class="tasks-kpi__oldest-age">{{ formatAge(oldestTask.ageDays) }}</span>
          <span v-else-if="part.kind === 'project'" :key="i" class="tasks-kpi__oldest-project">{{ oldestTask.projectName }}</span>
          <template v-else>{{ part.text }}</template>
        </template>
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

import { themeColor as izThemeColor } from "../lib/izChart";
import { t, n } from "../lib/l10n.mjs";
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
    /* Metrics by their stable backend key; the English label is never read. */
    metricsMap: function () {
      var map = {};
      (this.kpi.metrics || []).forEach(function (m) {
        if (m.key) map[m.key] = m.value;
      });
      return map;
    },
    overdue: function () {
      return parseInt(this.metricsMap.overdue, 10) || 0;
    },
    today: function () {
      return parseInt(this.metricsMap.today, 10) || 0;
    },
    upcoming: function () {
      return parseInt(this.metricsMap.upcoming, 10) || 0;
    },
    inProgress: function () {
      return parseInt(this.metricsMap.in_progress, 10) || 0;
    },
    nonDue: function () {
      return parseInt(this.metricsMap.non_due, 10) || 0;
    },
    avgDays: function () {
      return String(parseInt(this.metricsMap.avg_days_active, 10) || 0);
    },
    /* "Oldest: <strong>{task}</strong> ({age}) in {project}" cut at its
       placeholders, so the template can style each value without v-html. */
    oldestParts: function () {
      var text = t("Oldest: <strong>{task}</strong> ({age}) in {project}").replace(/<\/?strong>/g, "");
      return text.split(/(\{task\}|\{age\}|\{project\})/).filter(Boolean).map(function (piece) {
        var m = /^\{(task|age|project)\}$/.exec(piece);
        return m ? { kind: m[1], text: "" } : { kind: "text", text: piece };
      });
    },
    segments: function () {
      return [
        {
          key: "overdue",
          label: t("Overdue"),
          value: this.overdue,
          colorToken: ["--iz-danger", "#c9314a"],
          filterType: "due",
          filterValue: "overdue",
        },
        {
          key: "today",
          label: t("Today"),
          value: this.today,
          colorToken: ["--iz-cat-4", "#d98a2b"],
          filterType: "due",
          filterValue: "today",
        },
        {
          key: "upcoming",
          label: t("Upcoming"),
          value: this.upcoming,
          colorToken: ["--iz-cat-3", "#2f9e8f"],
          filterType: "due",
          filterValue: "nextSevenDays",
        },
        {
          key: "nondue",
          label: t("Non Due"),
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
      // Delegates to the shared helper vendored from the theme repo
      // (src/lib/izChart.js). Kept as a method because the template calls
      // it bare and because renderChart resolves colours through `self`
      // inside plain-function callbacks, where `this` is not the component.
      return izThemeColor(this.$el, name, fallback);
    },
    formatAge: function (days) {
      if (!days || days < 1) return t("Today");
      if (days < 7) return n("%n day", "%n days", days);
      var weeks = Math.floor(days / 7);
      if (weeks < 5) return n("%n week", "%n weeks", weeks);
      var months = Math.floor(days / 30);
      if (months < 12) return n("%n month", "%n months", months);
      var years = Math.floor(days / 365);
      return n("%n year", "%n years", years);
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
/* Band geometry from .iz-kpi__footer; the alert tint and the drill-down
   affordance are local. */
.tasks-kpi__oldest {
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-warning-text);
  cursor: pointer;
  transition: background 0.15s;
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
