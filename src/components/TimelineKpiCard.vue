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

    <!-- Gauge -->
    <div class="timeline-kpi__gauge-wrap">
      <div class="timeline-kpi__gauge">
        <canvas ref="gaugeCanvas" width="180" height="110"></canvas>
        <div class="timeline-kpi__gauge-center">
          <span class="timeline-kpi__gauge-value">{{ completionRate }}%</span>
          <span class="timeline-kpi__gauge-label">Completion</span>
        </div>
      </div>
    </div>

    <!-- Secondary stats -->
    <div class="timeline-kpi__stats">
      <div class="timeline-kpi__stat">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
        </svg>
        <span class="timeline-kpi__stat-label">Coordination Pending</span>
        <span class="timeline-kpi__stat-value">{{ coordinationPending }}</span>
      </div>
      <div class="timeline-kpi__stat">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" />
        </svg>
        <span class="timeline-kpi__stat-label">Prep Time</span>
        <span class="timeline-kpi__stat-value">{{ prepTime }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import {
  Chart,
  DoughnutController,
  ArcElement,
  Tooltip,
} from "chart.js";

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
      var color = this.gaugeColor;

      this.chart = new Chart(ctx, {
        type: "doughnut",
        data: {
          datasets: [{
            data: [rate, 100 - rate],
            backgroundColor: [color, "rgba(0,0,0,0.06)"],
            borderWidth: 0,
            hoverOffset: 0,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          rotation: -90,
          circumference: 180,
          cutout: "75%",
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
  gap: 12px;
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
  color: #0EA5E9;
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

/* ── Gauge ── */
.timeline-kpi__gauge-wrap {
  display: flex;
  justify-content: center;
}

.timeline-kpi__gauge {
  position: relative;
  width: 160px;
  height: 90px;
  overflow: hidden;
}

.timeline-kpi__gauge canvas {
  width: 160px !important;
  height: 100px !important;
  margin-top: -6px;
}

.timeline-kpi__gauge-center {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
}

.timeline-kpi__gauge-value {
  display: block;
  font-size: 24px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.timeline-kpi__gauge-label {
  display: block;
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 1px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* ── Secondary Stats ── */
.timeline-kpi__stats {
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
