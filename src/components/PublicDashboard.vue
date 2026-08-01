<template>
  <div class="public-dashboard iz-app">
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
/* Page backdrop follows the In Zicht theme, same as the authenticated view.
   This used to force #f0f1f5 !important; a share link now renders in whatever
   scheme the visitor's browser reports, which is the behaviour every other In
   Zicht surface has. To pin it back to light, restore the literal here AND
   force the light palette on the subtree — a half-pin gives dark cards on a
   light ground. */
body.nc-guest-page #app-content:has(.public-dashboard) {
  background: var(--image-background);
}
#adminpage-root {
  background: var(--image-background);
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
  /* Tokens come from the theme's `.iz-app` bridge (server.css §8). Anything
     this page needs to override must be written as `.public-dashboard.iz-app`:
     theme CSS is appended after the app bundle, so at equal specificity the
     theme wins. */

  background: var(--bg-page);
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
