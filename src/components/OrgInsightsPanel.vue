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
        <MembersPanel
          :embedded="true"
          :members="members"
          :org-id="orgId"
          :admin-uid="adminUid"
          :current-uid="currentUid"
          :owner-uid="adminUid"
          @reload="$emit('reload')"
        />
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

      <!-- ── Divider ── -->
      <div class="insights-panel__divider"></div>

      <!-- ── Sub-section: Backups ── -->
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
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
          </svg>
          Backups
          <span v-if="backupJobs.length" class="insights-panel__badge">{{ backupJobs.length }}</span>
        </div>
        <BackupsPanel :embedded="true" :jobs="backupJobs" />
      </div>

      <!-- ── Divider ── -->
      <div class="insights-panel__divider"></div>

      <!-- ── Sub-section: Capacity ──
           Sits next to Backups because both are about bytes on disk. The
           status pill and the Updated/Refresh controls live here rather than
           in StorageMonitoringPanel so the row matches its five siblings; the
           panel below renders the figures only. -->
      <section class="insights-panel__section" aria-labelledby="insights-capacity-title">
        <div id="insights-capacity-title" class="insights-panel__section-title">
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
            <line x1="22" y1="12" x2="2" y2="12" />
            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
            <line x1="6" y1="16" x2="6.01" y2="16" />
            <line x1="10" y1="16" x2="10.01" y2="16" />
          </svg>
          Capacity
          <span v-if="storage" :class="['iz-pill', storagePillClass]">{{ storageStatus }}</span>
          <span class="insights-panel__section-actions">
            <span v-if="storage" class="insights-panel__section-meta">Updated {{ storageUpdatedAt }}</span>
            <button
              class="iz-btn iz-btn--ghost iz-btn--sm"
              type="button"
              :disabled="storageLoading"
              @click="$emit('retry-storage')"
            >
              {{ storageLoading ? "Refreshing..." : "Refresh" }}
            </button>
          </span>
        </div>
        <StorageMonitoringPanel
          :embedded="true"
          :storage="storage"
          :loading="storageLoading"
          :error="storageError"
          @retry="$emit('retry-storage')"
        />
      </section>

      <!-- ── Divider ── -->
      <div class="insights-panel__divider"></div>

      <!-- ── Sub-section: Resources ──
           Moved out of the KPI strip: item counts belong beside Capacity's
           storage bytes, the same question at a different grain. Rendered as
           figures rather than the donut the KPI card used — four counts that
           do not sum to a meaningful whole are not a chart. -->
      <div v-if="resourceCells.length" class="insights-panel__section">
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
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
          </svg>
          Resources
        </div>
        <div class="insights-panel__resources">
          <div
            v-for="cell in resourceCells"
            :key="'res-' + cell.label"
            class="insights-panel__resource"
          >
            <span class="insights-panel__resource-value">{{ cell.value }}</span>
            <span class="insights-panel__resource-label">{{ cell.label }}</span>
            <span
              v-if="cell.sub"
              class="insights-panel__resource-sub"
              >{{ cell.sub }}</span
            >
          </div>
        </div>
      </div>

      <!-- ── Divider ── -->
      <div v-if="resourceCells.length" class="insights-panel__divider"></div>

      <!-- ── Sub-section: Upcoming Events ── -->
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
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          Upcoming events
          <span v-if="upcomingEvents.length" class="insights-panel__badge">{{ upcomingEvents.length }}</span>
        </div>
        <UpcomingEventsPanel :embedded="true" :events="upcomingEvents" />
      </div>
    </div>
  </section>
</template>

<script>
import OrganizationPanel from "./OrganizationPanel.vue";
import MembersPanel from "./MembersPanel.vue";
import SubscriptionPanel from "./SubscriptionPanel.vue";
import BackupsPanel from "./BackupsPanel.vue";
import UpcomingEventsPanel from "./UpcomingEventsPanel.vue";
import StorageMonitoringPanel from "./StorageMonitoringPanel.vue";

export default {
  name: "OrgInsightsPanel",
  components: {
    OrganizationPanel,
    MembersPanel,
    SubscriptionPanel,
    BackupsPanel,
    UpcomingEventsPanel,
    StorageMonitoringPanel,
  },
  props: {
    /* The `resources` entry from the dashboard's kpis array — the same object
       ResourcesKpiCard used to render in the strip. Null when the org has
       none, which hides the section. */
    resources: {
      type: Object,
      default: null,
    },
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
    orgId: {
      type: Number,
      default: null,
    },
    adminUid: {
      type: String,
      default: null,
    },
    currentUid: {
      type: String,
      default: null,
    },
    storage: {
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
      collapsed: true,
    };
  },
  computed: {
    /* KpiService ships these as {value, label} strings, with the file and note
       counts pre-formatted as "23 pub / 161 priv". Split that into a figure
       and a sub-line so the four cells share one baseline. */
    resourceCells: function () {
      var metrics = (this.resources && this.resources.metrics) || [];
      var cells = [];
      for (var i = 0; i < metrics.length; i++) {
        var raw = String(metrics[i].value === undefined ? "" : metrics[i].value);
        var split = raw.match(/^(\d+)\s*pub\s*\/\s*(\d+)\s*priv$/i);
        if (split) {
          cells.push({
            label: metrics[i].label,
            value: String(Number(split[1]) + Number(split[2])),
            sub: split[1] + " public · " + split[2] + " private",
          });
        } else {
          cells.push({ label: metrics[i].label, value: raw, sub: "" });
        }
      }
      return cells;
    },
    storageSummary: function () {
      return (this.storage && this.storage.summary) || {};
    },
    storageStatus: function () {
      var percentage = this.storageSummary.percentage;
      if (percentage === null || percentage === undefined) return "Capacity unavailable";
      if (percentage >= 100) return "Action needed";
      if (percentage >= 80) return "Watch closely";
      return "Healthy";
    },
    storagePillClass: function () {
      var percentage = this.storageSummary.percentage;
      if (percentage === null || percentage === undefined) return "iz-pill--muted";
      if (percentage >= 100) return "iz-pill--danger";
      if (percentage >= 80) return "iz-pill--warning";
      return "iz-pill--success";
    },
    storageUpdatedAt: function () {
      if (!this.storageSummary.calculatedAt) return "recently";
      return new Intl.DateTimeFormat(undefined, { hour: "2-digit", minute: "2-digit" }).format(
        new Date(this.storageSummary.calculatedAt)
      );
    },
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
  background: var(--bg-subtle);
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
  color: var(--accent);
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
.insights-panel__resources {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.insights-panel__resource {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 12px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-el, 8px);
  background: var(--bg-subtle);
}

.insights-panel__resource-value {
  font-size: 20px;
  font-weight: 600;
  line-height: 1.15;
  color: var(--color-text-primary);
  font-variant-numeric: tabular-nums;
}

.insights-panel__resource-label {
  font-size: 12px;
  color: var(--color-text-secondary);
}

.insights-panel__resource-sub {
  font-size: 11px;
  color: var(--color-text-muted);
}

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
  background: var(--accent-bg);
  color: var(--accent-strong);
  padding: 1px 7px;
  border-radius: 8px;
  margin-left: 2px;
}

/* ─── Controls in a section title row ───
   Capacity is the first section to carry any. The title row is uppercase and
   letter-spaced and both inherit, so a button dropped in reads "REFRESH" at
   0.03em — reset them here rather than on each control. */
.insights-panel__section-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
  text-transform: none;
  letter-spacing: normal;
}

.insights-panel__section-meta {
  font-size: var(--iz-fs-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

/* ─── Divider ─── */
.insights-panel__divider {
  height: 1px;
  background: var(--bg-subtle);
  margin: var(--spacing-lg, 24px) 0;
}

/* ─── Section spacing ─── */
.insights-panel__section {
  /* no extra styles needed, just a grouping element */
}
</style>
