<template>
  <div class="timeline-kpi">
    <div class="timeline-kpi__header">
      <div class="timeline-kpi__icon">
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
      <span class="timeline-kpi__title">Timeline</span>
    </div>

    <!-- Hero figure. The completion rate used to sit in the gauge's centre; it
         moved up here so all four KPI cards state their headline number in the
         same place, which is what lets the strip be scanned across. -->
    <div class="timeline-kpi__hero">
      <span class="timeline-kpi__hero-value iz-figure">{{ completionRate }}%</span>
      <span class="timeline-kpi__hero-label">
        Complete<template v-if="scheduleElapsed !== null">
          · {{ scheduleElapsed }}% elapsed</template
        >
      </span>
    </div>

    <!-- Body: rings left, stats right — the same two-column shape as the
         Tasks card, so both cards' visuals sit on one line across the strip. -->
    <div class="timeline-kpi__body">
      <div class="timeline-kpi__chart-wrap">
        <div class="timeline-kpi__chart">
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

    <!-- Schedule rail — full-width footer. Deliberately built to the same
         geometry as TasksKpiCard's oldest-task footer (padding, radius, font,
         margin-top:auto) so the two cards' footers share one band. -->
    <div v-if="scheduleElapsed !== null" class="timeline-kpi__rail-wrap">
      <span class="timeline-kpi__rail-label">Schedule</span>
      <div class="timeline-kpi__rail">
        <div
          class="timeline-kpi__rail-fill"
          :style="{ width: scheduleElapsed + '%' }"
        ></div>
        <span
          class="timeline-kpi__rail-now"
          :style="{ left: scheduleElapsed + '%' }"
        ></span>
      </div>
      <span class="timeline-kpi__rail-pct">{{ scheduleElapsed }}%</span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

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
    /* null when the org has no scheduled projects at all, which hides the rail
       rather than drawing an empty one that reads as 0% elapsed. */
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
      if (!this.$el) return fallback;
      var v = getComputedStyle(this.$el).getPropertyValue(name);
      return (v && v.trim()) || fallback;
    },
    renderGauge: function () {
      var ctx = this.$refs.gaugeCanvas.getContext("2d");
      var rate = this.completionRate;
      var elapsed = this.scheduleElapsed;
      var track = "rgba(0,0,0,0.06)";

      /* Two concentric rings, outer = work done, inner = schedule spent.
         Chart.js renders each dataset as its own ring, outermost first. Read
         together: outer ahead of inner is ahead of schedule, behind is
         slipping. A full circle rather than the old 180-degree gauge, so the
         card matches the Tasks donut and the strip lines up. */
      var datasets = [
        {
          data: [rate, 100 - rate],
          backgroundColor: [
            this.themeColor(this.gaugeColorToken[0], this.gaugeColorToken[1]),
            track,
          ],
          borderWidth: 0,
          hoverOffset: 0,
          weight: 1,
        },
      ];

      if (elapsed !== null) {
        datasets.push({
          data: [elapsed, 100 - elapsed],
          backgroundColor: [this.themeColor("--iz-cat-5", "#7c5cbf"), track],
          borderWidth: 0,
          hoverOffset: 0,
          weight: 0.6,
        });
      }

      this.chart = new Chart(ctx, {
        type: "doughnut",
        data: { datasets: datasets },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          rotation: -90,
          cutout: "62%",
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false },
          },
          layout: { padding: 0 },
          events: [],
        },
      });
    },
  },
};
</script>

<style scoped>
.timeline-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px 24px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  /* 16px, not the 12px this card used to use — the other three KPI cards all
     gap at 16, and the 4px difference pushed this card's chart off the line. */
  gap: 16px;
}

.timeline-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.timeline-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.timeline-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: rgba(14, 165, 233, 0.1);
  color: #0ea5e9;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.timeline-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

/* ── Body ──
   Mirrors TasksKpiCard's .tasks-kpi__body / __left / __details: same flex
   direction, same 20px gap, same 120px chart box and 22px centre value. The
   two cards' charts therefore share a baseline across the KPI strip, which
   the old 160x90 half-gauge could never do. */
.timeline-kpi__body {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.timeline-kpi__chart-wrap {
  flex-shrink: 0;
}

.timeline-kpi__chart {
  position: relative;
  width: 96px;
  height: 96px;
}

.timeline-kpi__chart canvas {
  width: 96px !important;
  height: 96px !important;
}

/* Hero figure — same treatment across all four KPI cards. */
.timeline-kpi__hero {
  display: flex;
  align-items: baseline;
  gap: 8px;
  /* Same box as the Projects hero, which carries this padding for its
     click target. Without it that card's numeral sits 4px lower than
     the rest and the figure row stops being a row. */
  padding: 4px 8px;
  margin: -4px -8px;
}

.timeline-kpi__hero-value {
  font-size: 36px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.timeline-kpi__hero-label {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
}

/* ── Schedule rail ──
   Geometry copied from .tasks-kpi__oldest so the two footers occupy the same
   band: same 8px/10px padding, same 8px radius, same 11px type, same
   margin-top:auto pinning it to the card's bottom. */
.timeline-kpi__rail-wrap {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 8px;
  /* One line, matching .tasks-kpi__oldest's single 11px/1.4 row, so both
     footers resolve to the same height and sit on one band. */
  font-size: 11px;
  line-height: 1.4;
  color: var(--color-text-secondary, #6b7280);
}

.timeline-kpi__rail-label {
  flex-shrink: 0;
}

.timeline-kpi__rail {
  position: relative;
  flex: 1;
  min-width: 0;
  height: 4px;
  border-radius: 2px;
  background: var(--color-border, #e5e7eb);
  overflow: visible;
}

.timeline-kpi__rail-fill {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  border-radius: 2px;
  background: #8b5cf6;
  transition: width 0.4s ease;
}

.timeline-kpi__rail-now {
  position: absolute;
  top: -3px;
  bottom: -3px;
  width: 2px;
  border-radius: 1px;
  background: var(--color-text-primary, #1a1a2e);
  transform: translateX(-1px);
}

.timeline-kpi__rail-pct {
  flex-shrink: 0;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  font-variant-numeric: tabular-nums;
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
