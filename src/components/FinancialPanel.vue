<template>
  <section class="fin-panel">
    <!-- HEADER -->
    <div class="fin-panel__header">
      <div class="fin-panel__header-text">
        <h2 class="fin-panel__title">Financial Overview</h2>
      </div>
      <button class="fin-panel__collapse-btn" @click="collapsed = !collapsed">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          :class="{ 'fin-panel__collapse-icon--rotated': collapsed }"
          class="fin-panel__collapse-icon"
        >
          <polyline points="18 15 12 9 6 15" />
        </svg>
      </button>
    </div>

    <div v-show="!collapsed">
      <!-- REVENUE KPI STRIP -->
      <div class="fin-panel__kpi-strip">
        <div class="fin-panel__kpi-card fin-panel__kpi-card--accent">
          <span class="fin-panel__kpi-value"
            >€{{ formatCurrency(revenue.mrr) }}</span
          >
          <span class="fin-panel__kpi-label">Monthly Revenue</span>
        </div>
        <div class="fin-panel__kpi-card">
          <span class="fin-panel__kpi-value"
            >€{{ formatCurrency(revenue.arr) }}</span
          >
          <span class="fin-panel__kpi-label">Annual Revenue</span>
        </div>
        <div class="fin-panel__kpi-card">
          <span class="fin-panel__kpi-value">{{ revenue.totalSubs }}</span>
          <span class="fin-panel__kpi-label">Total Subscriptions</span>
        </div>
        <div class="fin-panel__kpi-card">
          <span class="fin-panel__kpi-value">{{ revenue.paidSubs }}</span>
          <span class="fin-panel__kpi-label">Paid</span>
        </div>
        <div class="fin-panel__kpi-card">
          <span class="fin-panel__kpi-value">{{ revenue.freeSubs }}</span>
          <span class="fin-panel__kpi-label">Free</span>
        </div>
      </div>

      <!-- TOP ROW: Revenue by Plan + Subscription Distribution -->
      <div class="fin-panel__top-grid">
        <!-- Revenue by Plan -->
        <div class="fin-panel__card">
          <h3 class="fin-panel__card-title">Revenue by Plan</h3>
          <div class="fin-panel__card-title-underline"></div>
          <div class="fin-panel__plan-revenue">
            <div
              v-for="(item, idx) in revenue.revenueByPlan"
              :key="'rp-' + idx"
              class="fin-panel__plan-row"
            >
              <div class="fin-panel__plan-info">
                <span class="fin-panel__plan-name">{{ item.plan }}</span>
                <span class="fin-panel__plan-subs"
                  >{{ item.subs }} sub{{ item.subs !== 1 ? "s" : "" }}</span
                >
              </div>
              <div class="fin-panel__plan-amounts">
                <span class="fin-panel__plan-price"
                  >€{{ item.price.toFixed(2) }}/mo</span
                >
                <span class="fin-panel__plan-mrr"
                  >€{{ item.mrr.toFixed(2) }}</span
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Subscription Distribution -->
        <div class="fin-panel__card">
          <h3 class="fin-panel__card-title">Subscription Distribution</h3>
          <div class="fin-panel__card-title-underline"></div>
          <DonutChart
            v-if="hasDistributionData"
            :key="'fin-donut'"
            :chart-data="financialData.subscriptionDistribution"
          />
          <div v-else class="fin-panel__empty">
            <span class="fin-panel__empty-icon">📊</span>
            No active subscriptions yet
          </div>
        </div>
      </div>

      <!-- ORGANIZATIONS TABLE -->
      <div class="fin-panel__card fin-panel__card--full">
        <div class="fin-panel__card-header-row">
          <div>
            <h3 class="fin-panel__card-title">Organizations</h3>
            <div class="fin-panel__card-title-underline"></div>
          </div>
          <span class="fin-panel__org-count"
            >{{ organizations.length }} organization{{
              organizations.length !== 1 ? "s" : ""
            }}</span
          >
        </div>
        <div class="fin-panel__table-wrap">
          <table class="fin-panel__table">
            <thead>
              <tr>
                <th>Organization</th>
                <th>Plan</th>
                <th>Price</th>
                <th>Members</th>
                <th>Projects</th>
                <th>Status</th>
                <th>Started</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="org in organizations"
                :key="'org-' + org.id"
                class="fin-panel__table-row"
                @click="openOrgModal(org)"
              >
                <td>
                  <div class="fin-panel__org-name-cell">
                    <span class="fin-panel__org-avatar">{{
                      org.name.charAt(0).toUpperCase()
                    }}</span>
                    <div>
                      <span class="fin-panel__org-name">{{ org.name }}</span>
                      <span class="fin-panel__org-email">{{
                        org.contactEmail
                      }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="fin-panel__plan-badge"
                    :class="planBadgeClass(org.plan)"
                    >{{ org.plan }}</span
                  >
                </td>
                <td>
                  {{
                    org.planPrice > 0 ? "€" + org.planPrice.toFixed(2) : "Free"
                  }}
                </td>
                <td>
                  <span
                    class="fin-panel__usage"
                    :class="usageClass(org.memberCount, org.maxMembers)"
                  >
                    {{ org.memberCount }}/{{ org.maxMembers }}
                  </span>
                </td>
                <td>
                  <span
                    class="fin-panel__usage"
                    :class="usageClass(org.projectCount, org.maxProjects)"
                  >
                    {{ org.projectCount }}/{{ org.maxProjects }}
                  </span>
                </td>
                <td>
                  <span
                    class="fin-panel__status"
                    :class="'fin-panel__status--' + org.subStatus"
                    >{{ org.subStatus }}</span
                  >
                </td>
                <td class="fin-panel__date">{{ formatDate(org.startedAt) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PLANS COMPARISON TABLE -->
      <div class="fin-panel__card fin-panel__card--full">
        <h3 class="fin-panel__card-title">Subscription Plans</h3>
        <div class="fin-panel__card-title-underline"></div>
        <div class="fin-panel__table-wrap">
          <table class="fin-panel__table fin-panel__table--plans">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Price</th>
                <th>Max Projects</th>
                <th>Max Members</th>
                <th>Shared Storage</th>
                <th>Private Storage</th>
                <th>Visibility</th>
                <th>Active Subs</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plan in plans" :key="'plan-' + plan.id">
                <td>
                  <span
                    class="fin-panel__plan-badge"
                    :class="planBadgeClass(plan.name)"
                    >{{ plan.name }}</span
                  >
                </td>
                <td class="fin-panel__price-cell">
                  {{ plan.price > 0 ? "€" + plan.price.toFixed(2) : "Free"
                  }}<span v-if="plan.price > 0" class="fin-panel__price-period"
                    >/mo</span
                  >
                </td>
                <td>{{ plan.maxProjects }}</td>
                <td>{{ plan.maxMembers }}</td>
                <td>{{ plan.sharedGb }} GB</td>
                <td>{{ plan.privateGb }} GB</td>
                <td>
                  <span
                    class="fin-panel__visibility"
                    :class="
                      plan.isPublic
                        ? 'fin-panel__visibility--public'
                        : 'fin-panel__visibility--private'
                    "
                    >{{ plan.isPublic ? "Public" : "Private" }}</span
                  >
                </td>
                <td>
                  <strong>{{ plan.activeSubs }}</strong>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ORGANIZATION DETAIL MODAL -->
    <div
      v-if="selectedOrg"
      class="fin-modal__backdrop"
      @click.self="selectedOrg = null"
    >
      <div class="fin-modal">
        <div class="fin-modal__header">
          <div class="fin-modal__header-left">
            <span class="fin-modal__avatar">{{
              selectedOrg.name.charAt(0).toUpperCase()
            }}</span>
            <div>
              <h3 class="fin-modal__title">{{ selectedOrg.name }}</h3>
              <span class="fin-modal__subtitle">{{
                selectedOrg.contactEmail
              }}</span>
            </div>
          </div>
          <button class="fin-modal__close" @click="selectedOrg = null">
            &times;
          </button>
        </div>
        <div class="fin-modal__body">
          <div class="fin-modal__grid">
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Plan</span>
              <span
                class="fin-panel__plan-badge"
                :class="planBadgeClass(selectedOrg.plan)"
                >{{ selectedOrg.plan }}</span
              >
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Price</span>
              <span class="fin-modal__detail-value">{{
                selectedOrg.planPrice > 0
                  ? "€" + selectedOrg.planPrice.toFixed(2) + "/mo"
                  : "Free"
              }}</span>
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Status</span>
              <span
                class="fin-panel__status"
                :class="'fin-panel__status--' + selectedOrg.subStatus"
                >{{ selectedOrg.subStatus }}</span
              >
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Admin</span>
              <span class="fin-modal__detail-value">{{
                selectedOrg.adminUid
              }}</span>
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Members</span>
              <span class="fin-modal__detail-value">
                {{ selectedOrg.memberCount }} / {{ selectedOrg.maxMembers }}
                <span class="fin-panel__usage-bar">
                  <span
                    class="fin-panel__usage-fill"
                    :style="{
                      width:
                        Math.min(
                          100,
                          (selectedOrg.memberCount / selectedOrg.maxMembers) *
                            100,
                        ) + '%',
                    }"
                    :class="
                      usageClass(
                        selectedOrg.memberCount,
                        selectedOrg.maxMembers,
                      )
                    "
                  ></span>
                </span>
              </span>
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Projects</span>
              <span class="fin-modal__detail-value">
                {{ selectedOrg.projectCount }} / {{ selectedOrg.maxProjects }}
                <span class="fin-panel__usage-bar">
                  <span
                    class="fin-panel__usage-fill"
                    :style="{
                      width:
                        Math.min(
                          100,
                          (selectedOrg.projectCount / selectedOrg.maxProjects) *
                            100,
                        ) + '%',
                    }"
                    :class="
                      usageClass(
                        selectedOrg.projectCount,
                        selectedOrg.maxProjects,
                      )
                    "
                  ></span>
                </span>
              </span>
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Shared Storage</span>
              <span class="fin-modal__detail-value"
                >{{ selectedOrg.sharedStorageGb }} GB / project</span
              >
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Private Storage</span>
              <span class="fin-modal__detail-value"
                >{{ selectedOrg.privateStorageGb }} GB / user</span
              >
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Started</span>
              <span class="fin-modal__detail-value">{{
                formatDate(selectedOrg.startedAt)
              }}</span>
            </div>
            <div class="fin-modal__detail">
              <span class="fin-modal__detail-label">Expires</span>
              <span class="fin-modal__detail-value">{{
                selectedOrg.endedAt === "—"
                  ? "No expiry"
                  : formatDate(selectedOrg.endedAt)
              }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import DonutChart from "./DonutChart.vue";

export default {
  name: "FinancialPanel",
  components: {
    DonutChart,
  },
  props: {
    financialData: {
      type: Object,
      required: true,
    },
  },
  data: function () {
    return {
      collapsed: false,
      selectedOrg: null,
    };
  },
  computed: {
    revenue: function () {
      return (
        this.financialData.revenueOverview || {
          mrr: 0,
          arr: 0,
          potentialArr: 0,
          paidSubs: 0,
          freeSubs: 0,
          totalSubs: 0,
          revenueByPlan: [],
        }
      );
    },
    organizations: function () {
      return this.financialData.organizations || [];
    },
    plans: function () {
      return this.financialData.plans || [];
    },
    hasDistributionData: function () {
      var dist = this.financialData.subscriptionDistribution;
      if (!dist || !dist.data) return false;
      return dist.data.some(function (v) {
        return v > 0;
      });
    },
  },
  methods: {
    formatCurrency: function (amount) {
      if (amount >= 1000) {
        return (amount / 1000).toFixed(1) + "K";
      }
      return amount.toFixed(2);
    },
    formatDate: function (dateStr) {
      if (!dateStr || dateStr === "—") return "—";
      var d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    },
    planBadgeClass: function (planName) {
      if (!planName) return "";
      var lower = planName.toLowerCase();
      if (lower === "free") return "fin-panel__plan-badge--free";
      if (lower === "pro") return "fin-panel__plan-badge--pro";
      if (lower === "enterprise") return "fin-panel__plan-badge--enterprise";
      return "fin-panel__plan-badge--custom";
    },
    usageClass: function (used, max) {
      if (max === 0) return "";
      var pct = (used / max) * 100;
      if (pct >= 90) return "fin-panel__usage--danger";
      if (pct >= 70) return "fin-panel__usage--warning";
      return "fin-panel__usage--ok";
    },
    openOrgModal: function (org) {
      this.selectedOrg = org;
      document.body.style.overflow = "hidden";
    },
  },
  watch: {
    selectedOrg: function (val) {
      if (!val) {
        document.body.style.overflow = "";
      }
    },
  },
};
</script>

<style scoped>
.fin-panel {
  margin-bottom: var(--spacing-xl, 32px);
}

/* ─── Header ─── */
.fin-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg, 24px);
  background: #fcfdff;
  border: 1px solid #eef1f5;
  border-radius: var(--radius-card, 12px);
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
}

.fin-panel__title {
  font-size: 22px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
}

.fin-panel__collapse-btn {
  background: var(--bg-card, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-secondary, #6b7280);
  transition: all 0.2s ease;
  flex-shrink: 0;
  margin-top: 4px;
}

.fin-panel__collapse-btn:hover {
  background-color: var(--bg-page, #f5f6fa);
}

.fin-panel__collapse-icon {
  transition: transform 0.3s ease;
}

.fin-panel__collapse-icon--rotated {
  transform: rotate(180deg);
}

/* ─── KPI Strip ─── */
.fin-panel__kpi-strip {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 16px);
}

.fin-panel__kpi-card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-md, 16px);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  border-top: 3px solid transparent;
}

.fin-panel__kpi-card--accent {
  border-top-color: #2e9e5a;
}

.fin-panel__kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1.1;
}

.fin-panel__kpi-card--accent .fin-panel__kpi-value {
  color: #2e9e5a;
}

.fin-panel__kpi-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
  text-align: center;
}

/* ─── Top Grid ─── */
.fin-panel__top-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-md, 16px);
}

/* ─── Card ─── */
.fin-panel__card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-lg, 24px);
  transition: box-shadow 0.2s ease;
}

.fin-panel__card:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.fin-panel__card--full {
  margin-bottom: var(--spacing-md, 16px);
}

.fin-panel__card-header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.fin-panel__card-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0 0 var(--spacing-xs, 4px) 0;
  line-height: 1.35;
  padding: 0;
  border: none;
}

.fin-panel__card-title-underline {
  width: 36px;
  height: 3px;
  background-color: #2e9e5a;
  border-radius: 2px;
  margin-bottom: var(--spacing-lg, 24px);
}

.fin-panel__org-count {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-secondary, #6b7280);
  background: #f0f1f5;
  padding: 4px 10px;
  border-radius: 12px;
}

/* ─── Revenue by Plan ─── */
.fin-panel__plan-revenue {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.fin-panel__plan-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f3f4f6;
}

.fin-panel__plan-row:last-child {
  border-bottom: none;
}

.fin-panel__plan-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.fin-panel__plan-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.fin-panel__plan-subs {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  background: #f5f6fa;
  padding: 2px 6px;
  border-radius: 4px;
}

.fin-panel__plan-amounts {
  display: flex;
  align-items: center;
  gap: 16px;
}

.fin-panel__plan-price {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
}

.fin-panel__plan-mrr {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  min-width: 60px;
  text-align: right;
}

/* ─── Table ─── */
.fin-panel__table-wrap {
  overflow-x: auto;
}

.fin-panel__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.fin-panel__table th {
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 10px 12px;
  border-bottom: 2px solid #eef1f5;
  white-space: nowrap;
}

.fin-panel__table td {
  padding: 12px;
  color: var(--color-text-primary, #1a1a2e);
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.fin-panel__table-row {
  cursor: pointer;
  transition: background 0.15s;
}

.fin-panel__table-row:hover {
  background: #fafbfd;
}

.fin-panel__table tbody tr:last-child td {
  border-bottom: none;
}

/* Org name cell */
.fin-panel__org-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.fin-panel__org-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #4a90d9, #6cb0f0);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fin-panel__org-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  display: block;
}

.fin-panel__org-email {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  display: block;
}

/* Plan badges */
.fin-panel__plan-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 10px;
  white-space: nowrap;
}

.fin-panel__plan-badge--free {
  background: #f0f1f5;
  color: #6b7280;
}

.fin-panel__plan-badge--pro {
  background: #e8f0fe;
  color: #1e4a8a;
}

.fin-panel__plan-badge--enterprise {
  background: #f3e8ff;
  color: #6b21a8;
}

.fin-panel__plan-badge--custom {
  background: #fef3cd;
  color: #92400e;
}

/* Status */
.fin-panel__status {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 10px;
  white-space: nowrap;
  text-transform: capitalize;
}

.fin-panel__status--active {
  background: #d4edda;
  color: #166534;
}

.fin-panel__status--paused {
  background: #fef3cd;
  color: #92400e;
}

.fin-panel__status--cancelled {
  background: #fde8e8;
  color: #b91c1c;
}

.fin-panel__status--none {
  background: #f0f1f5;
  color: #6b7280;
}

/* Usage indicators */
.fin-panel__usage {
  font-size: 12px;
  font-weight: 600;
}

.fin-panel__usage--ok {
  color: #166534;
}

.fin-panel__usage--warning {
  color: #92400e;
}

.fin-panel__usage--danger {
  color: #b91c1c;
}

.fin-panel__usage-bar {
  display: inline-block;
  width: 60px;
  height: 4px;
  background: #f0f1f5;
  border-radius: 2px;
  margin-left: 8px;
  vertical-align: middle;
}

.fin-panel__usage-fill {
  display: block;
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s ease;
  background: #2e9e5a;
}

.fin-panel__usage-fill.fin-panel__usage--warning {
  background: #f4a261;
}

.fin-panel__usage-fill.fin-panel__usage--danger {
  background: #e63946;
}

/* Visibility */
.fin-panel__visibility {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 10px;
}

.fin-panel__visibility--public {
  background: #d4edda;
  color: #166534;
}

.fin-panel__visibility--private {
  background: #f0f1f5;
  color: #6b7280;
}

/* Price cell */
.fin-panel__price-cell {
  font-weight: 600;
}

.fin-panel__price-period {
  font-size: 11px;
  font-weight: 400;
  color: var(--color-text-muted, #9ca3af);
}

/* Date */
.fin-panel__date {
  white-space: nowrap;
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

/* Empty */
.fin-panel__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  color: #9ca3af;
  font-size: 14px;
  gap: 8px;
}

.fin-panel__empty-icon {
  font-size: 32px;
}

/* ═══ Organization Modal ═══ */
.fin-modal__backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  animation: fin-fade-in 0.15s ease;
}

@keyframes fin-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.fin-modal {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 560px;
  animation: fin-slide-up 0.2s ease;
}

@keyframes fin-slide-up {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fin-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #eef1f5;
}

.fin-modal__header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.fin-modal__avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4a90d9, #6cb0f0);
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fin-modal__title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
  padding: 0;
  border: none;
}

.fin-modal__subtitle {
  font-size: 12px;
  color: #6b7280;
}

.fin-modal__close {
  background: none;
  border: none;
  font-size: 24px;
  color: #6b7280;
  cursor: pointer;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  flex-shrink: 0;
}

.fin-modal__close:hover {
  background: #f0f1f5;
  color: #1a1a2e;
}

.fin-modal__body {
  padding: 20px 24px 24px;
}

.fin-modal__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.fin-modal__detail {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fin-modal__detail-label {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.fin-modal__detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a2e;
  display: flex;
  align-items: center;
}

/* ─── Responsive ─── */
@media (max-width: 1024px) {
  .fin-panel__kpi-strip {
    grid-template-columns: repeat(3, 1fr);
  }

  .fin-panel__top-grid {
    grid-template-columns: 1fr;
  }

  .fin-modal__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .fin-panel__kpi-strip {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
