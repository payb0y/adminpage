<template>
  <section class="alerts-panel">
    <!-- HEADER -->
    <div class="alerts-panel__header">
      <div class="alerts-panel__header-text">
        <h2 class="alerts-panel__title">Alerts &amp; Exceptions</h2>
      </div>
      <button
        class="alerts-panel__collapse-btn"
        @click="collapsed = !collapsed"
      >
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
          :class="{ 'alerts-panel__collapse-icon--rotated': collapsed }"
          class="alerts-panel__collapse-icon"
        >
          <polyline points="18 15 12 9 6 15" />
        </svg>
      </button>
    </div>

    <div v-show="!collapsed">
      <!-- SUMMARY STRIP — clickable tiles -->
      <div class="alerts-panel__summary-strip">
        <button
          v-for="(item, idx) in summaryTiles"
          :key="'sum-' + idx"
          class="alerts-panel__summary-card"
          :class="[
            'alerts-panel__summary-card--' + item.type,
            { 'alerts-panel__summary-card--clickable': item.hasDetail },
          ]"
          @click="item.hasDetail ? openDrawer(item.key) : null"
        >
          <span class="alerts-panel__summary-value">{{ item.value }}</span>
          <span class="alerts-panel__summary-label">{{ item.label }}</span>
          <span v-if="item.hasDetail" class="alerts-panel__summary-hint">
            click to view
          </span>
        </button>
      </div>
    </div>

    <!-- SLIDE-OVER DRAWER -->
    <transition name="drawer">
      <div
        v-if="activeDrawer"
        class="alerts-drawer__backdrop"
        @click.self="closeDrawer"
      >
        <div class="alerts-drawer" @click.stop>
          <!-- Drawer Header -->
          <div
            class="alerts-drawer__header"
            :class="'alerts-drawer__header--' + drawerConfig.type"
          >
            <div class="alerts-drawer__header-left">
              <span class="alerts-drawer__icon">{{ drawerConfig.icon }}</span>
              <h3 class="alerts-drawer__title">{{ drawerConfig.title }}</h3>
            </div>
            <button class="alerts-drawer__close" @click="closeDrawer">
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
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <!-- Drawer Body -->
          <div class="alerts-drawer__body">
            <!-- ── Overdue Tasks ── -->
            <template v-if="activeDrawer === 'overdue'">
              <div
                v-if="alerts.overdueTasks.length === 0"
                class="alerts-panel__empty"
              >
                <span class="alerts-panel__empty-check">✓</span>
                No overdue tasks
              </div>
              <div
                v-for="group in alerts.overdueTasks"
                :key="'od-' + group.project_name"
                class="alerts-drawer__project-group"
              >
                <div class="alerts-drawer__project-header">
                  <span class="alerts-drawer__project-name">{{
                    group.project_name
                  }}</span>
                  <span class="alerts-drawer__badge alerts-drawer__badge--danger">
                    {{ group.count }} task{{ group.count > 1 ? "s" : "" }}
                  </span>
                </div>
                <table class="alerts-drawer__table">
                  <thead>
                    <tr>
                      <th>Task</th>
                      <th>Status</th>
                      <th>Overdue</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="task in visibleTasks('overdue', group.project_name, group.tasks)"
                      :key="'od-t-' + task.task_id"
                    >
                      <td class="alerts-drawer__cell-name">{{ task.task_title }}</td>
                      <td>
                        <span class="alerts-drawer__stack-tag">{{ task.stack_title }}</span>
                      </td>
                      <td>
                        <span class="alerts-drawer__overdue-tag">
                          {{ task.days_overdue }}d
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <button
                  v-if="group.tasks.length > 5"
                  class="alerts-drawer__show-more"
                  @click="toggleExpanded('overdue', group.project_name)"
                >
                  {{ isExpanded('overdue', group.project_name)
                    ? 'Show less'
                    : `+${group.tasks.length - 5} more` }}
                </button>
              </div>
            </template>

            <!-- ── Unassigned Tasks ── -->
            <template v-if="activeDrawer === 'unassigned'">
              <div
                v-if="alerts.unassignedTasks.length === 0"
                class="alerts-panel__empty"
              >
                <span class="alerts-panel__empty-check">✓</span>
                All tasks have an assignee
              </div>
              <div
                v-for="group in alerts.unassignedTasks"
                :key="'ua-' + group.project_name"
                class="alerts-drawer__project-group"
              >
                <div class="alerts-drawer__project-header">
                  <span class="alerts-drawer__project-name">{{
                    group.project_name
                  }}</span>
                  <span class="alerts-drawer__badge alerts-drawer__badge--warning">
                    {{ group.count }} task{{ group.count > 1 ? "s" : "" }}
                  </span>
                </div>
                <table class="alerts-drawer__table">
                  <thead>
                    <tr>
                      <th>Task</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="task in visibleTasks('unassigned', group.project_name, group.tasks)"
                      :key="'ua-t-' + task.task_id"
                    >
                      <td class="alerts-drawer__cell-name">{{ task.task_title }}</td>
                      <td>
                        <span class="alerts-drawer__stack-tag">{{ task.stack_title }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <button
                  v-if="group.tasks.length > 5"
                  class="alerts-drawer__show-more"
                  @click="toggleExpanded('unassigned', group.project_name)"
                >
                  {{ isExpanded('unassigned', group.project_name)
                    ? 'Show less'
                    : `+${group.tasks.length - 5} more` }}
                </button>
              </div>
            </template>

            <!-- ── No Due Date Tasks ── -->
            <template v-if="activeDrawer === 'nodue'">
              <div
                v-if="alerts.noDueDateTasks.length === 0"
                class="alerts-panel__empty"
              >
                <span class="alerts-panel__empty-check">✓</span>
                All tasks have a due date
              </div>
              <div
                v-for="group in alerts.noDueDateTasks"
                :key="'nd-' + group.project_name"
                class="alerts-drawer__project-group"
              >
                <div class="alerts-drawer__project-header">
                  <span class="alerts-drawer__project-name">{{
                    group.project_name
                  }}</span>
                  <span class="alerts-drawer__badge alerts-drawer__badge--warning">
                    {{ group.count }} task{{ group.count > 1 ? "s" : "" }}
                  </span>
                </div>
                <table class="alerts-drawer__table">
                  <thead>
                    <tr>
                      <th>Task</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="task in visibleTasks('nodue', group.project_name, group.tasks)"
                      :key="'nd-t-' + task.task_id"
                    >
                      <td class="alerts-drawer__cell-name">{{ task.task_title }}</td>
                      <td>
                        <span class="alerts-drawer__stack-tag">{{ task.stack_title }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <button
                  v-if="group.tasks.length > 5"
                  class="alerts-drawer__show-more"
                  @click="toggleExpanded('nodue', group.project_name)"
                >
                  {{ isExpanded('nodue', group.project_name)
                    ? 'Show less'
                    : `+${group.tasks.length - 5} more` }}
                </button>
              </div>
            </template>

            <!-- ── Stalled Projects ── -->
            <template v-if="activeDrawer === 'stalled'">
              <div
                v-if="alerts.stalledProjects.length === 0 && alerts.zeroProgress.length === 0"
                class="alerts-panel__empty"
              >
                <span class="alerts-panel__empty-check">✓</span>
                All projects are active
              </div>

              <div v-if="alerts.stalledProjects.length > 0" class="alerts-drawer__section">
                <h4 class="alerts-drawer__section-title">Stalled Projects</h4>
                <table class="alerts-drawer__table">
                  <thead>
                    <tr>
                      <th>Project</th>
                      <th>Inactive</th>
                      <th>Progress</th>
                      <th>Last Activity</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="proj in alerts.stalledProjects"
                      :key="'stalled-' + proj.project_name"
                    >
                      <td class="alerts-drawer__cell-name">{{ proj.project_name }}</td>
                      <td>
                        <span class="alerts-drawer__overdue-tag">{{ proj.days_inactive }}d</span>
                      </td>
                      <td>{{ proj.done_tasks }}/{{ proj.total_tasks }} done</td>
                      <td class="alerts-drawer__cell-muted">{{ proj.last_activity }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="alerts.zeroProgress.length > 0" class="alerts-drawer__section">
                <h4 class="alerts-drawer__section-title">Zero Progress</h4>
                <table class="alerts-drawer__table">
                  <thead>
                    <tr>
                      <th>Project</th>
                      <th>Total Tasks</th>
                      <th>Completed</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="proj in alerts.zeroProgress"
                      :key="'zp-' + proj.project_name"
                    >
                      <td class="alerts-drawer__cell-name">{{ proj.project_name }}</td>
                      <td>{{ proj.total_tasks }}</td>
                      <td><span class="alerts-drawer__overdue-tag">0</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- ── App Updates ── -->
            <template v-if="activeDrawer === 'updates'">
              <div
                v-if="alerts.pendingUpdates.length === 0"
                class="alerts-panel__empty"
              >
                <span class="alerts-panel__empty-check">✓</span>
                All apps are up to date
              </div>
              <table v-else class="alerts-drawer__table">
                <thead>
                  <tr>
                    <th>Application</th>
                    <th>Details</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(upd, idx) in alerts.pendingUpdates"
                    :key="'upd-' + idx"
                  >
                    <td class="alerts-drawer__cell-name">{{ upd.app_name }}</td>
                    <td class="alerts-drawer__cell-muted">{{ upd.subject || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>
        </div>
      </div>
    </transition>
  </section>
</template>

<script>
export default {
  name: "AlertsPanel",
  props: {
    alerts: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      collapsed: false,
      activeDrawer: null,
      expandedGroups: {},
    };
  },
  computed: {
    overdueTotal() {
      return this.alerts.overdueTasks.reduce((sum, g) => sum + g.count, 0);
    },
    unassignedTotal() {
      return this.alerts.unassignedTasks.reduce((sum, g) => sum + g.count, 0);
    },
    noDueDateTotal() {
      return this.alerts.noDueDateTasks.reduce((sum, g) => sum + g.count, 0);
    },
    stalledTotal() {
      return this.alerts.stalledProjects.length + this.alerts.zeroProgress.length;
    },
    summaryTiles() {
      return this.alerts.summary.map((item) => {
        const keyMap = {
          'Overdue Tasks': 'overdue',
          'Unassigned Tasks': 'unassigned',
          'No Due Date': 'nodue',
          'Stalled Projects': 'stalled',
          'App Updates': 'updates',
        };
        return {
          ...item,
          key: keyMap[item.label] || item.label,
          hasDetail: item.value > 0,
        };
      });
    },
    drawerConfig() {
      const configs = {
        overdue: { title: 'Overdue Tasks', icon: '!', type: 'danger' },
        unassigned: { title: 'Unassigned Tasks', icon: '?', type: 'warning' },
        nodue: { title: 'Tasks Without Due Date', icon: '⏱', type: 'warning' },
        stalled: { title: 'Project Health', icon: '⚡', type: 'warning' },
        updates: { title: 'Pending App Updates', icon: '↑', type: 'warning' },
      };
      return configs[this.activeDrawer] || { title: '', icon: '', type: '' };
    },
  },
  methods: {
    openDrawer(key) {
      this.expandedGroups = {};
      this.activeDrawer = key;
      document.body.style.overflow = 'hidden';
    },
    closeDrawer() {
      this.activeDrawer = null;
      document.body.style.overflow = '';
    },
    expandKey(drawer, projectName) {
      return `${drawer}::${projectName}`;
    },
    isExpanded(drawer, projectName) {
      return !!this.expandedGroups[this.expandKey(drawer, projectName)];
    },
    toggleExpanded(drawer, projectName) {
      const key = this.expandKey(drawer, projectName);
      this.$set(this.expandedGroups, key, !this.expandedGroups[key]);
    },
    visibleTasks(drawer, projectName, tasks) {
      return this.isExpanded(drawer, projectName) ? tasks : tasks.slice(0, 5);
    },
  },
  beforeDestroy() {
    document.body.style.overflow = '';
  },
};
</script>

<style scoped>
.alerts-panel {
  margin-bottom: var(--spacing-xl, 32px);
}

/* ─── Header ─── */
.alerts-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg, 24px);
  background: #fcfdff;
  border: 1px solid #eef1f5;
  border-radius: var(--radius-card, 12px);
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
}

.alerts-panel__title {
  font-size: 22px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
}

.alerts-panel__collapse-btn {
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

.alerts-panel__collapse-btn:hover {
  background-color: var(--bg-page, #f5f6fa);
}

.alerts-panel__collapse-icon {
  transition: transform 0.3s ease;
}

.alerts-panel__collapse-icon--rotated {
  transform: rotate(180deg);
}

/* ─── Summary Strip ─── */
.alerts-panel__summary-strip {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: var(--spacing-sm, 8px);
  margin-bottom: var(--spacing-md, 16px);
}

.alerts-panel__summary-card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-md, 16px);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  border-top: 3px solid transparent;
  border-left: none;
  border-right: none;
  border-bottom: none;
  font-family: inherit;
  cursor: default;
  transition: all 0.2s ease;
  position: relative;
}

.alerts-panel__summary-card--clickable {
  cursor: pointer;
}

.alerts-panel__summary-card--clickable:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.alerts-panel__summary-card--clickable:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alerts-panel__summary-card--danger {
  border-top-color: #e63946;
}

.alerts-panel__summary-card--warning {
  border-top-color: #f4a261;
}

.alerts-panel__summary-card--success {
  border-top-color: #2ec4b6;
}

.alerts-panel__summary-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1;
}

.alerts-panel__summary-card--danger .alerts-panel__summary-value {
  color: #e63946;
}

.alerts-panel__summary-card--warning .alerts-panel__summary-value {
  color: #b8860b;
}

.alerts-panel__summary-card--success .alerts-panel__summary-value {
  color: #2e7d32;
}

.alerts-panel__summary-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
  text-align: center;
}

.alerts-panel__summary-hint {
  font-size: 10px;
  color: #9ca3af;
  opacity: 0;
  transition: opacity 0.2s ease;
  position: absolute;
  bottom: 4px;
}

.alerts-panel__summary-card--clickable:hover .alerts-panel__summary-hint {
  opacity: 1;
}

/* ─── Empty State ─── */
.alerts-panel__empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: var(--spacing-md, 16px);
  background: #f0fdf4;
  border-radius: 8px;
  color: #166534;
  font-size: 13px;
  font-weight: 500;
}

.alerts-panel__empty-check {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #d4edda;
  color: #166534;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

/* ─── Drawer Backdrop ─── */
.alerts-drawer__backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 9999;
  display: flex;
  justify-content: flex-end;
}

/* ─── Drawer Panel ─── */
.alerts-drawer {
  width: 560px;
  max-width: 90vw;
  height: 100vh;
  background: #fff;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.alerts-drawer__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #eef1f5;
  flex-shrink: 0;
}

.alerts-drawer__header--danger {
  background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
  border-bottom-color: #fde8e8;
}

.alerts-drawer__header--warning {
  background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
  border-bottom-color: #fef3cd;
}

.alerts-drawer__header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.alerts-drawer__icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.alerts-drawer__header--danger .alerts-drawer__icon {
  background-color: #fde8e8;
  color: #b91c1c;
}

.alerts-drawer__header--warning .alerts-drawer__icon {
  background-color: #fef3cd;
  color: #92400e;
}

.alerts-drawer__title {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
}

.alerts-drawer__close {
  background: none;
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.alerts-drawer__close:hover {
  background: #f3f4f6;
  color: #1a1a2e;
}

/* ─── Drawer Body ─── */
.alerts-drawer__body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.alerts-drawer__body::-webkit-scrollbar {
  width: 5px;
}

.alerts-drawer__body::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

/* ─── Project Group inside drawer ─── */
.alerts-drawer__project-group {
  margin-bottom: 24px;
}

.alerts-drawer__project-group:last-child {
  margin-bottom: 0;
}

.alerts-drawer__project-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f0f1f5;
}

.alerts-drawer__project-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.alerts-drawer__badge {
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 12px;
}

.alerts-drawer__badge--danger {
  background-color: #fde8e8;
  color: #b91c1c;
}

.alerts-drawer__badge--warning {
  background-color: #fef3cd;
  color: #92400e;
}

/* ─── Tables ─── */
.alerts-drawer__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.alerts-drawer__table thead th {
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 6px 8px;
  border-bottom: 1px solid #f0f1f5;
}

.alerts-drawer__table tbody tr {
  transition: background 0.1s ease;
}

.alerts-drawer__table tbody tr:hover {
  background: #f9fafb;
}

.alerts-drawer__table tbody td {
  padding: 10px 8px;
  border-bottom: 1px solid #f8f9fa;
  color: var(--color-text-primary, #1a1a2e);
  vertical-align: middle;
}

.alerts-drawer__cell-name {
  font-weight: 500;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.alerts-drawer__cell-muted {
  color: #9ca3af;
  font-size: 12px;
}

.alerts-drawer__stack-tag {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  background: #f5f6fa;
  padding: 3px 8px;
  border-radius: 4px;
  white-space: nowrap;
}

.alerts-drawer__overdue-tag {
  font-size: 11px;
  font-weight: 600;
  color: #b91c1c;
  background-color: #fde8e8;
  padding: 3px 8px;
  border-radius: 4px;
  white-space: nowrap;
}

/* ─── Show More ─── */
.alerts-drawer__show-more {
  background: none;
  border: none;
  color: #4a90d9;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  padding: 6px 0;
  margin-top: 4px;
}

.alerts-drawer__show-more:hover {
  text-decoration: underline;
}

/* ─── Section (Stalled/Health) ─── */
.alerts-drawer__section {
  margin-bottom: 24px;
}

.alerts-drawer__section:last-child {
  margin-bottom: 0;
}

.alerts-drawer__section-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 12px 0;
  padding: 0 0 8px 0;
  border: none;
  border-bottom: 2px solid #f0f1f5;
}

/* ─── Drawer Transition ─── */
.drawer-enter-active {
  transition: opacity 0.25s ease;
}

.drawer-enter-active .alerts-drawer {
  animation: slideIn 0.25s ease forwards;
}

.drawer-leave-active {
  transition: opacity 0.2s ease;
}

.drawer-leave-active .alerts-drawer {
  animation: slideOut 0.2s ease forwards;
}

.drawer-enter,
.drawer-leave-to {
  opacity: 0;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
  }
  to {
    transform: translateX(0);
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(100%);
  }
}

/* ─── Responsive ─── */
@media (max-width: 1024px) {
  .alerts-panel__summary-strip {
    grid-template-columns: repeat(3, 1fr);
  }

  .alerts-drawer {
    width: 480px;
  }
}

@media (max-width: 640px) {
  .alerts-panel__summary-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .alerts-drawer {
    width: 100vw;
    max-width: 100vw;
  }
}
</style>
