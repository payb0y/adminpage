<template>
  <component
    :is="embedded ? 'div' : 'section'"
    :class="['sub-panel', { 'sub-panel--embedded': embedded }]"
  >
    <div
      v-if="!embedded"
      class="sub-panel__header"
      @click="collapsed = !collapsed"
    >
      <h3 class="sub-panel__title">
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
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
          <line x1="1" y1="10" x2="23" y2="10" />
        </svg>
        Subscription &amp; Plan
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
        class="sub-panel__chevron"
        :class="{ 'sub-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="embedded || !collapsed" class="sub-panel__body">
      <!-- ── Usage Meters ── -->
      <div class="sub-panel__meters">
        <!-- Projects -->
        <div class="sub-panel__meter">
          <div class="sub-panel__meter-header">
            <span class="sub-panel__meter-label">Projects</span>
            <span class="sub-panel__meter-value"
              >{{ usageSummary.projectCount
              }}<span class="sub-panel__meter-max"
                >/{{ subscription.maxProjects }}</span
              ></span
            >
          </div>
          <div class="sub-panel__bar">
            <div
              class="sub-panel__bar-fill"
              :style="
                fillStyle(usageSummary.projectCount, subscription.maxProjects)
              "
              :class="
                fillClass(usageSummary.projectCount, subscription.maxProjects)
              "
            ></div>
          </div>
        </div>

        <!-- Members -->
        <div class="sub-panel__meter">
          <div class="sub-panel__meter-header">
            <span class="sub-panel__meter-label">Members</span>
            <span class="sub-panel__meter-value"
              >{{ usageSummary.memberCount
              }}<span class="sub-panel__meter-max"
                >/{{ subscription.maxMembers }}</span
              ></span
            >
          </div>
          <div class="sub-panel__bar">
            <div
              class="sub-panel__bar-fill"
              :style="
                fillStyle(usageSummary.memberCount, subscription.maxMembers)
              "
              :class="
                fillClass(usageSummary.memberCount, subscription.maxMembers)
              "
            ></div>
          </div>
        </div>

        <!-- Tasks Done -->
        <div class="sub-panel__meter">
          <div class="sub-panel__meter-header">
            <span class="sub-panel__meter-label">Tasks Done</span>
            <span class="sub-panel__meter-value"
              >{{ usageSummary.doneTasks
              }}<span class="sub-panel__meter-max"
                >/{{ usageSummary.totalTasks }}</span
              ></span
            >
          </div>
          <div class="sub-panel__bar">
            <div
              class="sub-panel__bar-fill sub-panel__bar-fill--ok"
              :style="
                fillStyle(usageSummary.doneTasks, usageSummary.totalTasks)
              "
            ></div>
          </div>
        </div>
      </div>

      <!-- ── Plan Detail Grid ── -->
      <div class="sub-panel__details">
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Plan</span>
          <span class="sub-panel__plan-badge" :class="planBadgeClass">{{
            subscription.planName
          }}</span>
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Status</span>
          <span
            class="sub-panel__status"
            :class="'sub-panel__status--' + subscription.status"
            >{{ subscription.status }}</span
          >
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Visibility</span>
          <span class="sub-panel__detail-value">{{
            subscription.isPublic ? "Public" : "Private"
          }}</span>
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Max Projects</span>
          <span class="sub-panel__detail-value">{{
            subscription.maxProjects
          }}</span>
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Max Members</span>
          <span class="sub-panel__detail-value">{{
            subscription.maxMembers
          }}</span>
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Shared Storage</span>
          <span class="sub-panel__detail-value"
            >{{ subscription.sharedStorageGb }} GB / project</span
          >
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Private Storage</span>
          <span class="sub-panel__detail-value"
            >{{ subscription.privateStorageGb }} GB / user</span
          >
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Started</span>
          <span class="sub-panel__detail-value">{{
            formatDate(subscription.startedAt)
          }}</span>
        </div>
        <div class="sub-panel__detail-row">
          <span class="sub-panel__detail-label">Expires</span>
          <span class="sub-panel__detail-value">{{
            subscription.endedAt
              ? formatDate(subscription.endedAt)
              : "No expiry"
          }}</span>
        </div>
      </div>
    </div>
  </component>
</template>

<script>
export default {
  name: "SubscriptionPanel",
  props: {
    embedded: {
      type: Boolean,
      default: false,
    },
    subscription: {
      type: Object,
      default: function () {
        return {
          status: "none",
          planName: "No plan",
          price: 0,
          maxProjects: 0,
          maxMembers: 0,
          sharedStorageGb: 0,
          privateStorageGb: 0,
          startedAt: null,
          endedAt: null,
          isPublic: false,
        };
      },
    },
    usageSummary: {
      type: Object,
      default: function () {
        return { memberCount: 0, projectCount: 0, totalTasks: 0, doneTasks: 0 };
      },
    },
  },
  data: function () {
    return {
      collapsed: false,
    };
  },
  computed: {
    priceDisplay: function () {
      var p = this.subscription.price;
      if (!p || p === 0) return "Free";
      return "€" + Number(p).toFixed(2) + "/mo";
    },
    planBadgeClass: function () {
      var p = (this.subscription.planName || "").toLowerCase();
      if (p === "free") return "sub-panel__plan-badge--free";
      if (p === "pro") return "sub-panel__plan-badge--pro";
      if (p === "enterprise") return "sub-panel__plan-badge--enterprise";
      return "sub-panel__plan-badge--custom";
    },
  },
  methods: {
    formatDate: function (dateStr) {
      if (!dateStr) return "—";
      var d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    },
    fillStyle: function (used, max) {
      if (!max || max === 0) return { width: "0%" };
      return { width: Math.min(100, (used / max) * 100) + "%" };
    },
    fillClass: function (used, max) {
      if (!max || max === 0) return "sub-panel__bar-fill--ok";
      var pct = (used / max) * 100;
      if (pct >= 90) return "sub-panel__bar-fill--danger";
      if (pct >= 70) return "sub-panel__bar-fill--warning";
      return "sub-panel__bar-fill--ok";
    },
  },
};
</script>

<style scoped>
.sub-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.sub-panel--embedded {
  background: none;
  border-radius: 0;
  box-shadow: none;
  margin-bottom: 0;
  overflow: visible;
}

.sub-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.sub-panel__header:hover {
  background: #fafbfd;
}

.sub-panel__title {
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

.sub-panel__title svg {
  color: #d97706;
}

.sub-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.sub-panel__chevron--rotated {
  transform: rotate(180deg);
}

.sub-panel__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

/* ─── Usage Meters ─── */
.sub-panel__meters {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-lg, 24px);
}

.sub-panel__meter {
  background: #fafbfd;
  border-radius: 8px;
  padding: 12px 14px;
}

.sub-panel__meter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.sub-panel__meter-label {
  font-size: 11px;
  font-weight: 500;
  color: var(--color-text-secondary, #6b7280);
}

.sub-panel__meter-value {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.sub-panel__meter-max {
  font-size: 12px;
  font-weight: 400;
  color: var(--color-text-muted, #9ca3af);
}

.sub-panel__bar {
  height: 5px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.sub-panel__bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.sub-panel__bar-fill--ok {
  background: #2e9e5a;
}
.sub-panel__bar-fill--warning {
  background: #f4a261;
}
.sub-panel__bar-fill--danger {
  background: #e63946;
}

/* ─── Plan Details ─── */
.sub-panel__details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 var(--spacing-lg, 24px);
}

.sub-panel__detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
  font-size: 13px;
}

.sub-panel__detail-row:last-child {
  border-bottom: none;
}

.sub-panel__detail-label {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.sub-panel__detail-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  text-align: right;
}

/* Plan badge */
.sub-panel__plan-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 8px;
}

.sub-panel__plan-badge--free {
  background: #f0f1f5;
  color: #6b7280;
}
.sub-panel__plan-badge--pro {
  background: #e8f0fe;
  color: #1e4a8a;
}
.sub-panel__plan-badge--enterprise {
  background: #f3e8ff;
  color: #6b21a8;
}
.sub-panel__plan-badge--custom {
  background: #fef3cd;
  color: #92400e;
}

/* Status */
.sub-panel__status {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 8px;
  text-transform: capitalize;
}

.sub-panel__status--active {
  background: #d4edda;
  color: #166534;
}
.sub-panel__status--paused {
  background: #fef3cd;
  color: #92400e;
}
.sub-panel__status--cancelled {
  background: #fde8e8;
  color: #b91c1c;
}
.sub-panel__status--none {
  background: #f0f1f5;
  color: #6b7280;
}

@media (max-width: 700px) {
  .sub-panel__meters {
    grid-template-columns: 1fr;
  }
  .sub-panel__details {
    grid-template-columns: 1fr;
  }
}
</style>
