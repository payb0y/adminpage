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

    <div class="tasks-kpi__body">
      <!-- Donut -->
      <div class="tasks-kpi__chart-wrap">
        <div v-if="hasData" class="tasks-kpi__chart">
          <canvas ref="chartCanvas" width="160" height="160"></canvas>
          <!-- Center label -->
          <div class="tasks-kpi__chart-center">
            <span class="tasks-kpi__chart-center-value">{{ inProgress }}</span>
            <span class="tasks-kpi__chart-center-label">Active</span>
          </div>
        </div>
        <div v-else class="tasks-kpi__chart-empty">
          <span>No tasks</span>
        </div>
      </div>

      <!-- Legend + secondary stat -->
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
              :style="{ backgroundColor: seg.color }"
            ></span>
            <span class="tasks-kpi__legend-label">{{ seg.label }}</span>
            <span class="tasks-kpi__legend-value">{{ seg.value }}</span>
          </div>
        </div>

        <!-- Avg Days Active -->
        <div class="tasks-kpi__secondary">
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
          <span class="tasks-kpi__secondary-text">
            <strong>{{ avgDays }}</strong> avg days active
          </span>
        </div>
      </div>
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
          color: "#EF4444",
          filterType: "due",
          filterValue: "overdue",
        },
        {
          key: "today",
          label: "Today",
          value: this.today,
          color: "#F59E0B",
          filterType: "due",
          filterValue: "today",
        },
        {
          key: "upcoming",
          label: "Upcoming",
          value: this.upcoming,
          color: "#4A90D9",
          filterType: "due",
          filterValue: "nextSevenDays",
        },
        {
          key: "nondue",
          label: "Non Due",
          value: this.nonDue,
          color: "#94A3B8",
          filterType: "due",
          filterValue: "nodue",
        },
      ];
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
    renderChart: function () {
      var ctx = this.$refs.chartCanvas.getContext("2d");
      var colors = this.segments.map(function (s) {
        return s.color;
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
              borderColor: "#ffffff",
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
              backgroundColor: "#1a1a2e",
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
  padding: 20px 24px;
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
  background-color: rgba(230, 126, 90, 0.1);
  color: #e67e5a;
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
.tasks-kpi__body {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.tasks-kpi__chart-wrap {
  flex-shrink: 0;
}

.tasks-kpi__chart {
  position: relative;
  width: 120px;
  height: 120px;
}

.tasks-kpi__chart canvas {
  width: 120px !important;
  height: 120px !important;
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
  width: 120px;
  height: 120px;
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
  background: #f0f4ff;
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
  padding: 8px 10px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 8px;
  color: var(--color-text-secondary, #6b7280);
}

.tasks-kpi__secondary-text {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

.tasks-kpi__secondary-text strong {
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}
</style>
