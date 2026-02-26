<template>
  <section class="insights-panel">
    <!-- ── Collapsible Header ── -->
    <div class="insights-panel__header" @click="collapsed = !collapsed">
      <h3 class="insights-panel__title">
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
            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
          />
          <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
          <line x1="12" y1="22.08" x2="12" y2="12" />
        </svg>
        Organization Insights
      </h3>
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
        class="insights-panel__chevron"
        :class="{ 'insights-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="!collapsed" class="insights-panel__body">
      <!-- ── Sub-section: Organization ── -->
      <div class="insights-panel__section">
        <div class="insights-panel__section-title">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
          </svg>
          Organization
        </div>
        <OrganizationPanel :embedded="true" :profile="profile" />
      </div>

      <!-- ── Divider ── -->
      <div class="insights-panel__divider"></div>

      <!-- ── Sub-section: Team Members ── -->
      <div class="insights-panel__section">
        <div class="insights-panel__section-title">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          Team Members
          <span class="insights-panel__badge">{{ members.length }}</span>
        </div>
        <MembersPanel :embedded="true" :members="members" />
      </div>

      <!-- ── Divider ── -->
      <div class="insights-panel__divider"></div>

      <!-- ── Sub-section: Subscription & Plan ── -->
      <div class="insights-panel__section">
        <div class="insights-panel__section-title">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
            <line x1="1" y1="10" x2="23" y2="10" />
          </svg>
          Subscription &amp; Plan
        </div>
        <SubscriptionPanel
          :embedded="true"
          :subscription="subscription"
          :usage-summary="usageSummary"
        />
      </div>
    </div>
  </section>
</template>

<script>
import OrganizationPanel from "./OrganizationPanel.vue";
import MembersPanel from "./MembersPanel.vue";
import SubscriptionPanel from "./SubscriptionPanel.vue";

export default {
  name: "OrgInsightsPanel",
  components: {
    OrganizationPanel,
    MembersPanel,
    SubscriptionPanel,
  },
  props: {
    profile: {
      type: Object,
      default: function () {
        return {};
      },
    },
    members: {
      type: Array,
      default: function () {
        return [];
      },
    },
    subscription: {
      type: Object,
      default: function () {
        return {};
      },
    },
    usageSummary: {
      type: Object,
      default: function () {
        return {};
      },
    },
  },
  data: function () {
    return {
      collapsed: false,
    };
  },
};
</script>

<style scoped>
.insights-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.insights-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.insights-panel__header:hover {
  background: #fafbfd;
}

.insights-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.insights-panel__title svg {
  color: #4a90d9;
}

.insights-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.insights-panel__chevron--rotated {
  transform: rotate(180deg);
}

.insights-panel__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

/* ─── Sub-section Title ─── */
.insights-panel__section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 12px;
  padding: 4px 0;
}

.insights-panel__section-title svg {
  opacity: 0.6;
}

.insights-panel__badge {
  font-size: 10px;
  font-weight: 600;
  background: #e8f0fe;
  color: #1e4a8a;
  padding: 1px 7px;
  border-radius: 8px;
  margin-left: 2px;
}

/* ─── Divider ─── */
.insights-panel__divider {
  height: 1px;
  background: #eef1f5;
  margin: var(--spacing-lg, 24px) 0;
}

/* ─── Section spacing ─── */
.insights-panel__section {
  /* no extra styles needed, just a grouping element */
}
</style>
