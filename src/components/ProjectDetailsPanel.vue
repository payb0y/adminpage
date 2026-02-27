<template>
  <div class="proj-details">
    <!-- Project Selector -->
    <div class="proj-details__selector">
      <label class="proj-details__selector-label">Select Project</label>
      <select v-model="selectedProjectId" class="proj-details__selector-input">
        <option value="">— Choose a project —</option>
        <option v-for="p in projects" :key="p.id" :value="p.id">
          {{ p.name }}{{ p.number ? " (" + p.number + ")" : "" }}
        </option>
      </select>
    </div>

    <!-- Empty state -->
    <div v-if="!selectedProject" class="proj-details__empty">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="40"
        height="40"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="proj-details__empty-icon"
      >
        <path
          d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
        />
      </svg>
      <p class="proj-details__empty-text">
        Select a project above to view detailed analytics
      </p>
    </div>

    <!-- Detail content -->
    <template v-if="selectedProject">
      <!-- ── Row 1: Info + Completion ── -->
      <div class="proj-details__row proj-details__row--two">
        <!-- Project Info Card -->
        <div class="proj-details__card">
          <div class="proj-details__card-header">
            <h4 class="proj-details__card-title">Project Information</h4>
            <span
              class="proj-details__badge"
              :class="
                'proj-details__badge--' +
                selectedProject.statusLabel.toLowerCase().replace(/ /g, '-')
              "
            >
              {{ selectedProject.statusLabel }}
            </span>
          </div>
          <div class="proj-details__info-grid">
            <div class="proj-details__info-item" v-if="selectedProject.number">
              <span class="proj-details__info-label">Project Number</span>
              <span class="proj-details__info-value">{{
                selectedProject.number
              }}</span>
            </div>
            <div
              class="proj-details__info-item"
              v-if="selectedProject.description"
            >
              <span class="proj-details__info-label">Description</span>
              <span class="proj-details__info-value">{{
                selectedProject.description
              }}</span>
            </div>
            <div
              class="proj-details__info-item"
              v-if="selectedProject.clientName"
            >
              <span class="proj-details__info-label">Client</span>
              <span class="proj-details__info-value">{{
                selectedProject.clientName
              }}</span>
            </div>
            <div
              class="proj-details__info-item"
              v-if="selectedProject.clientEmail"
            >
              <span class="proj-details__info-label">Email</span>
              <span class="proj-details__info-value">{{
                selectedProject.clientEmail
              }}</span>
            </div>
            <div
              class="proj-details__info-item"
              v-if="selectedProject.clientPhone"
            >
              <span class="proj-details__info-label">Phone</span>
              <span class="proj-details__info-value">{{
                selectedProject.clientPhone
              }}</span>
            </div>
            <div
              class="proj-details__info-item"
              v-if="
                selectedProject.location && selectedProject.location !== ','
              "
            >
              <span class="proj-details__info-label">Location</span>
              <span class="proj-details__info-value">{{
                selectedProject.location
              }}</span>
            </div>
            <div class="proj-details__info-item">
              <span class="proj-details__info-label">Created</span>
              <span class="proj-details__info-value">{{
                formatDate(selectedProject.createdAt)
              }}</span>
            </div>
            <div class="proj-details__info-item">
              <span class="proj-details__info-label">Last Updated</span>
              <span class="proj-details__info-value">{{
                formatDate(selectedProject.updatedAt)
              }}</span>
            </div>
          </div>
        </div>

        <!-- Completion + Resources Card -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Completion &amp; Resources</h4>
          <!-- Progress bar -->
          <div class="proj-details__progress">
            <div class="proj-details__progress-header">
              <span class="proj-details__progress-label">Task Completion</span>
              <span class="proj-details__progress-pct"
                >{{ selectedProject.completionPct }}%</span
              >
            </div>
            <div class="proj-details__progress-bar">
              <div
                class="proj-details__progress-fill"
                :style="{ width: selectedProject.completionPct + '%' }"
                :class="{
                  'proj-details__progress-fill--low':
                    selectedProject.completionPct < 25,
                  'proj-details__progress-fill--mid':
                    selectedProject.completionPct >= 25 &&
                    selectedProject.completionPct < 75,
                  'proj-details__progress-fill--high':
                    selectedProject.completionPct >= 75,
                }"
              ></div>
            </div>
            <span class="proj-details__progress-detail">
              {{ selectedProject.doneTasks }} of
              {{ selectedProject.totalTasks }} tasks completed
            </span>
          </div>
          <!-- Resource summary -->
          <div class="proj-details__resources">
            <div class="proj-details__resource-item">
              <span class="proj-details__resource-value">{{
                selectedProject.resources.files
              }}</span>
              <span class="proj-details__resource-label">Files</span>
            </div>
            <div class="proj-details__resource-item">
              <span class="proj-details__resource-value">{{
                selectedProject.resources.whiteboards
              }}</span>
              <span class="proj-details__resource-label">Whiteboards</span>
            </div>
            <div class="proj-details__resource-item">
              <span class="proj-details__resource-value">{{
                selectedProject.resources.notes
              }}</span>
              <span class="proj-details__resource-label">Notes</span>
            </div>
            <div
              class="proj-details__resource-item"
              v-if="selectedProject.assignees.length > 0"
            >
              <span class="proj-details__resource-value">{{
                selectedProject.assignees.length
              }}</span>
              <span class="proj-details__resource-label">Assignees</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Row 2: Task Distribution + Due Dates ── -->
      <div class="proj-details__row proj-details__row--two">
        <!-- Task Distribution by Stack (Donut) -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Task Distribution by Stack</h4>
          <div
            v-if="stackChartData.length > 0"
            class="proj-details__chart-wrap"
          >
            <DonutChart
              :labels="stackChartLabels"
              :data="stackChartData"
              :colors="stackChartColors"
            />
          </div>
          <div v-else class="proj-details__no-data">No tasks found</div>
          <!-- Stack table -->
          <table
            v-if="selectedProject.tasksByStack.length > 0"
            class="proj-details__table"
          >
            <thead>
              <tr>
                <th>Stack</th>
                <th class="proj-details__table-num">Tasks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(s, i) in selectedProject.tasksByStack" :key="i">
                <td>
                  <span
                    class="proj-details__stack-dot"
                    :style="{
                      backgroundColor: stackColors[i % stackColors.length],
                    }"
                  ></span>
                  {{ s.stack }}
                </td>
                <td class="proj-details__table-num">{{ s.total }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Due Date Breakdown -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Due Date Breakdown</h4>
          <div class="proj-details__due-grid">
            <div class="proj-details__due-item proj-details__due-item--danger">
              <span class="proj-details__due-value">{{
                selectedProject.dueStats.overdue
              }}</span>
              <span class="proj-details__due-label">Overdue</span>
            </div>
            <div class="proj-details__due-item proj-details__due-item--warning">
              <span class="proj-details__due-value">{{
                selectedProject.dueStats.today
              }}</span>
              <span class="proj-details__due-label">Due Today</span>
            </div>
            <div class="proj-details__due-item proj-details__due-item--info">
              <span class="proj-details__due-value">{{
                selectedProject.dueStats.upcoming
              }}</span>
              <span class="proj-details__due-label">Upcoming</span>
            </div>
            <div class="proj-details__due-item proj-details__due-item--muted">
              <span class="proj-details__due-value">{{
                selectedProject.dueStats.no_due
              }}</span>
              <span class="proj-details__due-label">No Due Date</span>
            </div>
          </div>

          <!-- Assignees -->
          <div
            v-if="selectedProject.assignees.length > 0"
            class="proj-details__assignees"
          >
            <h4 class="proj-details__card-title proj-details__card-title--sub">
              Team Members
            </h4>
            <div class="proj-details__assignee-list">
              <div
                v-for="(a, i) in selectedProject.assignees"
                :key="i"
                class="proj-details__assignee"
              >
                <span class="proj-details__assignee-avatar">{{
                  a.userId.charAt(0).toUpperCase()
                }}</span>
                <span class="proj-details__assignee-name">{{ a.userId }}</span>
                <span class="proj-details__assignee-count"
                  >{{ a.tasks }} tasks</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Row 3: Timeline ── -->
      <div v-if="selectedProject.timeline.length > 0" class="proj-details__row">
        <div class="proj-details__card proj-details__card--full">
          <h4 class="proj-details__card-title">Project Timeline</h4>
          <table class="proj-details__table proj-details__table--timeline">
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Duration</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, i) in selectedProject.timeline" :key="i">
                <td>
                  <span
                    class="proj-details__timeline-dot"
                    :style="{ backgroundColor: t.color || '#94a3b8' }"
                  ></span>
                  {{ t.label }}
                </td>
                <td>
                  <span
                    v-if="t.systemKey"
                    class="proj-details__badge proj-details__badge--system"
                    >System</span
                  >
                  <span
                    v-else
                    class="proj-details__badge proj-details__badge--custom"
                    >Custom</span
                  >
                </td>
                <td>{{ formatDate(t.startDate) }}</td>
                <td>{{ formatDate(t.endDate) }}</td>
                <td>{{ calcDuration(t.startDate, t.endDate) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import DonutChart from "./DonutChart.vue";

export default {
  name: "ProjectDetailsPanel",
  components: { DonutChart },
  props: {
    projects: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  data: function () {
    return {
      selectedProjectId: "",
      stackColors: [
        "#4A90D9",
        "#E67E5A",
        "#2E9E5A",
        "#8B5CF6",
        "#0EA5E9",
        "#F59E0B",
        "#EC4899",
        "#6B7280",
        "#10B981",
        "#EF4444",
      ],
    };
  },
  computed: {
    selectedProject: function () {
      if (!this.selectedProjectId) return null;
      var id = Number(this.selectedProjectId);
      return (
        this.projects.find(function (p) {
          return p.id === id;
        }) || null
      );
    },
    stackChartLabels: function () {
      if (!this.selectedProject) return [];
      return this.selectedProject.tasksByStack
        .filter(function (s) {
          return s.total > 0;
        })
        .map(function (s) {
          return s.stack;
        });
    },
    stackChartData: function () {
      if (!this.selectedProject) return [];
      return this.selectedProject.tasksByStack
        .filter(function (s) {
          return s.total > 0;
        })
        .map(function (s) {
          return s.total;
        });
    },
    stackChartColors: function () {
      var self = this;
      if (!this.selectedProject) return [];
      var filtered = this.selectedProject.tasksByStack.filter(function (s) {
        return s.total > 0;
      });
      return filtered.map(function (_, i) {
        return self.stackColors[i % self.stackColors.length];
      });
    },
  },
  methods: {
    formatDate: function (d) {
      if (!d) return "—";
      var date = new Date(d);
      if (isNaN(date.getTime())) return d;
      return date.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    },
    calcDuration: function (start, end) {
      if (!start || !end) return "—";
      var s = new Date(start);
      var e = new Date(end);
      if (isNaN(s.getTime()) || isNaN(e.getTime())) return "—";
      var days = Math.round((e - s) / (1000 * 60 * 60 * 24));
      if (days < 7) return days + "d";
      var weeks = Math.round(days / 7);
      return weeks + " wk" + (weeks !== 1 ? "s" : "");
    },
  },
};
</script>

<style scoped>
.proj-details__selector {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.proj-details__selector-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  white-space: nowrap;
}

.proj-details__selector-input {
  flex: 1;
  max-width: 400px;
  padding: 8px 12px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.proj-details__selector-input:focus {
  border-color: #4a90d9;
}

.proj-details__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 24px;
  color: var(--color-text-muted, #9ca3af);
}

.proj-details__empty-icon {
  margin-bottom: 12px;
  opacity: 0.4;
}

.proj-details__empty-text {
  font-size: 13px;
  margin: 0;
}

/* ── Layout ── */
.proj-details__row {
  margin-bottom: 16px;
}

.proj-details__row--two {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.proj-details__card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  padding: 20px;
}

.proj-details__card--full {
  width: 100%;
}

.proj-details__card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.proj-details__card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0 0 16px 0;
  padding: 0;
  border: none;
}

.proj-details__card-header .proj-details__card-title {
  margin-bottom: 0;
}

.proj-details__card-title--sub {
  font-size: 13px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border, #e5e7eb);
}

/* ── Badges ── */
.proj-details__badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.4;
}

.proj-details__badge--active {
  background: #d4edda;
  color: #166534;
}

.proj-details__badge--waiting-on-customer {
  background: #fef3cd;
  color: #92400e;
}

.proj-details__badge--on-hold {
  background: #fde8e8;
  color: #b91c1c;
}

.proj-details__badge--done {
  background: #e0e7ff;
  color: #3730a3;
}

.proj-details__badge--system {
  background: #e0f2fe;
  color: #0369a1;
}

.proj-details__badge--custom {
  background: #f3e8ff;
  color: #7c3aed;
}

/* ── Info Grid ── */
.proj-details__info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.proj-details__info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.proj-details__info-label {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 500;
}

.proj-details__info-value {
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  word-break: break-word;
}

/* ── Progress ── */
.proj-details__progress {
  margin-bottom: 20px;
}

.proj-details__progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.proj-details__progress-label {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.proj-details__progress-pct {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__progress-bar {
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}

.proj-details__progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.5s ease;
}

.proj-details__progress-fill--low {
  background: #ef4444;
}

.proj-details__progress-fill--mid {
  background: #f59e0b;
}

.proj-details__progress-fill--high {
  background: #10b981;
}

.proj-details__progress-detail {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 4px;
  display: block;
}

/* ── Resources ── */
.proj-details__resources {
  display: flex;
  gap: 0;
}

.proj-details__resource-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  border-radius: 8px;
  background: #f8fafc;
}

.proj-details__resource-item + .proj-details__resource-item {
  margin-left: 8px;
}

.proj-details__resource-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__resource-label {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 2px;
}

/* ── Chart ── */
.proj-details__chart-wrap {
  max-width: 200px;
  margin: 0 auto 16px;
}

.proj-details__no-data {
  text-align: center;
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 24px;
}

/* ── Tables ── */
.proj-details__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.proj-details__table th {
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  padding: 8px 10px;
  border-bottom: 1px solid var(--color-border, #e5e7eb);
}

.proj-details__table td {
  padding: 8px 10px;
  color: var(--color-text-primary, #1a1a2e);
  border-bottom: 1px solid #f3f4f6;
}

.proj-details__table tbody tr:last-child td {
  border-bottom: none;
}

.proj-details__table-num {
  text-align: right;
}

.proj-details__stack-dot,
.proj-details__timeline-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 6px;
  vertical-align: middle;
}

/* ── Due Grid ── */
.proj-details__due-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 8px;
}

.proj-details__due-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 14px 10px;
  border-radius: 8px;
}

.proj-details__due-item--danger {
  background: #fef2f2;
}

.proj-details__due-item--warning {
  background: #fffbeb;
}

.proj-details__due-item--info {
  background: #eff6ff;
}

.proj-details__due-item--muted {
  background: #f8fafc;
}

.proj-details__due-value {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__due-label {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 2px;
}

/* ── Assignees ── */
.proj-details__assignee-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.proj-details__assignee {
  display: flex;
  align-items: center;
  gap: 10px;
}

.proj-details__assignee-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e0e7ff;
  color: #4338ca;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.proj-details__assignee-name {
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  flex: 1;
}

.proj-details__assignee-count {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .proj-details__row--two {
    grid-template-columns: 1fr;
  }

  .proj-details__info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
