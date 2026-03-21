<template>
  <div class="bar-chart">
    <div v-if="hasData">
      <canvas ref="chartCanvas" :width="width" :height="height"></canvas>
    </div>
    <div v-else class="bar-chart__empty">
      <span class="bar-chart__empty-text">No tasks yet</span>
    </div>
  </div>
</template>

<script>
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from "chart.js";

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

export default {
  name: "BarChart",
  props: {
    chartData: {
      type: Object,
      required: true,
    },
    width: {
      type: Number,
      default: 400,
    },
    height: {
      type: Number,
      default: 250,
    },
  },
  data() {
    return {
      chart: null,
    };
  },
  computed: {
    hasData() {
      return this.chartData.data && this.chartData.data.some((v) => v > 0);
    },
  },
  mounted() {
    if (this.hasData) {
      this.renderChart();
    }
  },
  beforeDestroy() {
    if (this.chart) {
      this.chart.destroy();
    }
  },
  methods: {
    renderChart() {
      const ctx = this.$refs.chartCanvas.getContext("2d");
      const colors = this.chartData.colors || [
        "#2ec4b6",
        "#f4a261",
        "#e63946",
      ];

      this.chart = new Chart(ctx, {
        type: "bar",
        data: {
          labels: this.chartData.labels,
          datasets: [
            {
              data: this.chartData.data,
              backgroundColor: colors,
              borderColor: colors,
              borderWidth: 0,
              borderRadius: { topLeft: 6, topRight: 6 },
              borderSkipped: "bottom",
              barPercentage: 0.5,
              categoryPercentage: 0.6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: "#1a1a2e",
              titleFont: { size: 13, weight: "600" },
              bodyFont: { size: 12 },
              padding: 10,
              cornerRadius: 8,
              callbacks: {
                label: (context) => ` ${context.parsed.y} tasks`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                font: { size: 12, weight: "500" },
                color: "#6b7280",
              },
              border: { display: false },
            },
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(0,0,0,0.04)",
                drawBorder: false,
              },
              ticks: {
                font: { size: 11 },
                color: "#9ca3af",
                precision: 0,
              },
              border: { display: false },
            },
          },
          layout: {
            padding: { top: 24, left: 4, right: 4, bottom: 4 },
          },
        },
        plugins: [
          {
            id: "barValueLabels",
            afterDraw: (chart) => {
              const meta = chart.getDatasetMeta(0);
              const { ctx: c } = chart;
              c.save();
              c.font =
                "600 12px -apple-system, BlinkMacSystemFont, sans-serif";
              c.textAlign = "center";
              c.textBaseline = "bottom";
              meta.data.forEach((bar, i) => {
                const value = chart.data.datasets[0].data[i];
                if (value > 0) {
                  c.fillStyle = "#374151";
                  c.fillText(value, bar.x, bar.y - 6);
                }
              });
              c.restore();
            },
          },
        ],
      });
    },
  },
};
</script>

<style scoped>
.bar-chart {
  position: relative;
  width: 100%;
  height: 250px;
}

.bar-chart canvas {
  width: 100% !important;
  height: 100% !important;
}

.bar-chart__empty {
  width: 100%;
  height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--color-border, #e5e7eb);
  border-radius: 12px;
}

.bar-chart__empty-text {
  font-size: 14px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}
</style>
