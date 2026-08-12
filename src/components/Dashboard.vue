<template>
  <!-- iz-app opts this subtree into the theme's primitive layer: it supplies the
       generic token names the components read (see server.css §8 "App token
       bridge") and is the ancestor the .iz-app-scoped primitives require. -->
  <div class="adminpage-dashboard iz-app">
    <!-- ── No Organization State ── -->
    <div v-if="!data.orgOverview" class="adminpage-dashboard__empty">
      <div class="adminpage-dashboard__empty-icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="48"
          height="48"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
          <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
      </div>
      <h2 class="adminpage-dashboard__empty-title">Admin access required</h2>
      <p class="adminpage-dashboard__empty-text">
        You're not an admin of any organization.<br />
        Only organization owners can access this dashboard.
      </p>
    </div>

    <template v-else>
      <!-- ── KPI Strip ── -->
      <section class="adminpage-dashboard__kpi-strip">
        <ProjectsKpiCard
          v-if="projectsKpi"
          :kpi="projectsKpi"
          :can-create="isOrgAdmin"
          @filter-projects="onFilterProjects"
          @create-project="onCreateProject"
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

      <StorageMonitoringPanel
        :storage="storageData"
        :loading="storageLoading"
        :error="storageError"
        @retry="$emit('retry-storage')"
      />

      <!-- ── Projects Map (filterable, clickable to drill into details) ── -->
      <ProjectsMapPanel
        :projects="(projectGeocodes && projectGeocodes.projects) || []"
        :org-members="(data.orgOverview && data.orgOverview.members) || []"
        :loading="projectGeocodesLoading"
        :geocoding-in-flight="(projectGeocodes && projectGeocodes.geocodingInFlight) || 0"
        @select-project="onSelectProject"
      />

      <!-- ── Project Performance Analytics ── -->
      <ProjectPerformancePanel
        ref="perfPanel"
        :project-progress="data.projectProgress"
        :member-performance="data.memberPerformance"
        :task-delay-projects="data.taskDelayProjects"
        :task-completion-projects="data.taskCompletionProjects"
        :performance-details="data.performanceDetails"
        :project-details="data.projectDetails || []"
        :org-members="(data.orgOverview && data.orgOverview.members) || []"
      />

      <!-- ── Organization Insights (Org + KPIs + Members + Subscription) ── -->
      <OrgInsightsPanel
        :profile="data.orgOverview.profile || {}"
        :members="data.orgOverview.members || []"
        :subscription="data.orgOverview.subscription || {}"
        :usage-summary="data.orgOverview.usageSummary || {}"
        :backup-jobs="backupJobs"
        :upcoming-events="upcomingEvents"
        :org-id="(data.orgOverview.profile && data.orgOverview.profile.id) || null"
        :admin-uid="(data.orgOverview.profile && data.orgOverview.profile.adminUid) || null"
        :current-uid="data.orgOverview.currentUid || null"
        @reload="$emit('reload')"
      />

      <!-- ── Public Dashboard Links Management ── -->
      <PublicLinksAdmin />
    </template>

    <CreateProjectModal
      v-if="showCreateModal && data.orgOverview"
      :org-id="(data.orgOverview.profile && data.orgOverview.profile.id) || null"
      :org-members="data.orgOverview.members || []"
      :current-uid="data.orgOverview.currentUid || null"
      @cancel="showCreateModal = false"
      @created="onProjectCreated"
    />
  </div>
</template>

<script>
import ProjectsKpiCard from "./ProjectsKpiCard.vue";
import TasksKpiCard from "./TasksKpiCard.vue";
import ResourcesKpiCard from "./ResourcesKpiCard.vue";
import TimelineKpiCard from "./TimelineKpiCard.vue";
import ProjectPerformancePanel from "./ProjectPerformancePanel.vue";
import ProjectsMapPanel from "./ProjectsMapPanel.vue";
import CreateProjectModal from "./CreateProjectModal.vue";
import OrgInsightsPanel from "./OrgInsightsPanel.vue";
import PublicLinksAdmin from "./PublicLinksAdmin.vue";
import StorageMonitoringPanel from "./StorageMonitoringPanel.vue";

export default {
  name: "Dashboard",
  components: {
    ProjectsKpiCard,
    TasksKpiCard,
    ResourcesKpiCard,
    TimelineKpiCard,
    ProjectPerformancePanel,
    ProjectsMapPanel,
    CreateProjectModal,
    OrgInsightsPanel,
    PublicLinksAdmin,
    StorageMonitoringPanel,
  },
  props: {
    data: {
      type: Object,
      required: true,
    },
    backupJobs: {
      type: Array,
      default: function () {
        return [];
      },
    },
    upcomingEvents: {
      type: Array,
      default: function () {
        return [];
      },
    },
    projectGeocodes: {
      type: Object,
      default: function () {
        return { projects: [], geocodingInFlight: 0 };
      },
    },
    projectGeocodesLoading: {
      type: Boolean,
      default: false,
    },
    storageData: {
      type: Object,
      default: null,
    },
    storageLoading: {
      type: Boolean,
      default: false,
    },
    storageError: {
      type: String,
      default: null,
    },
  },
  data: function () {
    return {
      showCreateModal: false,
    };
  },
  computed: {
    projectsKpi: function () {
      return (
        (this.data.kpis || []).find(function (k) {
          return k.id === "projects";
        }) || null
      );
    },
    tasksKpi: function () {
      return (
        (this.data.kpis || []).find(function (k) {
          return k.id === "tasks";
        }) || null
      );
    },
    resourcesKpi: function () {
      return (
        (this.data.kpis || []).find(function (k) {
          return k.id === "resources";
        }) || null
      );
    },
    timelineKpi: function () {
      return (
        (this.data.kpis || []).find(function (k) {
          return k.id === "timeline";
        }) || null
      );
    },
    isOrgAdmin: function () {
      var ov = this.data && this.data.orgOverview;
      var currentUid = ov && ov.currentUid;
      var members = (ov && ov.members) || [];
      return members.some(function (member) {
        return member.userId === currentUid && member.role === "admin";
      });
    },
  },
  methods: {
    onFilterProjects: function (statusLabel) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.filterProjectsByStatus(statusLabel);
      }
    },
    onFilterTasks: function (filterType, filterValue) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.filterTasks(filterType, filterValue);
      }
    },
    onGotoOldestTask: function (oldestTask) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.gotoOldestTask(oldestTask);
      }
    },
    onSelectProject: function (projectId) {
      if (this.$refs.perfPanel) {
        this.$refs.perfPanel.selectProject(projectId);
      }
    },
    onCreateProject: function () {
      this.showCreateModal = true;
    },
    onProjectCreated: async function (projectId) {
      this.showCreateModal = false;
      var self = this;
      // Await the reload so the new project lands in project-details BEFORE
      // we ask the perf panel to scroll. Otherwise setSelectedProject runs
      // against a stale list, the details panel renders empty, and
      // scrollIntoView lands above where the populated panel will end up
      // — same failure mode the Task Delay → Details button avoids by
      // pointing at an already-loaded project.
      if (this.$root && typeof this.$root.fetchData === "function") {
        try { await this.$root.fetchData(); } catch (e) { /* ignore */ }
      } else {
        this.$emit("reload");
      }
      // Re-fetch the map so the new pin appears (once geocoded). Fire-and-
      // forget — it doesn't affect the details panel height.
      if (this.$root && typeof this.$root.fetchProjectGeocodes === "function") {
        this.$root.fetchProjectGeocodes();
      }
      // Two ticks: one for the data prop to propagate down, one for the
      // details panel to render the newly-selected project before we scroll.
      await this.$nextTick();
      await this.$nextTick();
      if (self.$refs.perfPanel) self.$refs.perfPanel.selectProject(projectId);
    },
  },
};
</script>

<style>
/* unscoped — page backdrop follows the In Zicht theme (light + dark).
   Deliberately NOT `!important` with a hardcoded grey: forcing a light
   backdrop here is what broke dark mode in superadminpage. --image-background
   is the theme's own page ground and flips with the colour scheme. */
#app-content:has(.adminpage-dashboard) {
  background: var(--image-background);
}

#adminpage-root {
  background: var(--image-background);
  min-height: 100vh;
}

/* ---- Cross-cutting In Zicht behaviours (unscoped so they reach every child
   component's elements regardless of scoped data-v attributes) ---- */

/* Space Grotesk on titles + big value numerals across all panels */
.adminpage-dashboard [class*="__title"],
.adminpage-dashboard [class*="__metric-value"],
.adminpage-dashboard [class*="__value"],
.adminpage-dashboard [class*="__count"],
.adminpage-dashboard [class*="__figure"],
.adminpage-dashboard [class*="__number"],
.adminpage-dashboard [class*="__amount"],
.adminpage-dashboard [class*="__headline"] {
  font-family: "Space Grotesk", system-ui, -apple-system, sans-serif;
}

/* Buttons — smooth transition on all, hover lift on primary/create actions.
   No :focus-visible ring: NC core's
   `button:not(.button-vue,…):not(:disabled,.primary):focus-visible { outline: … !important }`
   wins over anything we can set here, so such a rule would be dead CSS. */
.adminpage-dashboard [class*="btn"] {
  transition: background-color 0.2s ease, border-color 0.2s ease,
    box-shadow 0.2s ease, transform 0.2s ease;
}
.adminpage-dashboard [class*="btn--primary"]:hover:not(:disabled),
.adminpage-dashboard [class*="__create-btn"]:hover:not(:disabled),
.adminpage-dashboard [class*="__add-btn"]:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--iz-shadow-accent);
}

/* Native form controls: handled by the theme's `.iz-app input[type=...]`
   rule now that this subtree carries iz-app. */
</style>

<style scoped>
.adminpage-dashboard {
  /* Tokens come from the theme's `.iz-app` bridge (server.css §8). The local
     copy that used to live here is gone — see that section for why one
     definition matters. Only page-layout values stay in this file. */


  /* `background`, not `background-color`: --bg-page resolves to the theme's
     --image-background, which can be a gradient. */
  background: var(--bg-page);
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-lg);
  font-family: "Inter", system-ui, -apple-system, sans-serif;
  color: var(--color-text-primary);
}

.adminpage-dashboard__kpi-strip {
  display: grid;
  /* minmax(0, …) rather than a bare 1fr: 1fr's implicit minimum is `auto`, so
     a card with long content widens its own track at its neighbours' expense.
     Timeline's stat labels were taking it to 329px while Projects was squeezed
     to 253, which pulled the four cards' internals out of line. */
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
}

/* ─── Empty State ─── */
.adminpage-dashboard__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 50vh;
  text-align: center;
  padding: var(--spacing-2xl);
}

.adminpage-dashboard__empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: var(--accent-bg);
  color: var(--accent-on-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
}

.adminpage-dashboard__empty-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 8px 0;
  padding: 0;
  border: none;
}

.adminpage-dashboard__empty-text {
  font-size: 14px;
  color: var(--color-text-secondary);
  line-height: 1.5;
  margin: 0;
}

@media (max-width: 1200px) {
  .adminpage-dashboard__kpi-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .adminpage-dashboard__kpi-strip {
    grid-template-columns: 1fr;
  }
}
</style>
