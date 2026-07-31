<template>
  <div class="resources-kpi">
    <div class="resources-kpi__header">
      <div class="resources-kpi__icon">
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
      <span class="resources-kpi__title">Resources</span>
    </div>

    <!-- Hero figure -->
    <div class="resources-kpi__hero">
      <span class="resources-kpi__hero-value iz-figure">{{ grandTotal }}</span>
      <span class="resources-kpi__hero-label">
        Items<template v-if="publicTotal"> · {{ publicTotal }} public</template>
      </span>
    </div>

    <!-- Body: donut left, breakdown right — the shape TasksKpiCard and
         TimelineKpiCard use, so all three charts sit on one line. -->
    <div class="resources-kpi__body">
      <div class="resources-kpi__chart-wrap">
        <div v-if="grandTotal > 0" class="resources-kpi__chart">
          <canvas ref="chartCanvas" width="120" height="120"></canvas>
        </div>
        <div v-else class="resources-kpi__chart-empty">
          <span>No resources</span>
        </div>
      </div>

      <div class="resources-kpi__details">
        <div
          v-for="seg in segments"
          :key="seg.key"
          class="resources-kpi__row"
        >
          <span
            class="resources-kpi__row-dot"
            :style="{ backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]) }"
          ></span>
          <span class="resources-kpi__row-label">
            {{ seg.label }}
            <span v-if="seg.sub" class="resources-kpi__row-sub">{{ seg.sub }}</span>
          </span>
          <span class="resources-kpi__row-value">{{ seg.value }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

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
    metricsMap: function () {
      var map = {};
      (this.kpi.metrics || []).forEach(function (m) {
        map[m.label] = m.value;
      });
      return map;
    },
    whiteboards: function () {
      return parseInt(this.metricsMap["Whiteboards"], 10) || 0;
    },
    scrumbanBoards: function () {
      return parseInt(this.metricsMap["Scrumban Boards"], 10) || 0;
    },
    filesParts: function () {
      return this.parsePubPriv(this.metricsMap["Files"] || "0 pub / 0 priv");
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
      return this.parsePubPriv(this.metricsMap["Notes"] || "0 pub / 0 priv");
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
          label: "Whiteboards",
          value: this.whiteboards,
          colorToken: ["--iz-cat-5", "#7c5cbf"],
          sub: "",
        },
        {
          key: "scrumban",
          label: "Scrumban",
          value: this.scrumbanBoards,
          colorToken: ["--iz-cat-3", "#2f9e8f"],
          sub: "",
        },
        {
          key: "files",
          label: "Files",
          value: this.filesTotal,
          colorToken: ["--iz-cat-2", "#3a2350"],
          sub: this.filesPublic + " pub · " + this.filesPrivate + " priv",
        },
        {
          key: "notes",
          label: "Notes",
          value: this.notesTotal,
          colorToken: ["--iz-cat-4", "#d98a2b"],
          sub: this.notesPublic + " pub · " + this.notesPrivate + " priv",
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
      if (!this.$el) return fallback;
      var v = getComputedStyle(this.$el).getPropertyValue(name);
      return (v && v.trim()) || fallback;
    },
    parsePubPriv: function (val) {
      // Expected format: "12 pub / 3 priv"
      var parts = String(val).split("/");
      var pub = parseInt(parts[0], 10) || 0;
      var priv = parts.length > 1 ? parseInt(parts[1], 10) || 0 : 0;
      return { pub: pub, priv: priv };
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
              borderColor: "#ffffff",
              borderWidth: 3,
              hoverOffset: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
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
.resources-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px 24px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.resources-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.resources-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.resources-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.resources-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

/* ── 2×2 Grid ── */
/* ── Hero figure ──
   Same treatment as the Projects card's hero: .iz-figure numeral, 36px, with
   a muted label beside it, so the strip has one figure row. */
.resources-kpi__hero {
  display: flex;
  align-items: baseline;
  gap: 8px;
  /* Same box as the Projects hero, which carries this padding for its
     click target. Without it that card's numeral sits 4px lower than
     the rest and the figure row stops being a row. */
  padding: 4px 8px;
  margin: -4px -8px;
}

.resources-kpi__hero-value {
  font-size: 36px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.resources-kpi__hero-label {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
}

/* ── Body ──
   Copied from .tasks-kpi__body / __left / __details: 120px chart box, 20px
   gap, details column flexing. Keeps this card's chart on the same line as
   the Tasks donut and the Timeline rings. */
.resources-kpi__body {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.resources-kpi__chart-wrap {
  flex-shrink: 0;
}

.resources-kpi__chart {
  position: relative;
  width: 96px;
  height: 96px;
}

.resources-kpi__chart canvas {
  width: 96px !important;
  height: 96px !important;
}

.resources-kpi__chart-empty {
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--color-border, #e5e7eb);
  border-radius: 50%;
  font-size: 12px;
  text-align: center;
  color: var(--color-text-muted, #9ca3af);
}

.resources-kpi__details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.resources-kpi__row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
}

.resources-kpi__row-dot {
  width: 8px;
  height: 8px;
  border-radius: 2px;
  flex-shrink: 0;
  transform: translateY(-1px);
}

.resources-kpi__row-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  min-width: 0;
}

.resources-kpi__row-sub {
  display: block;
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
}

.resources-kpi__row-value {
  margin-left: auto;
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  font-variant-numeric: tabular-nums;
}

</style>
