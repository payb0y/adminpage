<template>
  <div class="area-chart">
    <canvas ref="chartCanvas" :width="width" :height="height"></canvas>
  </div>
</template>

<script>
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  Filler,
  CategoryScale,
  LinearScale,
  Tooltip,
} from "chart.js";

Chart.register(
  LineController,
  LineElement,
  PointElement,
  Filler,
  CategoryScale,
  LinearScale,
  Tooltip,
);

export default {
  name: "AreaChart",
  props: {
    labels: {
      type: Array,
      required: true,
    },
    data: {
      type: Array,
      required: true,
    },
    width: {
      type: Number,
      default: 400,
    },
    height: {
      type: Number,
      default: 220,
    },
  },
  data() {
    return {
      chart: null,
    };
  },
  mounted() {
    this.renderChart();
  },
  beforeDestroy() {
    if (this.chart) {
      this.chart.destroy();
    }
  },
  watch: {
    data() {
      if (this.chart) {
        this.chart.destroy();
      }
      this.renderChart();
    },
  },
  methods: {
    renderChart() {
      const ctx = this.$refs.chartCanvas.getContext("2d");

      // Gradient fill
      const gradient = ctx.createLinearGradient(0, 0, 0, this.height);
      gradient.addColorStop(0, "rgba(200, 120, 200, 0.35)");
      gradient.addColorStop(1, "rgba(200, 120, 200, 0.02)");

      this.chart = new Chart(ctx, {
        type: "line",
        data: {
          labels: this.labels,
          datasets: [
            {
              data: this.data,
              borderColor: "#c878c8",
              backgroundColor: gradient,
              borderWidth: 2.5,
              pointBackgroundColor: "#c878c8",
              pointBorderColor: "#ffffff",
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
              fill: true,
              tension: 0.35,
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
                label: (context) => ` ${context.parsed.y} completed tasks`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                font: { size: 11 },
                color: "#9ca3af",
              },
            },
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(0,0,0,0.04)",
              },
              ticks: {
                font: { size: 11 },
                color: "#9ca3af",
                stepSize: 2,
              },
            },
          },
          layout: {
            padding: { top: 20 },
          },
        },
        plugins: [
          {
            id: "peakLabel",
            afterDraw: (chart) => {
              const dataset = chart.data.datasets[0];
              const maxVal = Math.max(...dataset.data);
              if (maxVal === 0) {
                return;
              }
              const meta = chart.getDatasetMeta(0);
              const maxIdx = dataset.data.indexOf(maxVal);
              const point = meta.data[maxIdx];

              if (point) {
                const { ctx: c } = chart;
                c.save();
                const text = `${maxVal} completed tasks`;
                c.font = "11px -apple-system, BlinkMacSystemFont, sans-serif";
                const textWidth = c.measureText(text).width;
                const boxPad = 6;
                const boxX = point.x - textWidth / 2 - boxPad;
                const boxY = point.y - 30;

                c.fillStyle = "rgba(26, 26, 46, 0.85)";
                c.beginPath();
                c.roundRect(boxX, boxY, textWidth + boxPad * 2, 18, 4);
                c.fill();

                c.fillStyle = "#ffffff";
                c.textAlign = "center";
                c.textBaseline = "middle";
                c.fillText(text, point.x, boxY + 9);
                c.restore();
              }
            },
          },
        ],
      });
    },
  },
};
</script>

<style scoped>
.area-chart {
  position: relative;
  width: 100%;
  height: 220px;
}

.area-chart canvas {
  width: 100% !important;
  height: 100% !important;
}
</style>
