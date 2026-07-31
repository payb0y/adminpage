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

    <!-- Delivery window — full-width footer, same band as TasksKpiCard's
         oldest-task footer and ProjectsKpiCard's issue line.

         Shows what a percentage can't: the actual dates, where today falls
         between them, and where each project's deadline lands. It previously
         drew a bar of the elapsed percentage, which the hero already states —
         a second rendering of one number rather than a second fact. -->
    <div
      v-if="schedule"
      class="timeline-kpi__rail-wrap"
      :title="scheduleTitle"
    >
      <span class="timeline-kpi__rail-date">{{ formatDate(schedule.start) }}</span>
      <div class="timeline-kpi__rail">
        <div
          class="timeline-kpi__rail-fill"
          :style="{ width: schedule.todayPct + '%' }"
        ></div>
        <span
          v-for="(d, i) in schedule.deadlines"
          :key="i"
          class="timeline-kpi__rail-deadline"
          :style="{ left: d.pct + '%' }"
          :title="d.label + ' due ' + formatDate(d.date)"
        ></span>
        <span
          class="timeline-kpi__rail-now"
          :style="{ left: schedule.todayPct + '%' }"
          title="Today"
        ></span>
      </div>
      <span class="timeline-kpi__rail-date">{{ formatDate(schedule.end) }}</span>
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
    /* The row has no room to name each deadline, so they go in a title. */
    scheduleTitle: function () {
      var s = this.schedule;
      if (!s) return "";
      var self = this;
      var due = s.deadlines
        .map(function (d) {
          return d.label + " due " + self.formatDate(d.date);
        })
        .join(", ");
      return (
        "Delivery window " +
        this.formatDate(s.start) +
        " to " +
        this.formatDate(s.end) +
        (due ? " \u2014 " + due : "")
      );
    },
    /* null when no project has a start and end date, which omits the rail
       rather than drawing an empty track. */
    schedule: function () {
      var s = this.kpi.schedule;
      if (!s || !s.start || !s.end) return null;
      return {
        start: s.start,
        end: s.end,
        todayPct: typeof s.todayPct === "number" ? s.todayPct : 0,
        deadlines: Array.isArray(s.deadlines) ? s.deadlines : [],
      };
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
    /* '2026-10-23' -> '23 Oct'. Built from parts rather than toLocaleDateString
       so the label can't run long enough to break the rail's single row. */
    formatDate: function (iso) {
      var parts = String(iso).split("-");
      if (parts.length !== 3) return iso;
      var months = [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
      ];
      var m = parseInt(parts[1], 10);
      var d = parseInt(parts[2], 10);
      if (!m || !d || m < 1 || m > 12) return iso;
      return d + " " + months[m - 1];
    },
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
      var track = "rgba(0,0,0,0.06)";

      /* One ring: completion. It used to carry a second, inner ring for
         schedule elapsed, which drew the same number the rail below already
         showed — and at 0% completion the outer ring is invisible, so the
         elapsed arc read as the card's only progress. Elapsed now lives once,
         as a figure in the hero. */
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
/* Single row so this footer resolves to the same height as
   .tasks-kpi__oldest and .projects-kpi__issues and the three sit on one band.
   A stacked version with the dates underneath came out 39px against their 31
   and broke the line. */
.timeline-kpi__rail-wrap {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 8px;
  font-size: 11px;
  line-height: 1.4;
}

.timeline-kpi__rail-date {
  flex-shrink: 0;
  color: var(--color-text-secondary, #6b7280);
  font-variant-numeric: tabular-nums;
}

.timeline-kpi__rail {
  position: relative;
  flex: 1;
  min-width: 0;
  height: 4px;
  border-radius: 2px;
  background: var(--color-border, #e5e7eb);
}

/* Time already spent, up to today. */
.timeline-kpi__rail-fill {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  border-radius: 2px;
  background: var(--iz-cat-5, #7c5cbf);
  transition: width 0.4s ease;
}

/* One tick per project deadline, so clustered due dates are visible. */
.timeline-kpi__rail-deadline {
  position: absolute;
  top: 50%;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  background: var(--bg-page, #f0f1f5);
  border: 1.5px solid var(--iz-cat-5, #7c5cbf);
}

.timeline-kpi__rail-now {
  position: absolute;
  top: -4px;
  bottom: -4px;
  width: 2px;
  border-radius: 1px;
  background: var(--color-text-primary, #1a1a2e);
  transform: translateX(-1px);
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
