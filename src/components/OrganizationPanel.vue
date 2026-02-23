<template>
  <section class="org-panel">
    <div class="org-panel__header" @click="collapsed = !collapsed">
      <h3 class="org-panel__title">
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
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
          <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        Organization
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
        class="org-panel__chevron"
        :class="{ 'org-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="!collapsed" class="org-panel__body">
      <!-- ── Profile Header ── -->
      <div class="org-panel__profile">
        <span class="org-panel__avatar">{{ initials }}</span>
        <div class="org-panel__profile-text">
          <span class="org-panel__name">{{ profile.name }}</span>
          <span class="org-panel__admin">Admin: {{ profile.adminUid }}</span>
        </div>
      </div>

      <!-- ── Contact Details ── -->
      <div class="org-panel__details">
        <div
          class="org-panel__detail-row"
          v-if="profile.contactFirstName || profile.contactLastName"
        >
          <span class="org-panel__detail-label">Contact Name</span>
          <span class="org-panel__detail-value">{{ contactFullName }}</span>
        </div>
        <div class="org-panel__detail-row">
          <span class="org-panel__detail-label">Contact Email</span>
          <span class="org-panel__detail-value">{{
            profile.contactEmail
          }}</span>
        </div>
        <div class="org-panel__detail-row" v-if="profile.contactPhone">
          <span class="org-panel__detail-label">Contact Phone</span>
          <span class="org-panel__detail-value">{{
            profile.contactPhone
          }}</span>
        </div>
        <div class="org-panel__detail-row">
          <span class="org-panel__detail-label">Admin User</span>
          <span class="org-panel__detail-value">{{ profile.adminUid }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "OrganizationPanel",
  props: {
    profile: {
      type: Object,
      default: function () {
        return {
          name: "—",
          contactEmail: "—",
          contactFirstName: "",
          contactLastName: "",
          contactPhone: "",
          adminUid: "—",
        };
      },
    },
  },
  data: function () {
    return {
      collapsed: false,
    };
  },
  computed: {
    initials: function () {
      var name = this.profile.name || "?";
      return name.charAt(0).toUpperCase();
    },
    contactFullName: function () {
      var first = this.profile.contactFirstName || "";
      var last = this.profile.contactLastName || "";
      var full = (first + " " + last).trim();
      return full || "—";
    },
  },
};
</script>

<style scoped>
.org-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.org-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.org-panel__header:hover {
  background: #fafbfd;
}

.org-panel__title {
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

.org-panel__title svg {
  color: #2e9e5a;
}

.org-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.org-panel__chevron--rotated {
  transform: rotate(180deg);
}

.org-panel__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

/* ─── Profile Header ─── */
.org-panel__profile {
  display: flex;
  align-items: center;
  gap: 14px;
  padding-bottom: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-md, 16px);
  border-bottom: 1px solid #f3f4f6;
}

.org-panel__avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #2e9e5a, #5ec489);
  color: #fff;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.org-panel__profile-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.org-panel__name {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.org-panel__admin {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
}

/* ─── Detail Rows ─── */
.org-panel__details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 var(--spacing-lg, 24px);
}

.org-panel__detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
  font-size: 13px;
}

.org-panel__detail-row:last-child {
  border-bottom: none;
}

.org-panel__detail-label {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.org-panel__detail-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  text-align: right;
}

@media (max-width: 700px) {
  .org-panel__details {
    grid-template-columns: 1fr;
  }
}
</style>
