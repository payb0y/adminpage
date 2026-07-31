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

    <!-- Body: rings left, stats right — the same two-column shape as the
         Tasks card, so both cards' visuals sit on one line across the strip. -->
    <div class="timeline-kpi__body">
      <div class="timeline-kpi__chart-wrap">
        <div class="timeline-kpi__chart">
          <canvas ref="gaugeCanvas" width="120" height="120"></canvas>
          <div class="timeline-kpi__chart-center">
            <span class="timeline-kpi__chart-center-value">{{ completionRate }}%</span>
            <span class="timeline-kpi__chart-center-label">Complete</span>
          </div>
        </div>
      </div>

      <!-- Secondary stats -->
      <div class="timeline-kpi__stats">
      <div class="timeline-kpi__stat">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="14"
          height="14"
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
        <span class="timeline-kpi__stat-label">Coordination Pending</span>
        <span class="timeline-kpi__stat-value">{{ coordinationPending }}</span>
      </div>
      <div class="timeline-kpi__stat">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path
            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
          />
          <polyline points="14 2 14 8 20 8" />
          <line x1="16" y1="13" x2="8" y2="13" />
          <line x1="16" y1="17" x2="8" y2="17" />
        </svg>
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
    gaugeColor: function () {
      var rate = this.completionRate;
      if (rate >= 75) return "#22C55E";
      if (rate >= 50) return "#F59E0B";
      if (rate >= 25) return "#F97316";
      return "#EF4444";
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
          backgroundColor: [this.gaugeColor, track],
          borderWidth: 0,
          hoverOffset: 0,
          weight: 1,
        },
      ];

      if (elapsed !== null) {
        datasets.push({
          data: [elapsed, 100 - elapsed],
          backgroundColor: ["#8b5cf6", track],
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
  gap: 20px;
}

.timeline-kpi__chart-wrap {
  flex-shrink: 0;
}

.timeline-kpi__chart {
  position: relative;
  width: 120px;
  height: 120px;
}

.timeline-kpi__chart canvas {
  width: 120px !important;
  height: 120px !important;
}

.timeline-kpi__chart-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}

.timeline-kpi__chart-center-value {
  display: block;
  font-size: 22px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.timeline-kpi__chart-center-label {
  display: block;
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 2px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
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
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 8px;
  color: var(--color-text-secondary, #6b7280);
}

.timeline-kpi__stat-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  flex: 1;
}

.timeline-kpi__stat-value {
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}
</style>
