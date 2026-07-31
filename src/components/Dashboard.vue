<template>
  <div class="adminpage-dashboard">
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
      var profile = ov && ov.profile;
      var adminUid = profile && profile.adminUid;
      var currentUid = ov && ov.currentUid;
      return !!currentUid && !!adminUid && currentUid === adminUid;
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

/* Native form controls follow the In Zicht accent */
.adminpage-dashboard input[type="checkbox"],
.adminpage-dashboard input[type="radio"],
.adminpage-dashboard input[type="range"],
.adminpage-dashboard progress {
  accent-color: var(--accent);
}
</style>

<style scoped>
.adminpage-dashboard {
  /* ── App tokens are ALIASES of the In Zicht theme's .iz-* primitives ──
     The real definitions live in the theme (server.css §8), shared with the
     other In Zicht apps and — unlike this bundle — cache-busted by Nextcloud's
     ?v=. These aliases exist so the 22 components still written against the
     generic names resolve to theme values without being touched; as a
     component converts to .iz-* classes, its uses of these names go away.
     Do NOT re-add a literal value here — change it in the theme. */

  /* surfaces */
  --bg-page: var(--image-background, linear-gradient(135deg, #f7e9f2, #fdf9fc)); /* gradient → use with `background:` */
  --bg-card: var(--iz-surface, var(--color-main-background, #fff));
  --bg-subtle: var(--iz-surface-subtle, var(--color-background-hover, #faf6fa));
  --bg-inset: var(--iz-surface-inset, var(--color-background-dark, #f3ecf3));

  /* text */
  --color-text-primary: var(--iz-text, var(--color-main-text, #24172e));
  --color-text-secondary: var(--iz-text-secondary, var(--color-text-maxcontrast, #6a6472));
  --color-text-muted: var(--iz-text-muted, color-mix(in oklab, var(--color-text-maxcontrast, #6a6472) 70%, var(--color-main-background, #fff)));

  /* borders — --color-border is inherited from the theme (do NOT redefine: cycle) */
  --color-border-strong: var(--iz-border-strong, var(--color-border-dark, #e6d8e6));

  /* accent (pink) */
  --accent: var(--iz-accent, var(--color-primary-element, #cc3d94));
  --accent-hover: var(--iz-accent-hover, var(--color-primary-element-hover, #bd3487));
  --accent-strong: var(--iz-cat-2, var(--color-primary, #3a2350));
  --accent-bg: var(--iz-accent-bg, var(--color-primary-element-light, #f6e4f0));
  --accent-on-bg: var(--iz-accent-bg-text, var(--color-primary-element-light-text, #8a2b6b));

  /* radii */
  --radius-card: var(--iz-radius-card, var(--border-radius-container, 14px));
  --radius-el: var(--iz-radius, var(--border-radius-element, 8px));
  --radius-sm: var(--iz-radius-sm, var(--border-radius-small, 6px));
  --radius-lg: var(--iz-radius-lg, var(--border-radius-large, 10px));
  --radius-pill: var(--iz-radius-pill, var(--border-radius-pill, 9999px));

  /* shadows — In Zicht pink glow */
  --shadow-card: var(--iz-shadow, 0 1px 3px rgba(0, 0, 0, 0.08));
  --shadow-card-hover: var(--iz-shadow-lift, 0 12px 32px -8px rgba(204, 61, 148, 0.15), 0 4px 12px -4px rgba(0, 0, 0, 0.08));

  /* status — semantic */
  --color-danger: var(--iz-danger, var(--color-error, #c9314a));
  --color-danger-text: var(--iz-danger-text, var(--color-error, #b42318));
  --color-danger-bg: var(--iz-danger-bg, color-mix(in oklab, var(--color-error, #c9314a) 14%, var(--color-main-background, #fff)));
  --color-warning-text: var(--iz-warning-text, #a86a12);
  --color-warning-bg: var(--iz-warning-bg, color-mix(in oklab, var(--color-warning, #ecc980) 30%, var(--color-main-background, #fff)));
  --color-success: var(--iz-success, #1f7a3e);
  --color-success-text: var(--iz-success-text, #166534);
  --color-success-bg: var(--iz-success-bg, color-mix(in oklab, #1f7a3e 14%, var(--color-main-background, #fff)));

  /* legacy badge token names (children reference them) → point at the ramps */
  --color-badge-danger-bg: var(--iz-danger-bg, color-mix(in oklab, var(--color-error, #c9314a) 14%, var(--color-main-background, #fff)));
  --color-badge-danger-text: var(--iz-danger-text, var(--color-error, #b42318));
  --color-badge-warning-bg: var(--iz-warning-bg, color-mix(in oklab, var(--color-warning, #ecc980) 30%, var(--color-main-background, #fff)));
  --color-badge-warning-text: var(--iz-warning-text, #a86a12);
  --color-badge-success-bg: var(--iz-success-bg, color-mix(in oklab, #1f7a3e 14%, var(--color-main-background, #fff)));
  --color-badge-success-text: var(--iz-success-text, #166534);

  /* chart palette — series 1 = pink, reharmonized for light + dark */
  --chart-1: var(--iz-cat-1, var(--color-primary-element, #cc3d94));
  --chart-2: var(--iz-cat-2, var(--color-primary, #3a2350));
  --chart-3: var(--iz-cat-3, #2f9e8f);
  --chart-4: var(--iz-cat-4, #d98a2b);
  --chart-5: var(--iz-cat-5, #7c5cbf);
  --chart-5-bg: var(--iz-cat-5-bg, color-mix(in oklab, #7c5cbf 16%, var(--color-main-background, #fff)));

  /* spacing — unchanged */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 40px;

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
