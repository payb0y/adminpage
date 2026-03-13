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
</style>

<style scoped>
.public-dashboard {
  --bg-page: #f0f1f5;
  --bg-card: #ffffff;
  --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.08);
  --radius-card: 12px;
  --color-text-primary: #1a1a2e;
  --color-text-secondary: #6b7280;
  --color-main-background: #ffffff;
  --color-background-hover: #f3f4f6;
  --color-border: #e5e7eb;
  --color-text-maxcontrast: #1a1a2e;
  --color-text-light: #6b7280;
  color-scheme: light;

  background-color: var(--bg-page);
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.public-dashboard__kpi-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
    grid-template-columns: repeat(2, 1fr);
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
