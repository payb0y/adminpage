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
      <h2 class="adminpage-dashboard__empty-title">No Organization Found</h2>
      <p class="adminpage-dashboard__empty-text">
        Your account is not associated with any organization.<br />
        Please contact your administrator.
      </p>
    </div>

    <template v-else>
      <!-- ── Org Header ── -->
      <OrgHeaderBar :org-overview="data.orgOverview" />

      <!-- ── KPI Strip ── -->
      <section class="adminpage-dashboard__kpi-strip">
        <KpiCard
          v-for="kpi in data.kpis"
          :key="kpi.id"
          :title="kpi.title"
          :icon="kpi.icon"
          :icon-color="kpi.iconColor"
          :metrics="kpi.metrics"
        />
      </section>

      <!-- ── Alerts & Exceptions ── -->
      <AlertsPanel :alerts="data.alerts" />

      <!-- ── Organization ── -->
      <OrganizationPanel :profile="data.orgOverview.profile || {}" />

      <!-- ── Project Performance Analytics ── -->
      <ProjectPerformancePanel
        :project-progress="data.projectProgress"
        :member-performance="data.memberPerformance"
        :task-delay-projects="data.taskDelayProjects"
        :task-completion-projects="data.taskCompletionProjects"
        :performance-details="data.performanceDetails"
      />

      <!-- ── Team Members ── -->
      <MembersPanel :members="data.orgOverview.members || []" />

      <!-- ── Subscription & Plan ── -->
      <SubscriptionPanel
        :subscription="data.orgOverview.subscription || {}"
        :usage-summary="data.orgOverview.usageSummary || {}"
      />
    </template>
  </div>
</template>

<script>
import KpiCard from "./KpiCard.vue";
import AlertsPanel from "./AlertsPanel.vue";
import OrgHeaderBar from "./OrgHeaderBar.vue";
import OrganizationPanel from "./OrganizationPanel.vue";
import ProjectPerformancePanel from "./ProjectPerformancePanel.vue";
import MembersPanel from "./MembersPanel.vue";
import SubscriptionPanel from "./SubscriptionPanel.vue";

export default {
  name: "Dashboard",
  components: {
    KpiCard,
    AlertsPanel,
    OrgHeaderBar,
    OrganizationPanel,
    ProjectPerformancePanel,
    MembersPanel,
    SubscriptionPanel,
  },
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
};
</script>

<style>
#app-content:has(.adminpage-dashboard) {
  background-color: #f0f1f5 !important;
}

#adminpage-root {
  background-color: #f0f1f5 !important;
  min-height: 100vh;
}
</style>

<style scoped>
.adminpage-dashboard {
  --bg-page: #f0f1f5;
  --bg-card: #ffffff;
  --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.08);
  --shadow-card-hover: 0 4px 12px rgba(0, 0, 0, 0.1);
  --radius-card: 12px;
  --color-text-primary: #1a1a2e;
  --color-text-secondary: #6b7280;
  --color-text-muted: #9ca3af;
  --color-danger: #d94040;
  --color-warning: #b8860b;
  --color-success: #2e7d32;
  --color-badge-danger-bg: #fde8e8;
  --color-badge-danger-text: #b91c1c;
  --color-badge-warning-bg: #fef3cd;
  --color-badge-warning-text: #92400e;
  --color-badge-success-bg: #d4edda;
  --color-badge-success-text: #166534;
  --color-border: #e5e7eb;
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 40px;

  background-color: var(--bg-page);
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-lg);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
    Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Arial,
    sans-serif;
  color: var(--color-text-primary);
}

.adminpage-dashboard__kpi-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
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
  background: #e8f0fe;
  color: #4a90d9;
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

@media (max-width: 1024px) {
  .adminpage-dashboard__kpi-strip {
    grid-template-columns: 1fr;
  }
}
</style>
