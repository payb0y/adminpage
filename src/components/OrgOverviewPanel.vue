<template>
  <section class="org-panel">
    <!-- ── Header ── -->
    <div class="org-panel__header">
      <div class="org-panel__header-left">
        <span class="org-panel__avatar">{{ initials }}</span>
        <div>
          <h2 class="org-panel__title">{{ profile.name }}</h2>
          <span class="org-panel__subtitle">Your Organization Overview</span>
        </div>
      </div>
      <div class="org-panel__header-right">
        <span class="org-panel__plan-badge" :class="planBadgeClass">
          {{ subscription.planName }}
        </span>
        <span class="org-panel__sub-status" :class="'org-panel__sub-status--' + subscription.status">
          {{ subscription.status }}
        </span>
        <button class="org-panel__collapse-btn" @click="collapsed = !collapsed">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20" height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            :class="{ 'org-panel__chevron--rotated': collapsed }"
            class="org-panel__chevron"
          >
            <polyline points="18 15 12 9 6 15" />
          </svg>
        </button>
      </div>
    </div>

    <div v-show="!collapsed">
      <!-- ── Summary Strip ── -->
      <div class="org-panel__summary-strip">
        <!-- Projects -->
        <div class="org-panel__stat-card">
          <div class="org-panel__stat-icon org-panel__stat-icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
          </div>
          <div>
            <span class="org-panel__stat-value">{{ usageSummary.projectCount }}<span class="org-panel__stat-max">/{{ subscription.maxProjects }}</span></span>
            <span class="org-panel__stat-label">Projects</span>
            <div class="org-panel__usage-bar">
              <div class="org-panel__usage-fill" :style="usageFillStyle(usageSummary.projectCount, subscription.maxProjects)" :class="usageFillClass(usageSummary.projectCount, subscription.maxProjects)"></div>
            </div>
          </div>
        </div>

        <!-- Members -->
        <div class="org-panel__stat-card">
          <div class="org-panel__stat-icon org-panel__stat-icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div>
            <span class="org-panel__stat-value">{{ usageSummary.memberCount }}<span class="org-panel__stat-max">/{{ subscription.maxMembers }}</span></span>
            <span class="org-panel__stat-label">Members</span>
            <div class="org-panel__usage-bar">
              <div class="org-panel__usage-fill" :style="usageFillStyle(usageSummary.memberCount, subscription.maxMembers)" :class="usageFillClass(usageSummary.memberCount, subscription.maxMembers)"></div>
            </div>
          </div>
        </div>

        <!-- Tasks -->
        <div class="org-panel__stat-card">
          <div class="org-panel__stat-icon org-panel__stat-icon--purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
          </div>
          <div>
            <span class="org-panel__stat-value">{{ usageSummary.doneTasks }}<span class="org-panel__stat-max">/{{ usageSummary.totalTasks }}</span></span>
            <span class="org-panel__stat-label">Tasks Done</span>
            <div class="org-panel__usage-bar">
              <div class="org-panel__usage-fill org-panel__usage-fill--ok" :style="usageFillStyle(usageSummary.doneTasks, usageSummary.totalTasks)"></div>
            </div>
          </div>
        </div>

        <!-- Plan / Billing -->
        <div class="org-panel__stat-card">
          <div class="org-panel__stat-icon org-panel__stat-icon--orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
          </div>
          <div>
            <span class="org-panel__stat-value">{{ subscription.price > 0 ? '€' + subscription.price.toFixed(2) : 'Free' }}</span>
            <span class="org-panel__stat-label">{{ subscription.price > 0 ? 'per month' : 'Current Plan' }}</span>
            <span class="org-panel__stat-sub">{{ subscription.price > 0 ? '€' + (subscription.price * 12).toFixed(2) + '/yr' : 'Upgrade available' }}</span>
          </div>
        </div>

        <!-- Storage -->
        <div class="org-panel__stat-card">
          <div class="org-panel__stat-icon org-panel__stat-icon--teal">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
          </div>
          <div>
            <span class="org-panel__stat-value">{{ subscription.sharedStorageGb }} GB</span>
            <span class="org-panel__stat-label">Shared / project</span>
            <span class="org-panel__stat-sub">{{ subscription.privateStorageGb }} GB private/user</span>
          </div>
        </div>
      </div>

      <!-- ── Bottom Grid: Projects + Members + Plan Details ── -->
      <div class="org-panel__grid">

        <!-- Projects List -->
        <div class="org-panel__card org-panel__card--projects">
          <h3 class="org-panel__card-title">Projects</h3>
          <div class="org-panel__card-underline"></div>

          <div v-if="projects.length === 0" class="org-panel__empty">
            No projects yet.
          </div>

          <div
            v-for="project in projects"
            :key="'proj-' + project.id"
            class="org-panel__project-row"
            @click="toggleProject(project.id)"
          >
            <div class="org-panel__project-main">
              <div class="org-panel__project-info">
                <span class="org-panel__project-name">{{ project.name }}</span>
                <span class="org-panel__project-meta">
                  {{ project.done }}/{{ project.total }} tasks done
                  <span v-if="project.overdue > 0" class="org-panel__overdue-badge">{{ project.overdue }} overdue</span>
                </span>
              </div>
              <div class="org-panel__project-right">
                <span class="org-panel__progress-label">{{ project.progress }}%</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14" height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  :class="{ 'org-panel__row-chevron--open': expandedProjects[project.id] }"
                  class="org-panel__row-chevron"
                >
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="org-panel__proj-bar">
              <div
                class="org-panel__proj-fill"
                :style="{ width: project.progress + '%' }"
                :class="progressClass(project.progress)"
              ></div>
            </div>

            <!-- Expandable stacks breakdown -->
            <div v-if="expandedProjects[project.id] && project.stacks.length" class="org-panel__stacks">
              <div
                v-for="(stack, si) in project.stacks"
                :key="'stack-' + si"
                class="org-panel__stack-chip"
              >
                <span class="org-panel__stack-name">{{ stack.title }}</span>
                <span class="org-panel__stack-count">{{ stack.count }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Members List -->
        <div class="org-panel__card">
          <h3 class="org-panel__card-title">Members</h3>
          <div class="org-panel__card-underline"></div>

          <div v-if="members.length === 0" class="org-panel__empty">
            No members yet.
          </div>

          <div
            v-for="member in members"
            :key="'mem-' + member.userId"
            class="org-panel__member-row"
          >
            <span class="org-panel__member-avatar">{{ member.userId.charAt(0).toUpperCase() }}</span>
            <div class="org-panel__member-info">
              <span class="org-panel__member-name">{{ member.userId }}</span>
              <span class="org-panel__member-role" :class="'org-panel__member-role--' + member.role">{{ member.role }}</span>
            </div>
          </div>
        </div>

        <!-- Plan Details -->
        <div class="org-panel__card">
          <h3 class="org-panel__card-title">Plan Details</h3>
          <div class="org-panel__card-underline"></div>

          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Plan</span>
            <span class="org-panel__plan-badge" :class="planBadgeClass">{{ subscription.planName }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Price</span>
            <span class="org-panel__plan-detail-value">{{ subscription.price > 0 ? '€' + subscription.price.toFixed(2) + '/mo' : 'Free' }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Status</span>
            <span class="org-panel__sub-status" :class="'org-panel__sub-status--' + subscription.status">{{ subscription.status }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Visibility</span>
            <span class="org-panel__plan-detail-value">{{ subscription.isPublic ? 'Public' : 'Private' }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Max Projects</span>
            <span class="org-panel__plan-detail-value">{{ subscription.maxProjects }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Max Members</span>
            <span class="org-panel__plan-detail-value">{{ subscription.maxMembers }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Shared Storage</span>
            <span class="org-panel__plan-detail-value">{{ subscription.sharedStorageGb }} GB / project</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Private Storage</span>
            <span class="org-panel__plan-detail-value">{{ subscription.privateStorageGb }} GB / user</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Started</span>
            <span class="org-panel__plan-detail-value">{{ formatDate(subscription.startedAt) }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Expires</span>
            <span class="org-panel__plan-detail-value">{{ subscription.endedAt ? formatDate(subscription.endedAt) : 'No expiry' }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Contact</span>
            <span class="org-panel__plan-detail-value">{{ profile.contactEmail }}</span>
          </div>
          <div class="org-panel__plan-detail-row">
            <span class="org-panel__plan-detail-label">Admin User</span>
            <span class="org-panel__plan-detail-value">{{ profile.adminUid }}</span>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "OrgOverviewPanel",
  props: {
    orgOverview: {
      type: Object,
      required: true,
    },
  },
  data: function () {
    return {
      collapsed: false,
      expandedProjects: {},
    };
  },
  computed: {
    profile: function () {
      return this.orgOverview.profile || { name: "—", contactEmail: "—", adminUid: "—" };
    },
    subscription: function () {
      return this.orgOverview.subscription || {
        status: "none", planName: "No plan", price: 0,
        maxProjects: 0, maxMembers: 0,
        sharedStorageGb: 0, privateStorageGb: 0,
        startedAt: null, endedAt: null, isPublic: false,
      };
    },
    members: function () {
      return this.orgOverview.members || [];
    },
    projects: function () {
      return this.orgOverview.projects || [];
    },
    usageSummary: function () {
      return this.orgOverview.usageSummary || {
        memberCount: 0, projectCount: 0, totalTasks: 0, doneTasks: 0,
      };
    },
    initials: function () {
      var name = this.profile.name || "?";
      return name.charAt(0).toUpperCase();
    },
    planBadgeClass: function () {
      var p = (this.subscription.planName || "").toLowerCase();
      if (p === "free")       return "org-panel__plan-badge--free";
      if (p === "pro")        return "org-panel__plan-badge--pro";
      if (p === "enterprise") return "org-panel__plan-badge--enterprise";
      return "org-panel__plan-badge--custom";
    },
  },
  methods: {
    toggleProject: function (id) {
      this.$set(this.expandedProjects, id, !this.expandedProjects[id]);
    },
    formatDate: function (dateStr) {
      if (!dateStr) return "—";
      var d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
    },
    usageFillStyle: function (used, max) {
      if (!max || max === 0) return { width: "0%" };
      return { width: Math.min(100, (used / max) * 100) + "%" };
    },
    usageFillClass: function (used, max) {
      if (!max || max === 0) return "org-panel__usage-fill--ok";
      var pct = (used / max) * 100;
      if (pct >= 90) return "org-panel__usage-fill--danger";
      if (pct >= 70) return "org-panel__usage-fill--warning";
      return "org-panel__usage-fill--ok";
    },
    progressClass: function (pct) {
      if (pct >= 80) return "org-panel__proj-fill--high";
      if (pct >= 40) return "org-panel__proj-fill--mid";
      return "org-panel__proj-fill--low";
    },
  },
};
</script>

<style scoped>
.org-panel {
  margin-bottom: var(--spacing-xl, 32px);
}

/* ─── Header ─── */
.org-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,.08));
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  margin-bottom: var(--spacing-md, 16px);
  border-left: 4px solid #4A90D9;
}

.org-panel__header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.org-panel__avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #4A90D9, #6cb0f0);
  color: #fff;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.org-panel__title {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
  line-height: 1.2;
}

.org-panel__subtitle {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  display: block;
  margin-top: 2px;
}

.org-panel__header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Plan badge */
.org-panel__plan-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 10px;
}

.org-panel__plan-badge--free       { background: #f0f1f5; color: #6b7280; }
.org-panel__plan-badge--pro        { background: #e8f0fe; color: #1e4a8a; }
.org-panel__plan-badge--enterprise { background: #f3e8ff; color: #6b21a8; }
.org-panel__plan-badge--custom     { background: #fef3cd; color: #92400e; }

/* Sub status */
.org-panel__sub-status {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 10px;
  text-transform: capitalize;
}

.org-panel__sub-status--active    { background: #d4edda; color: #166534; }
.org-panel__sub-status--paused    { background: #fef3cd; color: #92400e; }
.org-panel__sub-status--cancelled { background: #fde8e8; color: #b91c1c; }
.org-panel__sub-status--none      { background: #f0f1f5; color: #6b7280; }

/* Collapse button */
.org-panel__collapse-btn {
  background: #f0f1f5;
  border: none;
  border-radius: 50%;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-secondary, #6b7280);
  transition: background 0.2s;
}

.org-panel__collapse-btn:hover { background: #e5e7eb; }

.org-panel__chevron { transition: transform 0.3s; }
.org-panel__chevron--rotated { transform: rotate(180deg); }

/* ─── Summary Strip ─── */
.org-panel__summary-strip {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 16px);
}

.org-panel__stat-card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,.08));
  padding: var(--spacing-md, 16px);
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.org-panel__stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.org-panel__stat-icon--blue   { background: #e8f0fe; color: #4A90D9; }
.org-panel__stat-icon--green  { background: #d4edda; color: #2E9E5A; }
.org-panel__stat-icon--purple { background: #f3e8ff; color: #9333ea; }
.org-panel__stat-icon--orange { background: #fef3cd; color: #d97706; }
.org-panel__stat-icon--teal   { background: #ccfbf1; color: #0d9488; }

.org-panel__stat-card > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.org-panel__stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1.1;
}

.org-panel__stat-max {
  font-size: 13px;
  font-weight: 400;
  color: var(--color-text-muted, #9ca3af);
}

.org-panel__stat-label {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.org-panel__stat-sub {
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
}

/* Usage bar (inside stat card) */
.org-panel__usage-bar {
  height: 4px;
  background: #f0f1f5;
  border-radius: 2px;
  margin-top: 4px;
  width: 100%;
  overflow: hidden;
}

.org-panel__usage-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease;
}

.org-panel__usage-fill--ok      { background: #2E9E5A; }
.org-panel__usage-fill--warning { background: #f4a261; }
.org-panel__usage-fill--danger  { background: #e63946; }

/* ─── Bottom Grid ─── */
.org-panel__grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: var(--spacing-md, 16px);
}

/* ─── Cards ─── */
.org-panel__card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,.08));
  padding: var(--spacing-lg, 24px);
}

.org-panel__card-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0 0 4px 0;
  padding: 0;
  border: none;
}

.org-panel__card-underline {
  width: 32px;
  height: 3px;
  background: #4A90D9;
  border-radius: 2px;
  margin-bottom: var(--spacing-lg, 24px);
}

.org-panel__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 16px 0;
  text-align: center;
}

/* ─── Project rows ─── */
.org-panel__project-row {
  padding: 10px 0;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.15s;
  border-radius: 4px;
}

.org-panel__project-row:last-child { border-bottom: none; }
.org-panel__project-row:hover { background: #fafbfd; }

.org-panel__project-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.org-panel__project-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.org-panel__project-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.org-panel__project-meta {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  display: flex;
  align-items: center;
  gap: 6px;
}

.org-panel__overdue-badge {
  background: #fde8e8;
  color: #b91c1c;
  font-size: 10px;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 8px;
}

.org-panel__project-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.org-panel__progress-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.org-panel__row-chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.25s;
}

.org-panel__row-chevron--open { transform: rotate(180deg); }

/* Progress bar (project) */
.org-panel__proj-bar {
  height: 5px;
  background: #f0f1f5;
  border-radius: 3px;
  overflow: hidden;
}

.org-panel__proj-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.org-panel__proj-fill--high { background: #2E9E5A; }
.org-panel__proj-fill--mid  { background: #4A90D9; }
.org-panel__proj-fill--low  { background: #f4a261; }

/* Stacks breakdown */
.org-panel__stacks {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
  padding: 6px 0 2px;
}

.org-panel__stack-chip {
  display: flex;
  align-items: center;
  gap: 5px;
  background: #f5f6fa;
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
}

.org-panel__stack-name { font-weight: 500; }
.org-panel__stack-count {
  background: #e5e7eb;
  border-radius: 4px;
  padding: 0 5px;
  font-weight: 700;
  font-size: 10px;
  color: var(--color-text-primary, #1a1a2e);
}

/* ─── Member rows ─── */
.org-panel__member-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.org-panel__member-row:last-child { border-bottom: none; }

.org-panel__member-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #4A90D9, #6cb0f0);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.org-panel__member-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.org-panel__member-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.org-panel__member-role {
  font-size: 10px;
  font-weight: 600;
  padding: 1px 7px;
  border-radius: 8px;
  width: fit-content;
  text-transform: capitalize;
}

.org-panel__member-role--admin  { background: #e8f0fe; color: #1e4a8a; }
.org-panel__member-role--owner  { background: #f3e8ff; color: #6b21a8; }
.org-panel__member-role--member { background: #f0f1f5; color: #6b7280; }

/* ─── Plan detail rows ─── */
.org-panel__plan-detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 0;
  border-bottom: 1px solid #f3f4f6;
  font-size: 13px;
}

.org-panel__plan-detail-row:last-child { border-bottom: none; }

.org-panel__plan-detail-label {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.org-panel__plan-detail-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  text-align: right;
}

/* ─── Responsive ─── */
@media (max-width: 1100px) {
  .org-panel__summary-strip {
    grid-template-columns: repeat(3, 1fr);
  }
  .org-panel__grid {
    grid-template-columns: 1fr 1fr;
  }
  .org-panel__card--projects {
    grid-column: 1 / -1;
  }
}

@media (max-width: 700px) {
  .org-panel__summary-strip {
    grid-template-columns: 1fr 1fr;
  }
  .org-panel__grid {
    grid-template-columns: 1fr;
  }
  .org-panel__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
