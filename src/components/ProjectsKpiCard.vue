<template>
  <div class="iz-kpi projects-kpi">
    <div class="iz-kpi__header projects-kpi__header">
      <div class="iz-kpi__icon projects-kpi__icon">
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
      <span class="iz-kpi__title projects-kpi__title">Projects</span>
      <button
        v-if="canCreate"
        type="button"
        class="iz-btn iz-btn--primary iz-btn--sm projects-kpi__new-btn"
        @click.stop="$emit('create-project')"
      >+ New</button>
    </div>

    <!-- Hero number -->
    <div
      class="iz-kpi__hero projects-kpi__hero iz-kpi__hero--clickable projects-kpi__hero--clickable"
      @click="$emit('filter-projects', '')"
    >
      <span class="iz-kpi__hero-value projects-kpi__hero-value iz-figure">{{ total }}</span>
      <span class="iz-kpi__hero-label projects-kpi__hero-label">Total Projects</span>
    </div>

    <!-- Body: donut left, status rows right — the same shape as the Tasks,
         Resources and Timeline cards, so all four charts share a line. Each
         row stays a drill-down, as the chips and the old legend both were. -->
    <div v-if="total > 0" class="iz-kpi__body projects-kpi__body">
      <div class="projects-kpi__chart-wrap">
        <div class="iz-kpi__chart projects-kpi__chart">
          <canvas ref="chartCanvas" width="120" height="120"></canvas>
        </div>
      </div>

      <div class="iz-kpi__details projects-kpi__details">
        <button
          v-for="seg in segments"
          :key="seg.key"
          type="button"
          class="iz-kpi__row projects-kpi__row"
          @click="$emit('filter-projects', seg.statusLabel)"
        >
          <span
            class="iz-kpi__row-dot projects-kpi__row-dot"
            :style="{ backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]) }"
          ></span>
          <span class="iz-kpi__row-label projects-kpi__row-label">{{ seg.label }}</span>
          <span class="iz-kpi__row-value projects-kpi__row-value">{{ seg.value }}</span>
        </button>
      </div>
    </div>
    <div v-else class="iz-kpi__empty projects-kpi__empty">No projects yet</div>

    <!-- Issue count — full-width footer, same geometry as TasksKpiCard's
         oldest-task footer and TimelineKpiCard's schedule rail so the three
         cards' footers share one band. Not a donut slice: a project can be
         Active and have an issue at once, so it sits outside the breakdown. -->
    <div
      v-if="total > 0"
      class="iz-kpi__footer projects-kpi__issues"
      :class="{ 'projects-kpi__issues--clear': withIssue === 0 }"
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
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        <line x1="12" y1="9" x2="12" y2="13" />
        <line x1="12" y1="17" x2="12.01" y2="17" />
      </svg>
      <span class="projects-kpi__issues-text">
        <strong>{{ withIssue }}</strong>
        {{ withIssue === 1 ? "project" : "projects" }} with issues
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

import { themeColor as izThemeColor } from "../lib/izChart";
Chart.register(DoughnutController, ArcElement, Tooltip);

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
  data: function () {
    return {
      chart: null,
    };
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
    withIssue: function () {
      return this.metricsMap["With Issue"] || 0;
    },
    /* Colour is an explicit property per segment, never built from the key —
       a class or colour derived from data can silently resolve to nothing. */
    segments: function () {
      return [
        {
          key: "active",
          label: "Active",
          statusLabel: "Active",
          value: this.active,
          colorToken: ["--iz-success", "#1f7a3e"],
        },
        {
          key: "waiting",
          label: "W.o.c.",
          statusLabel: "Waiting on Customer",
          value: this.waiting,
          colorToken: ["--iz-cat-4", "#d98a2b"],
        },
        {
          key: "on_hold",
          label: "On Hold",
          statusLabel: "On Hold",
          value: this.onHold,
          colorToken: ["--iz-text-muted", "#9a94a2"],
        },
        {
          key: "done",
          label: "Done",
          statusLabel: "Done",
          value: this.done,
          colorToken: ["--iz-cat-5", "#7c5cbf"],
        },
      ];
    },
  },
  mounted: function () {
    this.renderChart();
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
      var self = this;
      this.$nextTick(function () {
        self.renderChart();
      });
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
    renderChart: function () {
      if (!this.$refs.chartCanvas) return;
      var segs = this.segments;
      var self = this;
      this.chart = new Chart(this.$refs.chartCanvas.getContext("2d"), {
        type: "doughnut",
        data: {
          labels: segs.map(function (s) {
            return s.label;
          }),
          datasets: [
            {
              data: segs.map(function (s) {
                return s.value;
              }),
              backgroundColor: segs.map(function (s) {
                return self.themeColor(s.colorToken[0], s.colorToken[1]);
              }),
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











.projects-kpi__chart-wrap {
  flex-shrink: 0;
}





/* Hover only, and on the row's own background rather than a colour token, so
   it clears the moment the pointer leaves. :focus and :active stay inert —
   focus survives a mouse click, and a row still highlighted afterwards reads
   as a selected filter that isn't one. No :focus-visible rule: NC core styles
   button:focus-visible with `outline: ... !important`, which no specificity
   beats, so keyboard focus already has a ring and ours would be dead CSS. */
button.projects-kpi__row:focus,
button.projects-kpi__row:active {
  background: transparent;
  box-shadow: none;
  outline: none;
}





/* Footer band — geometry deliberately identical to .tasks-kpi__oldest and
   .timeline-kpi__rail-wrap: margin-top:auto, 8px/10px padding, 8px radius,
   11px/1.4 type. All three resolve to the same height and sit on one line. */
/* Band geometry from .iz-kpi__footer; only the alert tint is local — this
   footer reports a problem, so it does not use the neutral surface. */
.projects-kpi__issues {
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-warning-text);
}

.projects-kpi__issues svg {
  flex-shrink: 0;
  color: var(--color-danger);
}

/* Zero issues is good news, not a warning — drop the alarm colouring rather
   than showing a red-tinted banner that says nothing is wrong. */
.projects-kpi__issues--clear {
  background: var(--bg-page, #f0f1f5);
  color: var(--color-text-secondary, #6b7280);
}

.projects-kpi__issues--clear svg {
  color: var(--color-text-muted, #9ca3af);
}

.projects-kpi__issues-text strong {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}



</style>
