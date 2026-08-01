<template>
  <div class="public-dashboard">
    <!-- ── KPI Strip ── -->
    <section class="public-dashboard__kpi-strip">
      <ProjectsKpiCard
        v-if="projectsKpi"
        :kpi="projectsKpi"
        @filter-projects="onFilterProjects"
      />
      <TasksKpiCard
        v-if="tasksKpi"
        :kpi="tasksKpi"
        @filter-tasks="onFilterTasks"
        @goto-oldest-task="onGotoOldestTask"
      />
      <ResourcesKpiCard v-if="resourcesKpi" :kpi="resourcesKpi" />
      <TimelineKpiCard v-if="timelineKpi" :kpi="timelineKpi" />
    </section>

    <!-- ── Project Performance Analytics ── -->
    <ProjectPerformancePanel
      ref="perfPanel"
      :project-progress="data.projectProgress"
      :member-performance="[]"
      :show-member-performance="false"
      :show-team-workload="false"
      :show-assignees="false"
      :task-delay-projects="data.taskDelayProjects"
      :task-completion-projects="data.taskCompletionProjects"
      :performance-details="data.performanceDetails || null"
      :project-details="data.projectDetails || []"
    />

    <!-- ── Footer ── -->
    <footer class="public-dashboard__footer">
      <span>Public Dashboard</span>
    </footer>
  </div>
</template>

<script>
import ProjectsKpiCard from "./ProjectsKpiCard.vue";
import TasksKpiCard from "./TasksKpiCard.vue";
import ResourcesKpiCard from "./ResourcesKpiCard.vue";
import TimelineKpiCard from "./TimelineKpiCard.vue";
import ProjectPerformancePanel from "./ProjectPerformancePanel.vue";

export default {
  name: "PublicDashboard",
  components: {
    ProjectsKpiCard,
    TasksKpiCard,
    ResourcesKpiCard,
    TimelineKpiCard,
    ProjectPerformancePanel,
  },
  props: {
    data: { type: Object, required: true },
  },
  computed: {
    projectsKpi() {
      return (this.data.kpis || []).find((k) => k.id === "projects") || null;
    },
    tasksKpi() {
      return (this.data.kpis || []).find((k) => k.id === "tasks") || null;
    },
    resourcesKpi() {
      return (this.data.kpis || []).find((k) => k.id === "resources") || null;
    },
    timelineKpi() {
      return (this.data.kpis || []).find((k) => k.id === "timeline") || null;
    },
  },
  methods: {
    onFilterProjects(statusLabel) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.filterProjectsByStatus(statusLabel);
      }
    },
    onFilterTasks(filterType, filterValue) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.filterTasks(filterType, filterValue);
      }
    },
    onGotoOldestTask(oldestTask) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.gotoOldestTask(oldestTask);
      }
    },
  },
};
</script>

<style>
/* Override Nextcloud public page background */
body.nc-guest-page #app-content:has(.public-dashboard) {
  background-color: #f0f1f5 !important;
}
#adminpage-root {
  background-color: #f0f1f5 !important;
  min-height: 100vh;
}

/* Same cross-cutting In Zicht rules as Dashboard.vue, so a share link renders
   with the same typography as the authenticated view. Unscoped so they reach
   child components' elements past their scoped data-v attributes. */
.public-dashboard [class*="__title"],
.public-dashboard [class*="__metric-value"],
.public-dashboard [class*="__value"],
.public-dashboard [class*="__count"],
.public-dashboard [class*="__figure"],
.public-dashboard [class*="__number"],
.public-dashboard [class*="__amount"],
.public-dashboard [class*="__headline"] {
  font-family: "Space Grotesk", system-ui, -apple-system, sans-serif;
}

.public-dashboard [class*="btn"] {
  transition: background-color 0.2s ease, border-color 0.2s ease,
    box-shadow 0.2s ease, transform 0.2s ease;
}
</style>

<style scoped>
.public-dashboard {
  /* ── Same alias block as Dashboard.vue — see the comment there ──
     This page renders the four KPI cards and ProjectPerformancePanel, the same
     components as the authenticated dashboard, so it has to define every token
     they consume. It previously declared eleven of them; the components ask for
     roughly twenty-five, and an undefined custom property makes the whole
     declaration invalid, which silently drops the colour rather than falling
     back. Keep this list in step with Dashboard.vue.

     Unlike Dashboard.vue this page keeps `color-scheme: light` and a literal
     page ground: a share link is opened by people outside the organisation, on
     their own machines, and the theme's palette keys off attributes Nextcloud
     may not emit on a guest page. Every alias below therefore carries the light
     literal as its fallback, so the page renders correctly whether or not the
     theme's variables are in scope. */

  /* surfaces */
  --bg-page: #f0f1f5;
  --bg-card: var(--iz-surface, #ffffff);
  --bg-subtle: var(--iz-surface-subtle, #faf6fa);
  --bg-inset: var(--iz-surface-inset, #f3ecf3);

  /* text */
  --color-text-primary: var(--iz-text, #24172e);
  --color-text-secondary: var(--iz-text-secondary, #6a6472);
  --color-text-muted: var(--iz-text-muted, #9a94a2);

  /* borders */
  --color-border: var(--iz-border, #e5e7eb);
  --color-border-strong: var(--iz-border-strong, #e6d8e6);

  /* accent */
  --accent: var(--iz-accent, #cc3d94);
  --accent-hover: var(--iz-accent-hover, #bd3487);
  --accent-strong: var(--iz-cat-2, #3a2350);
  --accent-bg: var(--iz-accent-bg, #f6e4f0);
  --accent-on-bg: var(--iz-accent-bg-text, #8a2b6b);

  /* radii */
  --radius-card: var(--iz-radius-card, 14px);
  --radius-el: var(--iz-radius, 8px);
  --radius-sm: var(--iz-radius-sm, 6px);
  --radius-lg: var(--iz-radius-lg, 10px);
  --radius-pill: var(--iz-radius-pill, 9999px);

  /* shadows */
  --shadow-card: var(--iz-shadow, 0 1px 3px rgba(0, 0, 0, 0.08));
  --shadow-card-hover: var(--iz-shadow-lift, 0 4px 12px rgba(0, 0, 0, 0.1));

  /* status */
  --color-danger: var(--iz-danger, #c9314a);
  --color-danger-text: var(--iz-danger-text, #b42318);
  --color-danger-bg: var(--iz-danger-bg, #fde8e8);
  --color-warning-text: var(--iz-warning-text, #a86a12);
  --color-warning-bg: var(--iz-warning-bg, #fef3cd);
  --color-success: var(--iz-success, #1f7a3e);
  --color-success-text: var(--iz-success-text, #166534);
  --color-success-bg: var(--iz-success-bg, #d4edda);
  --color-badge-danger-bg: var(--iz-danger-bg, #fde8e8);
  --color-badge-danger-text: var(--iz-danger-text, #b42318);
  --color-badge-warning-bg: var(--iz-warning-bg, #fef3cd);
  --color-badge-warning-text: var(--iz-warning-text, #a86a12);
  --color-badge-success-bg: var(--iz-success-bg, #d4edda);
  --color-badge-success-text: var(--iz-success-text, #166534);

  /* chart palette */
  --chart-1: var(--iz-cat-1, #cc3d94);
  --chart-2: var(--iz-cat-2, #3a2350);
  --chart-3: var(--iz-cat-3, #2f9e8f);
  --chart-4: var(--iz-cat-4, #d98a2b);
  --chart-5: var(--iz-cat-5, #7c5cbf);
  --chart-5-bg: var(--iz-cat-5-bg, #f3e8ff);

  /* NC variables the child components read directly */
  --color-main-background: #ffffff;
  --color-background-hover: #f3f4f6;
  --color-text-maxcontrast: #1a1a2e;
  --color-text-light: #6b7280;

  /* spacing */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 40px;

  color-scheme: light;

  background-color: var(--bg-page);
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  font-family: "Inter", system-ui, -apple-system, sans-serif;
}

.public-dashboard__kpi-strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.public-dashboard__footer {
  text-align: center;
  padding: 24px 0 12px;
  color: var(--color-text-secondary);
  font-size: 12px;
}

@media (max-width: 1200px) {
  .public-dashboard__kpi-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .public-dashboard__kpi-strip {
    grid-template-columns: 1fr;
  }
  .public-dashboard {
    padding: 12px;
  }
}
</style>
