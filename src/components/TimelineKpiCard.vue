<template>
  <div class="iz-kpi timeline-kpi">
    <div class="iz-kpi__header timeline-kpi__header">
      <div class="iz-kpi__icon timeline-kpi__icon">
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
          <circle cx="12" cy="12" r="10" />
          <polyline points="12 6 12 12 16 14" />
        </svg>
      </div>
      <span class="iz-kpi__title timeline-kpi__title">Timeline</span>
    </div>

    <!-- Hero figure. The completion rate used to sit in the gauge's centre; it
         moved up here so all four KPI cards state their headline number in the
         same place, which is what lets the strip be scanned across. -->
    <div class="iz-kpi__hero timeline-kpi__hero">
      <span class="iz-kpi__hero-value timeline-kpi__hero-value iz-figure">{{ completionRate }}%</span>
      <span class="iz-kpi__hero-label timeline-kpi__hero-label">
        Complete<template v-if="scheduleElapsed !== null">
          · {{ scheduleElapsed }}% elapsed</template
        >
      </span>
    </div>

    <!-- Body: rings left, stats right — the same two-column shape as the
         Tasks card, so both cards' visuals sit on one line across the strip. -->
    <div class="iz-kpi__body timeline-kpi__body">
      <div class="timeline-kpi__chart-wrap">
        <div class="iz-kpi__chart timeline-kpi__chart">
          <canvas ref="gaugeCanvas" width="120" height="120"></canvas>
        </div>
      </div>

      <!-- Secondary stats: plain rows, matching the Projects and Resources
           breakdown columns. These used to be icon + tinted pill, which needed
           128px in a 116px column and clipped. -->
      <div class="timeline-kpi__stats">
        <div class="timeline-kpi__stat">
          <span class="timeline-kpi__stat-label">Coordination</span>
          <span class="timeline-kpi__stat-value">{{ coordinationPending }}</span>
        </div>
        <div class="timeline-kpi__stat">
          <span class="timeline-kpi__stat-label">Prep Time</span>
          <span class="timeline-kpi__stat-value">{{ prepTime }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

import { themeColor as izThemeColor } from "../lib/izChart";
Chart.register(DoughnutController, ArcElement, Tooltip);

export default {
  name: "TimelineKpiCard",
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
    completionRate: function () {
      var val = this.metricsMap["Avg Completion Rate"] || "0%";
      return parseInt(val, 10) || 0;
    },
    coordinationPending: function () {
      return this.metricsMap["Avg Coordination Pending"] || "0 wks";
    },
    prepTime: function () {
      return this.metricsMap["Avg Required Prep Time"] || "0 wks";
    },
    scheduleElapsed: function () {
      var val = this.metricsMap["Avg Schedule Elapsed"];
      if (val === undefined || val === null) return null;
      return parseInt(val, 10) || 0;
    },
    /* Completion ramp, on the theme's status tokens rather than raw hexes. */
    gaugeColorToken: function () {
      var rate = this.completionRate;
      if (rate >= 75) return ["--iz-success", "#1f7a3e"];
      if (rate >= 50) return ["--iz-cat-4", "#d98a2b"];
      if (rate >= 25) return ["--iz-warning", "#ecc980"];
      return ["--iz-danger", "#c9314a"];
    },
  },
  mounted: function () {
    this.renderGauge();
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
        self.renderGauge();
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
    renderGauge: function () {
      var ctx = this.$refs.gaugeCanvas.getContext("2d");
      var rate = this.completionRate;
      var track = "rgba(0,0,0,0.06)";

      /* One ring: completion. It used to carry a second, inner ring for
         schedule elapsed — and at 0% completion the outer ring is invisible,
         so the elapsed arc read as the card's only progress. Elapsed now
         lives once, as a figure in the hero. */
      var datasets = [
        {
          data: [rate, 100 - rate],
          backgroundColor: [
            this.themeColor(this.gaugeColorToken[0], this.gaugeColorToken[1]),
            track,
          ],
          borderWidth: 0,
          hoverOffset: 4,
        },
      ];

      var self = this;
      this.chart = new Chart(ctx, {
        type: "doughnut",
        /* Labelled so the tooltip reads 'Complete 40%' rather than naming the
           dataset index. */
        data: { labels: ["Complete", "Remaining"], datasets: datasets },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          rotation: -90,
          cutout: "65%",
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: self.themeColor("--iz-text", "#1a1a2e"),
              titleFont: { size: 12, weight: "600" },
              bodyFont: { size: 11 },
              padding: 8,
              cornerRadius: 6,
              callbacks: {
                label: function (ctx) {
                  return " " + ctx.parsed + "%";
                },
              },
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






.timeline-kpi__chart-wrap {
  flex-shrink: 0;
}

/* ── Secondary Stats ── */
.timeline-kpi__stats {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.timeline-kpi__stat {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
}

.timeline-kpi__stat-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  min-width: 0;
}

.timeline-kpi__stat-value {
  margin-left: auto;
  white-space: nowrap;
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  font-variant-numeric: tabular-nums;
}

</style>
