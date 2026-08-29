<template>
  <div class="iz-kpi progression-kpi">
    <div class="iz-kpi__header progression-kpi__header">
      <div class="iz-kpi__icon progression-kpi__icon">
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
          <path d="M21 12a9 9 0 1 1-6.219-8.56" />
          <polyline points="21 4 21 10 15 10" />
        </svg>
      </div>
      <span class="iz-kpi__title progression-kpi__title">Progression</span>
    </div>

    <!-- Hero: the portfolio average. Clicking it clears the band filter, the
         same contract ProjectsKpiCard's hero has with its status filter. -->
    <div
      class="iz-kpi__hero progression-kpi__hero iz-kpi__hero--clickable progression-kpi__hero--clickable"
      @click="$emit('filter-progress', '')"
    >
      <span class="iz-kpi__hero-value progression-kpi__hero-value iz-figure"
        >{{ averageProgress }}%</span
      >
      <span class="iz-kpi__hero-label progression-kpi__hero-label">
        Average across {{ total }}
        {{ total === 1 ? "project" : "projects" }}
      </span>
    </div>

    <!-- Body: donut left, band rows right — the shape Projects, Tasks and
         Timeline use, so all four charts sit on one line. Each row is a
         drill-down into the panel's completion filter. -->
    <div v-if="total > 0" class="iz-kpi__body progression-kpi__body">
      <div class="progression-kpi__chart-wrap">
        <div class="iz-kpi__chart progression-kpi__chart">
          <canvas ref="chartCanvas" width="120" height="120"></canvas>
        </div>
      </div>

      <div class="iz-kpi__details progression-kpi__details">
        <button
          v-for="seg in segments"
          :key="seg.key"
          type="button"
          class="iz-kpi__row progression-kpi__row"
          @click="$emit('filter-progress', seg.key)"
        >
          <span
            class="iz-kpi__row-dot progression-kpi__row-dot"
            :style="{
              backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]),
            }"
          ></span>
          <span class="iz-kpi__row-label progression-kpi__row-label">{{
            seg.label
          }}</span>
          <span class="iz-kpi__row-value progression-kpi__row-value">{{
            seg.value
          }}</span>
        </button>
      </div>
    </div>
    <div v-else class="iz-kpi__empty progression-kpi__empty">
      No project progress yet
    </div>

    <!-- Footer band — same geometry as the Projects, Tasks and Timeline
         footers so the four cards' footers share one line. Reports the top
         band, which is the one figure the hero average cannot show. -->
    <div
      v-if="total > 0"
      class="iz-kpi__footer progression-kpi__near-done"
      :class="{ 'progression-kpi__near-done--none': nearlyDone === 0 }"
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
        <polyline points="20 6 9 17 4 12" />
      </svg>
      <span class="progression-kpi__near-done-text">
        <strong>{{ nearlyDone }}</strong>
        {{ nearlyDone === 1 ? "project" : "projects" }} above 75%
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

import { themeColor as izThemeColor } from "../lib/izChart";
Chart.register(DoughnutController, ArcElement, Tooltip);

/* The four bands are the ones the performance panel's completion filter
   already uses (see passesCompletionFilter). Keys match the filter's values
   verbatim so the click needs no translation table. */
var BANDS = [
  { key: "0-25", label: "0–25%", min: 0, max: 25, token: "--iz-seq-1", fallback: "#d9a3d3" },
  { key: "25-50", label: "25–50%", min: 25, max: 50, token: "--iz-seq-2", fallback: "#c876c1" },
  { key: "50-75", label: "50–75%", min: 50, max: 75, token: "--iz-seq-3", fallback: "#b142ab" },
  { key: "75-100", label: "75–100%", min: 75, max: 100, token: "--iz-seq-4", fallback: "#772473" },
];

export default {
  name: "ProgressionKpiCard",
  props: {
    /* projectProgress from the dashboard payload: [{ name, progress }]. The
       card counts and averages it; it never needs the project identity. */
    projects: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  data: function () {
    return {
      chart: null,
    };
  },
  computed: {
    total: function () {
      return (this.projects || []).length;
    },
    averageProgress: function () {
      if (this.total === 0) return 0;
      var sum = 0;
      for (var i = 0; i < this.projects.length; i++) {
        sum += Number(this.projects[i].progress) || 0;
      }
      return Math.round(sum / this.total);
    },
    counts: function () {
      var out = { "0-25": 0, "25-50": 0, "50-75": 0, "75-100": 0 };
      for (var i = 0; i < (this.projects || []).length; i++) {
        out[this.bandOf(Number(this.projects[i].progress) || 0)]++;
      }
      return out;
    },
    nearlyDone: function () {
      return this.counts["75-100"];
    },
    /* Colour is an explicit property per segment, never built from the key —
       a colour derived from data can silently resolve to nothing. */
    segments: function () {
      var counts = this.counts;
      return BANDS.map(function (b) {
        return {
          key: b.key,
          label: b.label,
          value: counts[b.key],
          colorToken: [b.token, b.fallback],
        };
      });
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
    projects: function () {
      if (this.chart) {
        this.chart.destroy();
        this.chart = null;
      }
      var self = this;
      this.$nextTick(function () {
        self.renderChart();
      });
    },
  },
  methods: {
    /* Band boundaries are exclusive at the bottom and inclusive at the top,
       matching passesCompletionFilter exactly. A project at 50% belongs to
       25–50% in both places; drifting here would show a count the filter
       then contradicts. */
    bandOf: function (progress) {
      if (progress <= 25) return "0-25";
      if (progress <= 50) return "25-50";
      if (progress <= 75) return "50-75";
      return "75-100";
    },
    themeColor: function (name, fallback) {
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
.progression-kpi__chart-wrap {
  flex-shrink: 0;
}

/* Hover only, matching .projects-kpi__row: focus survives a mouse click, and
   a row still highlighted afterwards reads as a selected filter that isn't
   one. No :focus-visible rule — NC core's `outline: … !important` on
   button:focus-visible already draws the ring and ours would be dead CSS. */
button.progression-kpi__row:focus,
button.progression-kpi__row:active {
  background: transparent;
  box-shadow: none;
  outline: none;
}

/* Band geometry comes from .iz-kpi__footer; only the tint is local. This
   footer is good news, so it uses the success pair rather than the alert
   colouring .projects-kpi__issues carries. */
.progression-kpi__near-done {
  background: var(--color-badge-success-bg);
  color: var(--color-success-text);
}

.progression-kpi__near-done svg {
  flex-shrink: 0;
  color: var(--color-success);
}

/* Nothing above 75% is not an error — drop the celebration rather than
   showing a green banner reporting zero. */
.progression-kpi__near-done--none {
  background: var(--bg-page, #f0f1f5);
  color: var(--color-text-secondary, #6b7280);
}

.progression-kpi__near-done--none svg {
  color: var(--color-text-muted, #9ca3af);
}

.progression-kpi__near-done-text strong {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
</style>
