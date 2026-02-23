<template>
  <section class="members-panel">
    <div class="members-panel__header" @click="collapsed = !collapsed">
      <h3 class="members-panel__title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Team Members
        <span class="members-panel__count">{{ members.length }}</span>
      </h3>
      <svg
        xmlns="http://www.w3.org/2000/svg" width="18" height="18"
        viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="members-panel__chevron" :class="{ 'members-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15"/>
      </svg>
    </div>

    <div v-show="!collapsed" class="members-panel__body">
      <div v-if="members.length === 0" class="members-panel__empty">
        No members yet.
      </div>

      <div
        v-for="member in members"
        :key="'mem-' + member.userId"
        class="members-panel__row"
      >
        <span class="members-panel__avatar">{{ member.userId.charAt(0).toUpperCase() }}</span>
        <div class="members-panel__info">
          <span class="members-panel__name">{{ member.userId }}</span>
          <span class="members-panel__joined" v-if="member.joinedAt">
            Joined {{ formatDate(member.joinedAt) }}
          </span>
        </div>
        <span class="members-panel__role" :class="'members-panel__role--' + member.role">
          {{ member.role }}
        </span>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "MembersPanel",
  props: {
    members: {
      type: Array,
      default: function () { return []; },
    },
  },
  data: function () {
    return {
      collapsed: false,
    };
  },
  methods: {
    formatDate: function (dateStr) {
      if (!dateStr) return "—";
      var d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
    },
  },
};
</script>

<style scoped>
.members-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.members-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.members-panel__header:hover {
  background: #fafbfd;
}

.members-panel__title {
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

.members-panel__title svg {
  color: #4A90D9;
}

.members-panel__count {
  font-size: 11px;
  font-weight: 600;
  background: #e8f0fe;
  color: #1e4a8a;
  padding: 2px 8px;
  border-radius: 8px;
}

.members-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.members-panel__chevron--rotated {
  transform: rotate(180deg);
}

.members-panel__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

.members-panel__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 16px 0;
  text-align: center;
}

.members-panel__row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f3f4f6;
}

.members-panel__row:last-child {
  border-bottom: none;
}

.members-panel__avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4A90D9, #6cb0f0);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.members-panel__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.members-panel__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.members-panel__joined {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
}

.members-panel__role {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 8px;
  text-transform: capitalize;
  flex-shrink: 0;
}

.members-panel__role--admin  { background: #e8f0fe; color: #1e4a8a; }
.members-panel__role--owner  { background: #f3e8ff; color: #6b21a8; }
.members-panel__role--member { background: #f0f1f5; color: #6b7280; }
</style>
