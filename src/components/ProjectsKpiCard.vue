<template>
  <div class="projects-kpi">
    <div class="projects-kpi__header">
      <div class="projects-kpi__icon">
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
          <path
            d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
          />
        </svg>
      </div>
      <span class="projects-kpi__title">Projects</span>
      <button
        v-if="canCreate"
        type="button"
        class="iz-btn iz-btn--primary iz-btn--sm projects-kpi__new-btn"
        @click.stop="$emit('create-project')"
      >+ New</button>
    </div>

    <!-- Hero number -->
    <div
      class="projects-kpi__hero projects-kpi__hero--clickable"
      @click="$emit('filter-projects', '')"
    >
      <span class="projects-kpi__hero-value iz-figure">{{ total }}</span>
      <span class="projects-kpi__hero-label">Total Projects</span>
    </div>

    <!-- Body: donut left, status rows right — the same shape as the Tasks,
         Resources and Timeline cards, so all four charts share a line. Each
         row stays a drill-down, as the chips and the old legend both were. -->
    <div v-if="total > 0" class="projects-kpi__body">
      <div class="projects-kpi__chart-wrap">
        <div class="projects-kpi__chart">
          <canvas ref="chartCanvas" width="120" height="120"></canvas>
        </div>
      </div>

      <div class="projects-kpi__details">
        <button
          v-for="seg in segments"
          :key="seg.key"
          type="button"
          class="projects-kpi__row"
          @click="$emit('filter-projects', seg.statusLabel)"
        >
          <span
            class="projects-kpi__row-dot"
            :style="{ backgroundColor: themeColor(seg.colorToken[0], seg.colorToken[1]) }"
          ></span>
          <span class="projects-kpi__row-label">{{ seg.label }}</span>
          <span class="projects-kpi__row-value">{{ seg.value }}</span>
        </button>
      </div>
    </div>
    <div v-else class="projects-kpi__empty">No projects yet</div>

    <!-- Issue count — full-width footer, same geometry as TasksKpiCard's
         oldest-task footer and TimelineKpiCard's schedule rail so the three
         cards' footers share one band. Not a donut slice: a project can be
         Active and have an issue at once, so it sits outside the breakdown. -->
    <div
      v-if="total > 0"
      class="projects-kpi__issues"
      :class="{ 'projects-kpi__issues--clear': withIssue === 0 }"
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
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        <line x1="12" y1="9" x2="12" y2="13" />
        <line x1="12" y1="17" x2="12.01" y2="17" />
      </svg>
      <span class="projects-kpi__issues-text">
        <strong>{{ withIssue }}</strong>
        {{ withIssue === 1 ? "project" : "projects" }} with issues
      </span>
    </div>
  </div>
</template>

<script>
import { Chart, DoughnutController, ArcElement, Tooltip } from "chart.js";

Chart.register(DoughnutController, ArcElement, Tooltip);

export default {
  name: "ProjectsKpiCard",
  props: {
    kpi: {
      type: Object,
      required: true,
    },
    canCreate: {
      type: Boolean,
      default: false,
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
        map[m.label] = parseInt(m.value, 10) || 0;
      });
      return map;
    },
    active: function () {
      return this.metricsMap["Active"] || 0;
    },
    waiting: function () {
      return this.metricsMap["W.o.c."] || 0;
    },
    onHold: function () {
      return this.metricsMap["On Hold"] || 0;
    },
    done: function () {
      return this.metricsMap["Done"] || 0;
    },
    total: function () {
      return this.active + this.waiting + this.onHold + this.done;
    },
    withIssue: function () {
      return this.metricsMap["With Issue"] || 0;
    },
    /* Colour is an explicit property per segment, never built from the key —
       a class or colour derived from data can silently resolve to nothing. */
    segments: function () {
      return [
        {
          key: "active",
          label: "Active",
          statusLabel: "Active",
          value: this.active,
          colorToken: ["--iz-success", "#1f7a3e"],
        },
        {
          key: "waiting",
          label: "W.o.c.",
          statusLabel: "Waiting on Customer",
          value: this.waiting,
          colorToken: ["--iz-cat-4", "#d98a2b"],
        },
        {
          key: "on_hold",
          label: "On Hold",
          statusLabel: "On Hold",
          value: this.onHold,
          colorToken: ["--iz-text-muted", "#9a94a2"],
        },
        {
          key: "done",
          label: "Done",
          statusLabel: "Done",
          value: this.done,
          colorToken: ["--iz-cat-5", "#7c5cbf"],
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
.projects-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px 24px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.projects-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.projects-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
  /* Pinned to the icon's height. This is the only KPI card with an action in
     its header, and at narrow widths '+ New' wrapped to two lines, making the
     header 40px against the other three cards' 32 — which pushed this card's
     figure and chart 8px below everyone else's. */
  min-height: 32px;
}

.projects-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  /* --iz-accent-text is for sitting on the SOLID accent (it is white); the
     on-tint companion is --iz-accent-bg-text, which is what .iz-badge--accent
     uses. Pairing the tint with the wrong one renders the glyph invisible. */
  background-color: var(--iz-accent-bg);
  color: var(--iz-accent-bg-text);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.projects-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.projects-kpi__hero {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.projects-kpi__hero--clickable {
  cursor: pointer;
  border-radius: 8px;
  padding: 4px 8px;
  margin: -4px -8px;
  transition: background 0.15s;
}

.projects-kpi__hero--clickable:hover {
  background: #f0f4ff;
}

.projects-kpi__hero-value {
  font-size: 36px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.projects-kpi__hero-label {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
}

/* ── Body ──
   Copied from .tasks-kpi__body / __left / __details: 120px chart box, 20px
   gap, details column flexing, so this card's donut sits on the same line as
   the Tasks, Resources and Timeline charts.

   Rows are drill-down buttons. Selectors are qualified on `button` because NC
   core styles bare elements at 0,1,1 and outranks a plain class. */
.projects-kpi__body {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.projects-kpi__chart-wrap {
  flex-shrink: 0;
}

.projects-kpi__chart {
  position: relative;
  width: 96px;
  height: 96px;
}

.projects-kpi__chart canvas {
  width: 96px !important;
  height: 96px !important;
}

.projects-kpi__details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

button.projects-kpi__row {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 2px 4px;
  margin: 0 -4px;
  border: 0;
  background: transparent;
  border-radius: var(--iz-radius-sm);
  cursor: pointer;
  font: inherit;
  text-align: left;
  -webkit-appearance: none;
  appearance: none;
  min-height: 0;
}

/* Hover only, and on the row's own background rather than a colour token, so
   it clears the moment the pointer leaves. :focus and :active stay inert —
   focus survives a mouse click, and a row still highlighted afterwards reads
   as a selected filter that isn't one. No :focus-visible rule: NC core styles
   button:focus-visible with `outline: ... !important`, which no specificity
   beats, so keyboard focus already has a ring and ours would be dead CSS. */
button.projects-kpi__row:focus,
button.projects-kpi__row:active {
  background: transparent;
  box-shadow: none;
  outline: none;
}

button.projects-kpi__row:hover {
  background: var(--bg-page, #f0f1f5);
  box-shadow: none;
}

.projects-kpi__row-dot {
  width: 8px;
  height: 8px;
  border-radius: 2px;
  flex-shrink: 0;
}

.projects-kpi__row-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  min-width: 0;
}

.projects-kpi__row-value {
  margin-left: auto;
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  font-variant-numeric: tabular-nums;
}

/* Footer band — geometry deliberately identical to .tasks-kpi__oldest and
   .timeline-kpi__rail-wrap: margin-top:auto, 8px/10px padding, 8px radius,
   11px/1.4 type. All three resolve to the same height and sit on one line. */
.projects-kpi__issues {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  background: #fef3f2;
  border-radius: 8px;
  color: #92400e;
  font-size: 11px;
  line-height: 1.4;
  min-width: 0;
  overflow: hidden;
}

.projects-kpi__issues svg {
  flex-shrink: 0;
  color: #dc2626;
}

/* Zero issues is good news, not a warning — drop the alarm colouring rather
   than showing a red-tinted banner that says nothing is wrong. */
.projects-kpi__issues--clear {
  background: var(--bg-page, #f0f1f5);
  color: var(--color-text-secondary, #6b7280);
}

.projects-kpi__issues--clear svg {
  color: var(--color-text-muted, #9ca3af);
}

.projects-kpi__issues-text strong {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.projects-kpi__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  text-align: center;
  padding: 12px 0;
}

.projects-kpi__header {
  position: relative;
}
/* Chrome comes from the theme's .iz-btn --primary --sm; only layout stays
   here. It used to hardcode the old #4a90d9 blue, which by the end of the
   migration was the one thing on the card still on the old palette.

   min-height is reset because NC core gives bare buttons 34px, which made this
   header 40px against the other cards' 32 and pushed the whole card down the
   strip. A min-height on the header can't cap a taller child. */
button.projects-kpi__new-btn {
  min-height: 0;
  margin-left: auto;
  flex-shrink: 0;
  white-space: nowrap;
}

</style>
