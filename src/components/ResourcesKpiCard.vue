<template>
  <div class="iz-kpi resources-kpi">
    <div class="iz-kpi__header resources-kpi__header">
      <div class="iz-kpi__icon resources-kpi__icon">
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
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
          <line x1="8" y1="21" x2="16" y2="21" />
          <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
      </div>
      <span class="iz-kpi__title resources-kpi__title">{{ t("Resources") }}</span>
    </div>

    <!-- Hero figure -->
    <div class="iz-kpi__hero resources-kpi__hero">
      <span class="iz-kpi__hero-value resources-kpi__hero-value iz-figure">{{ grandTotal }}</span>
      <span class="iz-kpi__hero-label resources-kpi__hero-label">
        {{ t("Items") }}<template v-if="publicTotal"> · {{ t("{count} public", { count: publicTotal }) }}</template>
      </span>
    </div>

    <!-- Body: donut left, breakdown right — the shape TasksKpiCard and
         TimelineKpiCard use, so all three charts sit on one line. -->
    <div class="iz-kpi__body resources-kpi__body">
      <div class="resources-kpi__chart-wrap">
        <div v-if="grandTotal > 0" class="iz-kpi__chart resources-kpi__chart">
          <canvas ref="chartCanvas" width="120" height="120"></canvas>
        </div>
        <div v-else class="resources-kpi__chart-empty">
          <span>{{ t("No resources") }}</span>
        </div>
      </div>

      <div class="iz-kpi__details resources-kpi__details">
        <div
          v-for="seg in segments"
          :key="seg.key"
          class="iz-kpi__row resources-kpi__row"
        >
          <span
            class="iz-kpi__row-dot resources-kpi__row-dot"
            :style="{ backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]) }"
          ></span>
          <span class="iz-kpi__row-label resources-kpi__row-label">
            {{ seg.label }}
            <span v-if="seg.sub" class="iz-kpi__row-sub resources-kpi__row-sub">{{ seg.sub }}</span>
          </span>
          <span class="iz-kpi__row-value resources-kpi__row-value">{{ seg.value }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

import { themeColor as izThemeColor } from "../lib/izChart";
import { t } from "../lib/l10n.mjs";
Chart.register(DoughnutController, ArcElement, Tooltip);

export default {
  name: "ResourcesKpiCard",
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
        if (m.key) map[m.key] = m;
      });
      return map;
    },
    whiteboards: function () {
      return parseInt((this.metricsMap.whiteboards || {}).value, 10) || 0;
    },
    scrumbanBoards: function () {
      return parseInt((this.metricsMap.scrumban_boards || {}).value, 10) || 0;
    },
    filesParts: function () {
      return this.pubPriv(this.metricsMap.files);
    },
    filesPublic: function () {
      return this.filesParts.pub;
    },
    filesPrivate: function () {
      return this.filesParts.priv;
    },
    filesTotal: function () {
      return this.filesPublic + this.filesPrivate;
    },
    notesParts: function () {
      return this.pubPriv(this.metricsMap.notes);
    },
    notesPublic: function () {
      return this.notesParts.pub;
    },
    notesPrivate: function () {
      return this.notesParts.priv;
    },
    notesTotal: function () {
      return this.notesPublic + this.notesPrivate;
    },
    grandTotal: function () {
      return (
        this.whiteboards + this.scrumbanBoards + this.filesTotal + this.notesTotal
      );
    },
    publicTotal: function () {
      return this.filesPublic + this.notesPublic;
    },
    /* Colour is an explicit property per segment, never built from the key.
       The four categories are unrelated kinds of thing, so they take
       categorical colours rather than a status ramp. */
    segments: function () {
      return [
        {
          key: "whiteboards",
          label: t("Whiteboards"),
          value: this.whiteboards,
          colorToken: ["--iz-cat-5", "#7c5cbf"],
          sub: "",
        },
        {
          key: "scrumban",
          label: t("Scrumban"),
          value: this.scrumbanBoards,
          colorToken: ["--iz-cat-3", "#2f9e8f"],
          sub: "",
        },
        {
          key: "files",
          label: t("Files"),
          value: this.filesTotal,
          colorToken: ["--iz-cat-2", "#3a2350"],
          sub: t("{public} pub · {private} priv", { public: this.filesPublic, private: this.filesPrivate }),
        },
        {
          key: "notes",
          label: t("Notes"),
          value: this.notesTotal,
          colorToken: ["--iz-cat-4", "#d98a2b"],
          sub: t("{public} pub · {private} priv", { public: this.notesPublic, private: this.notesPrivate }),
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
    /* The backend sends the public and private counts as numbers next to
       the formatted value. */
    pubPriv: function (metric) {
      var m = metric || {};
      return { pub: parseInt(m.public, 10) || 0, priv: parseInt(m.private, 10) || 0 };
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








.resources-kpi__chart-wrap {
  flex-shrink: 0;
}



.resources-kpi__chart-empty {
  width: 112px;
  height: 112px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--color-border, #e5e7eb);
  border-radius: 50%;
  font-size: 12px;
  text-align: center;
  color: var(--color-text-muted, #9ca3af);
}


/* Layout from .iz-kpi__row; this card aligns on the baseline because its rows
   carry a second line under the label. */
.resources-kpi__row {
  align-items: baseline;
}





</style>
