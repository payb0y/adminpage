<template>
  <section class="perf-panel">
    <!-- HEADER -->
    <div class="perf-panel__header">
      <div class="perf-panel__header-text">
        <h2 class="perf-panel__title">Project Performance Analytics</h2>
      </div>
      <button class="perf-panel__collapse-btn" @click="collapsed = !collapsed">
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
          :class="{ 'perf-panel__collapse-icon--rotated': collapsed }"
          class="perf-panel__collapse-icon"
        >
          <polyline points="18 15 12 9 6 15" />
        </svg>
      </button>
    </div>

    <div v-show="!collapsed">
      <!-- TOP ROW: Progress + Productivity -->
      <div class="perf-panel__top-grid">
        <!-- Project Progress Comparison -->
        <div class="perf-panel__card perf-panel__card--clickable" @click="openModal('progress')">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Project Progress<br />Comparison
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
            </span>
          </div>
          <div class="perf-panel__card-title-underline"></div>
          <div class="perf-panel__bar-list">
            <div
              v-for="(item, idx) in projectProgress"
              :key="'prog-' + idx"
              class="perf-panel__bar-item"
            >
              <div class="perf-panel__bar-row">
                <span class="perf-panel__bar-label">{{ item.name }}</span>
                <span class="perf-panel__bar-value">{{ item.progress }}%</span>
              </div>
              <div class="perf-panel__bar-track">
                <div
                  class="perf-panel__bar-fill"
                  :style="{ width: item.progress + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Productivity by Discipline -->
        <div class="perf-panel__card perf-panel__card--clickable" @click="openModal('discipline')">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Productivity<br />by Discipline
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
            </span>
          </div>
          <div class="perf-panel__card-title-underline"></div>
          <p class="perf-panel__card-desc">
            Tasks completed in relation to the assigned task
          </p>
          <div class="perf-panel__bar-list">
            <div
              v-for="(item, idx) in productivityByDiscipline"
              :key="'disc-' + idx"
              class="perf-panel__bar-item"
            >
              <div class="perf-panel__bar-row">
                <span class="perf-panel__bar-label">{{ item.name }}</span>
                <span class="perf-panel__bar-value">{{ item.progress }}%</span>
              </div>
              <div class="perf-panel__bar-track">
                <div
                  class="perf-panel__bar-fill"
                  :style="{ width: item.progress + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ROW: Delay Donut + Completion Area -->
      <div class="perf-panel__bottom-grid">
        <!-- Project Tasks Delay Overview -->
        <div class="perf-panel__card perf-panel__card--clickable" @click="openModal('delay')">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Project Tasks Delay<br />Overview
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
            </span>
          </div>
          <div class="perf-panel__card-title-underline"></div>

          <!-- Project navigator -->
          <div class="perf-panel__navigator" @click.stop>
            <button class="perf-panel__nav-btn" @click="prevDelayProject">
              &lsaquo;
            </button>
            <span class="perf-panel__nav-label">{{
              activeDelayProject.name
            }}</span>
            <button class="perf-panel__nav-btn" @click="nextDelayProject">
              &rsaquo;
            </button>
          </div>

          <DonutChart
            :key="'donut-' + delayIndex"
            :chart-data="activeDelayProject.chart"
          />
        </div>

        <!-- Task Completion Over Time -->
        <div class="perf-panel__card perf-panel__card--clickable" @click="openModal('completion')">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">Task Completion<br />Over Time</h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
            </span>
          </div>
          <div class="perf-panel__card-title-underline"></div>

          <!-- Project navigator -->
          <div class="perf-panel__navigator" @click.stop>
            <button class="perf-panel__nav-btn" @click="prevCompletionProject">
              &lsaquo;
            </button>
            <span class="perf-panel__nav-label">{{
              activeCompletionProject.name
            }}</span>
            <button class="perf-panel__nav-btn" @click="nextCompletionProject">
              &rsaquo;
            </button>
          </div>

          <AreaChart
            :key="'area-' + completionIndex"
            :labels="activeCompletionProject.weeks"
            :data="activeCompletionProject.data"
          />
        </div>
      </div>
    </div>

    <!-- DRILL-DOWN MODAL -->
    <div v-if="modal" class="perf-modal__backdrop" @click.self="closeModal">
      <div class="perf-modal">
        <div class="perf-modal__header">
          <h3 class="perf-modal__title">{{ modalTitle }}</h3>
          <button class="perf-modal__close" @click="closeModal">&times;</button>
        </div>
        <div class="perf-modal__body">

          <!-- Progress Detail -->
          <template v-if="modal === 'progress'">
            <div
              v-for="proj in details.progressDetails"
              :key="'mp-' + proj.name"
              class="perf-modal__project"
            >
              <div class="perf-modal__project-header" @click="toggleProject('progress', proj.name)">
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ proj.done }}/{{ proj.total }} done
                  </span>
                  <span class="perf-modal__badge" :class="progressBadgeClass(proj.progress)">
                    {{ proj.progress }}%
                  </span>
                  <span class="perf-modal__chevron" :class="{ 'perf-modal__chevron--open': isProjectOpen('progress', proj.name) }">&#8250;</span>
                </div>
              </div>
              <div class="perf-modal__progress-bar">
                <div class="perf-modal__progress-fill" :style="{ width: proj.progress + '%' }"></div>
              </div>
              <transition name="perf-modal-expand">
                <div v-if="isProjectOpen('progress', proj.name)" class="perf-modal__task-table-wrap">
                  <table class="perf-modal__task-table">
                    <thead>
                      <tr>
                        <th>Task</th>
                        <th>Stack</th>
                        <th>Status</th>
                        <th>Due Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in proj.tasks" :key="'mpt-' + ti">
                        <td>{{ task.title }}</td>
                        <td><span class="perf-modal__stack-badge">{{ task.stack }}</span></td>
                        <td><span class="perf-modal__status" :class="'perf-modal__status--' + task.status">{{ task.status }}</span></td>
                        <td>{{ task.due || '\u2014' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div v-if="details.progressDetails.length === 0" class="perf-modal__empty">No project data available</div>
          </template>

          <!-- Discipline Detail -->
          <template v-if="modal === 'discipline'">
            <div
              v-for="disc in details.disciplineDetails"
              :key="'md-' + disc.name"
              class="perf-modal__project"
            >
              <div class="perf-modal__project-header" @click="toggleProject('discipline', disc.name)">
                <span class="perf-modal__project-name">{{ disc.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ disc.done }}/{{ disc.total }} done
                  </span>
                  <span class="perf-modal__badge" :class="progressBadgeClass(disc.progress)">
                    {{ disc.progress }}%
                  </span>
                  <span class="perf-modal__chevron" :class="{ 'perf-modal__chevron--open': isProjectOpen('discipline', disc.name) }">&#8250;</span>
                </div>
              </div>
              <div class="perf-modal__progress-bar">
                <div class="perf-modal__progress-fill" :style="{ width: disc.progress + '%' }"></div>
              </div>
              <transition name="perf-modal-expand">
                <div v-if="isProjectOpen('discipline', disc.name)" class="perf-modal__task-table-wrap">
                  <table class="perf-modal__task-table">
                    <thead>
                      <tr>
                        <th>Task</th>
                        <th>Project</th>
                        <th>Stack</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in disc.tasks" :key="'mdt-' + ti">
                        <td>{{ task.title }}</td>
                        <td>{{ task.project }}</td>
                        <td><span class="perf-modal__stack-badge">{{ task.stack }}</span></td>
                        <td><span class="perf-modal__status" :class="'perf-modal__status--' + task.status">{{ task.status }}</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div v-if="details.disciplineDetails.length === 0" class="perf-modal__empty">No label/discipline data available</div>
          </template>

          <!-- Delay Detail -->
          <template v-if="modal === 'delay'">
            <div
              v-for="proj in details.delayDetails"
              :key="'mdl-' + proj.name"
              class="perf-modal__project"
            >
              <div class="perf-modal__project-header" @click="toggleProject('delay', proj.name)">
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--success">
                    {{ countByCategory(proj.tasks, 'on-time') }} on-time
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--warning">
                    {{ countByCategory(proj.tasks, 'delayed') }} delayed
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--danger">
                    {{ countByCategory(proj.tasks, 'blocked') }} blocked
                  </span>
                  <span class="perf-modal__chevron" :class="{ 'perf-modal__chevron--open': isProjectOpen('delay', proj.name) }">&#8250;</span>
                </div>
              </div>
              <transition name="perf-modal-expand">
                <div v-if="isProjectOpen('delay', proj.name)" class="perf-modal__task-table-wrap">
                  <table class="perf-modal__task-table">
                    <thead>
                      <tr>
                        <th>Task</th>
                        <th>Stack</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Due</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in proj.tasks" :key="'mdlt-' + ti">
                        <td>{{ task.title }}</td>
                        <td><span class="perf-modal__stack-badge">{{ task.stack }}</span></td>
                        <td><span class="perf-modal__status" :class="'perf-modal__status--' + task.status">{{ task.status }}</span></td>
                        <td>
                          <span class="perf-modal__category" :class="'perf-modal__category--' + task.category">
                            {{ task.category }}
                            <template v-if="task.days_overdue"> ({{ task.days_overdue }}d)</template>
                          </span>
                        </td>
                        <td>{{ task.due || '\u2014' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div v-if="details.delayDetails.length === 0" class="perf-modal__empty">No delay data available</div>
          </template>

          <!-- Completion Detail -->
          <template v-if="modal === 'completion'">
            <div
              v-for="proj in details.completionDetails"
              :key="'mc-' + proj.name"
              class="perf-modal__project"
            >
              <div class="perf-modal__project-header" @click="toggleProject('completion', proj.name)">
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ proj.completed }}/{{ proj.total_tasks }} completed
                  </span>
                  <span class="perf-modal__chevron" :class="{ 'perf-modal__chevron--open': isProjectOpen('completion', proj.name) }">&#8250;</span>
                </div>
              </div>
              <transition name="perf-modal-expand">
                <div v-if="isProjectOpen('completion', proj.name)" class="perf-modal__task-table-wrap">
                  <div v-if="proj.tasks.length === 0" class="perf-modal__empty-inline">No completed tasks yet</div>
                  <table v-else class="perf-modal__task-table">
                    <thead>
                      <tr>
                        <th>Task</th>
                        <th>Completed</th>
                        <th>Due Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in proj.tasks" :key="'mct-' + ti">
                        <td>{{ task.title }}</td>
                        <td>{{ task.completed_at }}</td>
                        <td>{{ task.due || '\u2014' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div v-if="details.completionDetails.length === 0" class="perf-modal__empty">No completion data available</div>
          </template>

        </div>
      </div>
    </div>
  </section>
</template>

<script>
import DonutChart from "./DonutChart.vue";
import AreaChart from "./AreaChart.vue";

export default {
  name: "ProjectPerformancePanel",
  components: {
    DonutChart,
    AreaChart,
  },
  props: {
    projectProgress: {
      type: Array,
      required: true,
    },
    productivityByDiscipline: {
      type: Array,
      required: true,
    },
    taskDelayProjects: {
      type: Array,
      required: true,
    },
    taskCompletionProjects: {
      type: Array,
      required: true,
    },
    performanceDetails: {
      type: Object,
      default: function () {
        return {
          progressDetails: [],
          disciplineDetails: [],
          delayDetails: [],
          completionDetails: [],
        };
      },
    },
  },
  data: function () {
    return {
      collapsed: false,
      delayIndex: 0,
      completionIndex: 0,
      modal: null,
      expandedProjects: {},
    };
  },
  computed: {
    activeDelayProject: function () {
      return this.taskDelayProjects[this.delayIndex];
    },
    activeCompletionProject: function () {
      return this.taskCompletionProjects[this.completionIndex];
    },
    details: function () {
      return this.performanceDetails || {
        progressDetails: [],
        disciplineDetails: [],
        delayDetails: [],
        completionDetails: [],
      };
    },
    modalTitle: function () {
      var titles = {
        progress: 'Project Progress \u2014 Task Details',
        discipline: 'Productivity by Discipline \u2014 Task Details',
        delay: 'Task Delay Overview \u2014 Task Details',
        completion: 'Task Completion \u2014 Completed Tasks',
      };
      return titles[this.modal] || '';
    },
  },
  methods: {
    prevDelayProject: function () {
      this.delayIndex =
        (this.delayIndex - 1 + this.taskDelayProjects.length) %
        this.taskDelayProjects.length;
    },
    nextDelayProject: function () {
      this.delayIndex = (this.delayIndex + 1) % this.taskDelayProjects.length;
    },
    prevCompletionProject: function () {
      this.completionIndex =
        (this.completionIndex - 1 + this.taskCompletionProjects.length) %
        this.taskCompletionProjects.length;
    },
    nextCompletionProject: function () {
      this.completionIndex =
        (this.completionIndex + 1) % this.taskCompletionProjects.length;
    },
    openModal: function (type) {
      this.modal = type;
      this.expandedProjects = {};
      document.body.style.overflow = 'hidden';
    },
    closeModal: function () {
      this.modal = null;
      this.expandedProjects = {};
      document.body.style.overflow = '';
    },
    toggleProject: function (section, name) {
      var key = section + ':' + name;
      this.$set(this.expandedProjects, key, !this.expandedProjects[key]);
    },
    isProjectOpen: function (section, name) {
      return !!this.expandedProjects[section + ':' + name];
    },
    progressBadgeClass: function (pct) {
      if (pct >= 75) return 'perf-modal__badge--success';
      if (pct >= 40) return 'perf-modal__badge--warning';
      return 'perf-modal__badge--danger';
    },
    countByCategory: function (tasks, cat) {
      return tasks.filter(function (t) { return t.category === cat; }).length;
    },
  },
};
</script>

<style scoped>
.perf-panel {
  margin-bottom: var(--spacing-xl, 32px);
}

/* Header */
.perf-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg, 24px);
  background: #fcfdff;
  border: 1px solid #eef1f5;
  border-radius: var(--radius-card, 12px);
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
}

.perf-panel__header-text {
  flex: 1;
}

.perf-panel__title {
  font-size: 22px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
}

.perf-panel__collapse-btn {
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

.perf-panel__collapse-btn:hover {
  background-color: var(--bg-page, #f5f6fa);
}

.perf-panel__collapse-icon {
  transition: transform 0.3s ease;
}

.perf-panel__collapse-icon--rotated {
  transform: rotate(180deg);
}

/* Grids */
.perf-panel__top-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-md, 16px);
}

.perf-panel__bottom-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md, 16px);
}

/* Card */
.perf-panel__card {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-lg, 24px);
  transition: box-shadow 0.2s ease, transform 0.15s ease;
}

.perf-panel__card:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.perf-panel__card--clickable {
  cursor: pointer;
  position: relative;
}

.perf-panel__card--clickable:hover {
  transform: translateY(-1px);
}

.perf-panel__card-header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.perf-panel__card-drill {
  color: var(--color-text-muted, #9ca3af);
  opacity: 0;
  transition: opacity 0.2s ease, color 0.2s ease;
  flex-shrink: 0;
  margin-top: 2px;
}

.perf-panel__card--clickable:hover .perf-panel__card-drill {
  opacity: 1;
  color: #c878c8;
}

.perf-panel__card-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0 0 var(--spacing-xs, 4px) 0;
  line-height: 1.35;
  padding: 0;
  border: none;
}

.perf-panel__card-title-underline {
  width: 36px;
  height: 3px;
  background-color: #c878c8;
  border-radius: 2px;
  margin-bottom: var(--spacing-lg, 24px);
}

.perf-panel__card-desc {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  margin: 0 0 var(--spacing-lg, 24px) 0;
  line-height: 1.5;
}

/* Progress Bars */
.perf-panel__bar-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md, 16px);
}

.perf-panel__bar-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.perf-panel__bar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.perf-panel__bar-label {
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  font-weight: 400;
}

.perf-panel__bar-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.perf-panel__bar-track {
  width: 100%;
  height: 6px;
  background-color: #f0f1f5;
  border-radius: 3px;
  overflow: hidden;
}

.perf-panel__bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #c878c8, #d494d4);
  border-radius: 3px;
  transition: width 0.4s ease;
}

/* Project Navigator */
.perf-panel__navigator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-lg, 24px);
}

.perf-panel__nav-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  font-size: 22px;
  line-height: 1;
  color: var(--color-text-secondary, #6b7280);
  transition: color 0.2s ease;
  flex-shrink: 0;
}

.perf-panel__nav-btn:hover {
  color: var(--color-text-primary, #1a1a2e);
}

.perf-panel__nav-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-primary, #1a1a2e);
  text-align: center;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* =============== DRILL-DOWN MODAL =============== */

.perf-modal__backdrop {
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
  animation: perf-fade-in 0.15s ease;
}

@keyframes perf-fade-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.perf-modal {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 780px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  animation: perf-slide-up 0.2s ease;
}

@keyframes perf-slide-up {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

.perf-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #eef1f5;
  flex-shrink: 0;
}

.perf-modal__title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
  padding: 0;
  border: none;
}

.perf-modal__close {
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

.perf-modal__close:hover {
  background: #f0f1f5;
  color: #1a1a2e;
}

.perf-modal__body {
  padding: 16px 24px 24px;
  overflow-y: auto;
  flex: 1;
}

.perf-modal__body::-webkit-scrollbar {
  width: 5px;
}

.perf-modal__body::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

/* Project Row */
.perf-modal__project {
  margin-bottom: 12px;
  border: 1px solid #eef1f5;
  border-radius: 10px;
  overflow: hidden;
}

.perf-modal__project-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.15s;
  gap: 12px;
}

.perf-modal__project-header:hover {
  background: #fafbfd;
}

.perf-modal__project-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a2e;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.perf-modal__project-stats {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  flex-wrap: wrap;
}

.perf-modal__chevron {
  font-size: 16px;
  font-weight: 700;
  color: #9ca3af;
  transition: transform 0.2s ease;
  display: inline-flex;
  margin-left: 4px;
}

.perf-modal__chevron--open {
  transform: rotate(90deg);
}

/* Badges */
.perf-modal__badge {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 10px;
  white-space: nowrap;
}

.perf-modal__badge--success {
  background: #d4edda;
  color: #166534;
}

.perf-modal__badge--warning {
  background: #fef3cd;
  color: #92400e;
}

.perf-modal__badge--danger {
  background: #fde8e8;
  color: #b91c1c;
}

.perf-modal__badge--info {
  background: #e8f0fe;
  color: #1e4a8a;
}

/* Progress bar in modal */
.perf-modal__progress-bar {
  height: 3px;
  background: #f0f1f5;
}

.perf-modal__progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #c878c8, #d494d4);
  transition: width 0.4s ease;
}

/* Task Table */
.perf-modal__task-table-wrap {
  padding: 0 16px 12px;
  background: #fafbfd;
}

.perf-modal__task-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.perf-modal__task-table th {
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 10px 8px 6px;
  border-bottom: 1px solid #eef1f5;
}

.perf-modal__task-table td {
  padding: 8px;
  color: #1a1a2e;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.perf-modal__task-table tbody tr:last-child td {
  border-bottom: none;
}

.perf-modal__task-table tbody tr:hover {
  background: #f5f6fa;
}

/* Status and Category pills */
.perf-modal__status,
.perf-modal__category {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 10px;
  white-space: nowrap;
}

.perf-modal__status--done {
  background: #d4edda;
  color: #166534;
}

.perf-modal__status--open {
  background: #e8f0fe;
  color: #1e4a8a;
}

.perf-modal__status--archived {
  background: #f0f1f5;
  color: #6b7280;
}

.perf-modal__category--on-time {
  background: #d4edda;
  color: #166534;
}

.perf-modal__category--delayed {
  background: #fef3cd;
  color: #92400e;
}

.perf-modal__category--blocked {
  background: #fde8e8;
  color: #b91c1c;
}

.perf-modal__stack-badge {
  font-size: 11px;
  background: #f0f1f5;
  color: #6b7280;
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
}

/* Empty states */
.perf-modal__empty {
  text-align: center;
  padding: 32px 16px;
  color: #9ca3af;
  font-size: 14px;
}

.perf-modal__empty-inline {
  padding: 12px 8px;
  color: #9ca3af;
  font-size: 13px;
  font-style: italic;
}

/* Transition */
.perf-modal-expand-enter-active,
.perf-modal-expand-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.perf-modal-expand-enter,
.perf-modal-expand-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .perf-panel__top-grid,
  .perf-panel__bottom-grid {
    grid-template-columns: 1fr;
  }

  .perf-modal {
    max-width: 100%;
    max-height: 90vh;
  }
}
</style>
