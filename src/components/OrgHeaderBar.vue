<template>
  <div class="org-header">
    <div class="org-header__left">
      <span class="org-header__avatar">{{ initials }}</span>
      <div class="org-header__text">
        <h2 class="org-header__title">{{ profile.name }}</h2>
        <span class="org-header__subtitle">
          <span class="org-header__contact">{{ profile.contactEmail }}</span>
          <span class="org-header__divider">·</span>
          <span class="org-header__admin">Admin: {{ profile.adminUid }}</span>
        </span>
      </div>
    </div>
    <div class="org-header__right">
      <span class="org-header__plan-badge" :class="planBadgeClass">
        {{ subscription.planName }}
      </span>
      <span class="org-header__status" :class="'org-header__status--' + subscription.status">
        {{ subscription.status }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  name: "OrgHeaderBar",
  props: {
    orgOverview: {
      type: Object,
      required: true,
    },
  },
  computed: {
    profile: function () {
      return this.orgOverview.profile || { name: "—", contactEmail: "—", adminUid: "—" };
    },
    subscription: function () {
      return this.orgOverview.subscription || {
        status: "none", planName: "No plan", price: 0,
      };
    },
    initials: function () {
      var name = this.profile.name || "?";
      return name.charAt(0).toUpperCase();
    },
    planBadgeClass: function () {
      var p = (this.subscription.planName || "").toLowerCase();
      if (p === "free")       return "org-header__plan-badge--free";
      if (p === "pro")        return "org-header__plan-badge--pro";
      if (p === "enterprise") return "org-header__plan-badge--enterprise";
      return "org-header__plan-badge--custom";
    },
  },
};
</script>

<style scoped>
.org-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,.08));
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  margin-bottom: var(--spacing-xl, 32px);
  border-left: 4px solid #4A90D9;
}

.org-header__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.org-header__avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #4A90D9, #6cb0f0);
  color: #fff;
  font-size: 22px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.org-header__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.org-header__title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
  line-height: 1.2;
}

.org-header__subtitle {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  display: flex;
  align-items: center;
  gap: 6px;
}

.org-header__divider {
  color: var(--color-text-muted, #9ca3af);
}

.org-header__right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Plan badge */
.org-header__plan-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 10px;
}
.org-header__plan-badge--free       { background: #f0f1f5; color: #6b7280; }
.org-header__plan-badge--pro        { background: #e8f0fe; color: #1e4a8a; }
.org-header__plan-badge--enterprise { background: #f3e8ff; color: #6b21a8; }
.org-header__plan-badge--custom     { background: #fef3cd; color: #92400e; }

/* Sub status */
.org-header__status {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 10px;
  text-transform: capitalize;
}
.org-header__status--active    { background: #d4edda; color: #166534; }
.org-header__status--paused    { background: #fef3cd; color: #92400e; }
.org-header__status--cancelled { background: #fde8e8; color: #b91c1c; }
.org-header__status--none      { background: #f0f1f5; color: #6b7280; }

@media (max-width: 700px) {
  .org-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
