<template>
  <component
    :is="embedded ? 'div' : 'section'"
    :class="['perf-panel', { 'perf-panel--embedded': embedded }]"
  >
    <!-- HEADER (standalone only) -->
    <div
      v-if="!embedded"
      class="perf-panel__header"
      @click="collapsed = !collapsed"
    >
      <h3 class="perf-panel__title">
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
          <line x1="18" y1="20" x2="18" y2="10" />
          <line x1="12" y1="20" x2="12" y2="4" />
          <line x1="6" y1="20" x2="6" y2="14" />
        </svg>
        Project Performance Analytics
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
        class="perf-panel__chevron"
        :class="{ 'perf-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="embedded || !collapsed">
      <!-- TOP ROW: Progress + Productivity -->
      <div class="perf-panel__top-grid">
        <!-- Project Progress Comparison -->
        <div
          class="perf-panel__card perf-panel__card--clickable"
          @click="openModal('progress')"
        >
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Project Progress<br />Comparison
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
                <path
                  d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"
                />
              </svg>
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

        <!-- Member Performance -->
        <div
          class="perf-panel__card perf-panel__card--clickable"
          @click="openModal('member')"
        >
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">Member<br />Performance</h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
                <path
                  d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"
                />
              </svg>
            </span>
          </div>
          <div class="perf-panel__card-title-underline"></div>
          <p class="perf-panel__card-desc">
            Tasks completed per member vs assigned tasks
          </p>
          <div class="perf-panel__member-list">
            <div
              v-for="(item, idx) in memberPerformance"
              :key="'mem-' + idx"
              class="perf-panel__member-item"
            >
              <div class="perf-panel__member-row">
                <div class="perf-panel__member-info">
                  <span class="perf-panel__member-avatar">{{
                    item.name.charAt(0).toUpperCase()
                  }}</span>
                  <span class="perf-panel__bar-label">{{ item.name }}</span>
                </div>
                <span class="perf-panel__member-stats">
                  <span class="perf-panel__member-count"
                    >{{ item.done }}/{{ item.total }}</span
                  >
                  <span class="perf-panel__bar-value"
                    >{{ item.progress }}%</span
                  >
                </span>
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
        <div
          class="perf-panel__card perf-panel__card--clickable"
          @click="openModal('delay')"
        >
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Project Tasks Delay<br />Overview
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
                <path
                  d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"
                />
              </svg>
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
        <div
          class="perf-panel__card perf-panel__card--clickable"
          @click="openModal('completion')"
        >
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Task Completion<br />Over Time
            </h3>
            <span class="perf-panel__card-drill" title="Click for details">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
                <path
                  d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"
                />
              </svg>
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

      <!-- ── TASK BROWSER ── -->
      <div class="perf-panel__task-browser">
        <div class="perf-panel__card">
          <h3 class="perf-panel__card-title">Task Browser</h3>
          <div class="perf-panel__card-title-underline"></div>

          <!-- Filters -->
          <div class="task-browser__filters">
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Task Name</label>
              <input
                v-model="tbFilterName"
                type="text"
                class="task-browser__input"
                placeholder="Search tasks…"
              />
            </div>
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Project</label>
              <select v-model="tbFilterProject" class="task-browser__select">
                <option value="">All Projects</option>
                <option v-for="p in browserProjects" :key="p" :value="p">
                  {{ p }}
                </option>
              </select>
            </div>
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Status</label>
              <select v-model="tbFilterStatus" class="task-browser__select">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="done">Done</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Stack</label>
              <select v-model="tbFilterStack" class="task-browser__select">
                <option value="">All Stacks</option>
                <option v-for="s in browserStacks" :key="s" :value="s">
                  {{ s }}
                </option>
              </select>
            </div>
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Label</label>
              <select v-model="tbFilterLabel" class="task-browser__select">
                <option value="">All Labels</option>
                <option v-for="l in browserLabels" :key="l" :value="l">
                  {{ l }}
                </option>
              </select>
            </div>
            <div class="task-browser__filter-group">
              <label class="task-browser__label">Due</label>
              <select v-model="tbFilterDue" class="task-browser__select">
                <option value="">All</option>
                <option value="overdue">Overdue</option>
                <option value="today">Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="nextSevenDays">Next 7 Days</option>
                <option value="later">Later</option>
                <option value="nodue">No Due Date</option>
              </select>
            </div>
          </div>

          <!-- Results count -->
          <div class="task-browser__count">
            {{ filteredTasks.length }} of {{ browserTasks.length }} tasks
          </div>

          <!-- Table -->
          <div class="task-browser__table-wrap">
            <table class="task-browser__table">
              <thead>
                <tr>
                  <th>Task</th>
                  <th>Project</th>
                  <th>Stack</th>
                  <th>Status</th>
                  <th>Labels</th>
                  <th>Assignees</th>
                  <th>Due Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredTasks.length === 0">
                  <td colspan="7" class="task-browser__empty">
                    No tasks match the current filters
                  </td>
                </tr>
                <tr v-for="task in paginatedTasks" :key="'tb-' + task.id">
                  <td class="task-browser__cell-title">{{ task.title }}</td>
                  <td>{{ task.project }}</td>
                  <td>
                    <span class="perf-modal__stack-badge">{{
                      task.stack
                    }}</span>
                  </td>
                  <td>
                    <span
                      class="perf-modal__status"
                      :class="'perf-modal__status--' + task.status"
                      >{{ task.status }}</span
                    >
                  </td>
                  <td>
                    <span
                      v-for="lbl in task.labels"
                      :key="lbl"
                      class="task-browser__label-badge"
                      >{{ lbl }}</span
                    >
                    <span
                      v-if="task.labels.length === 0"
                      class="task-browser__muted"
                      >&mdash;</span
                    >
                  </td>
                  <td>
                    <span v-if="task.assignees.length">{{
                      task.assignees.join(", ")
                    }}</span>
                    <span v-else class="task-browser__muted">&mdash;</span>
                  </td>
                  <td>
                    <span
                      v-if="task.due"
                      class="task-browser__due"
                      :class="'task-browser__due--' + task.dueBucket"
                      >{{ task.due }}</span
                    >
                    <span v-else class="task-browser__muted">&mdash;</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="task-browser__pagination">
            <button
              class="task-browser__page-btn"
              :disabled="tbPage <= 1"
              @click="tbPage--"
            >
              &lsaquo; Prev
            </button>
            <span class="task-browser__page-info"
              >Page {{ tbPage }} of {{ totalPages }}</span
            >
            <button
              class="task-browser__page-btn"
              :disabled="tbPage >= totalPages"
              @click="tbPage++"
            >
              Next &rsaquo;
            </button>
          </div>
        </div>
      </div>

      <!-- ── PER PROJECT DETAILS ── -->
      <div class="perf-panel__project-details">
        <div class="perf-panel__card">
          <h3 class="perf-panel__card-title">Per Project Details</h3>
          <div class="perf-panel__card-title-underline"></div>
          <ProjectDetailsPanel :projects="projectDetails" />
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
              <div
                class="perf-modal__project-header"
                @click="toggleProject('progress', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ proj.done }}/{{ proj.total }} done
                  </span>
                  <span
                    class="perf-modal__badge"
                    :class="progressBadgeClass(proj.progress)"
                  >
                    {{ proj.progress }}%
                  </span>
                  <span
                    class="perf-modal__chevron"
                    :class="{
                      'perf-modal__chevron--open': isProjectOpen(
                        'progress',
                        proj.name,
                      ),
                    }"
                    >&#8250;</span
                  >
                </div>
              </div>
              <div class="perf-modal__progress-bar">
                <div
                  class="perf-modal__progress-fill"
                  :style="{ width: proj.progress + '%' }"
                ></div>
              </div>
              <transition name="perf-modal-expand">
                <div
                  v-if="isProjectOpen('progress', proj.name)"
                  class="perf-modal__task-table-wrap"
                >
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
                        <td>
                          <span class="perf-modal__stack-badge">{{
                            task.stack
                          }}</span>
                        </td>
                        <td>
                          <span
                            class="perf-modal__status"
                            :class="'perf-modal__status--' + task.status"
                            >{{ task.status }}</span
                          >
                        </td>
                        <td>{{ task.due || "\u2014" }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="details.progressDetails.length === 0"
              class="perf-modal__empty"
            >
              No project data available
            </div>
          </template>

          <!-- Member Detail -->
          <template v-if="modal === 'member'">
            <div
              v-for="mem in details.memberDetails"
              :key="'mm-' + mem.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('member', mem.name)"
              >
                <div class="perf-modal__member-header">
                  <span class="perf-modal__member-avatar">{{
                    mem.name.charAt(0).toUpperCase()
                  }}</span>
                  <span class="perf-modal__project-name">{{ mem.name }}</span>
                </div>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ mem.done }}/{{ mem.total }} done
                  </span>
                  <span
                    class="perf-modal__badge"
                    :class="progressBadgeClass(mem.progress)"
                  >
                    {{ mem.progress }}%
                  </span>
                  <span
                    class="perf-modal__chevron"
                    :class="{
                      'perf-modal__chevron--open': isProjectOpen(
                        'member',
                        mem.name,
                      ),
                    }"
                    >&#8250;</span
                  >
                </div>
              </div>
              <div class="perf-modal__progress-bar">
                <div
                  class="perf-modal__progress-fill"
                  :style="{ width: mem.progress + '%' }"
                ></div>
              </div>
              <transition name="perf-modal-expand">
                <div
                  v-if="isProjectOpen('member', mem.name)"
                  class="perf-modal__task-table-wrap"
                >
                  <table class="perf-modal__task-table">
                    <thead>
                      <tr>
                        <th>Task</th>
                        <th>Project</th>
                        <th>Stack</th>
                        <th>Status</th>
                        <th>Due Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in mem.tasks" :key="'mmt-' + ti">
                        <td>{{ task.title }}</td>
                        <td>{{ task.project }}</td>
                        <td>
                          <span class="perf-modal__stack-badge">{{
                            task.stack
                          }}</span>
                        </td>
                        <td>
                          <span
                            class="perf-modal__status"
                            :class="'perf-modal__status--' + task.status"
                            >{{ task.status }}</span
                          >
                        </td>
                        <td>{{ task.due || "\u2014" }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="details.memberDetails.length === 0"
              class="perf-modal__empty"
            >
              No member assignment data available
            </div>
          </template>

          <!-- Delay Detail -->
          <template v-if="modal === 'delay'">
            <div
              v-for="proj in details.delayDetails"
              :key="'mdl-' + proj.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('delay', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--success">
                    {{ countByCategory(proj.tasks, "on-time") }} on-time
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--warning">
                    {{ countByCategory(proj.tasks, "delayed") }} delayed
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--danger">
                    {{ countByCategory(proj.tasks, "blocked") }} blocked
                  </span>
                  <span
                    class="perf-modal__chevron"
                    :class="{
                      'perf-modal__chevron--open': isProjectOpen(
                        'delay',
                        proj.name,
                      ),
                    }"
                    >&#8250;</span
                  >
                </div>
              </div>
              <transition name="perf-modal-expand">
                <div
                  v-if="isProjectOpen('delay', proj.name)"
                  class="perf-modal__task-table-wrap"
                >
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
                        <td>
                          <span class="perf-modal__stack-badge">{{
                            task.stack
                          }}</span>
                        </td>
                        <td>
                          <span
                            class="perf-modal__status"
                            :class="'perf-modal__status--' + task.status"
                            >{{ task.status }}</span
                          >
                        </td>
                        <td>
                          <span
                            class="perf-modal__category"
                            :class="'perf-modal__category--' + task.category"
                          >
                            {{ task.category }}
                            <template v-if="task.days_overdue">
                              ({{ task.days_overdue }}d)</template
                            >
                          </span>
                        </td>
                        <td>{{ task.due || "\u2014" }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="details.delayDetails.length === 0"
              class="perf-modal__empty"
            >
              No delay data available
            </div>
          </template>

          <!-- Completion Detail -->
          <template v-if="modal === 'completion'">
            <div
              v-for="proj in details.completionDetails"
              :key="'mc-' + proj.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('completion', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <span class="perf-modal__badge perf-modal__badge--info">
                    {{ proj.completed }}/{{ proj.total_tasks }} completed
                  </span>
                  <span
                    class="perf-modal__chevron"
                    :class="{
                      'perf-modal__chevron--open': isProjectOpen(
                        'completion',
                        proj.name,
                      ),
                    }"
                    >&#8250;</span
                  >
                </div>
              </div>
              <transition name="perf-modal-expand">
                <div
                  v-if="isProjectOpen('completion', proj.name)"
                  class="perf-modal__task-table-wrap"
                >
                  <div
                    v-if="proj.tasks.length === 0"
                    class="perf-modal__empty-inline"
                  >
                    No completed tasks yet
                  </div>
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
                        <td>{{ task.due || "\u2014" }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="details.completionDetails.length === 0"
              class="perf-modal__empty"
            >
              No completion data available
            </div>
          </template>
        </div>
      </div>
    </div>
  </component>
</template>

<script>
import DonutChart from "./DonutChart.vue";
import AreaChart from "./AreaChart.vue";
import ProjectDetailsPanel from "./ProjectDetailsPanel.vue";

export default {
  name: "ProjectPerformancePanel",
  components: {
    DonutChart,
    AreaChart,
    ProjectDetailsPanel,
  },
  props: {
    embedded: {
      type: Boolean,
      default: false,
    },
    projectProgress: {
      type: Array,
      required: true,
    },
    memberPerformance: {
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
          memberDetails: [],
          delayDetails: [],
          completionDetails: [],
        };
      },
    },
    taskBrowser: {
      type: Object,
      default: function () {
        return { tasks: [], projects: [], stacks: [], labels: [] };
      },
    },
    projectDetails: {
      type: Array,
      default: function () {
        return [];
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
      // Task browser state
      tbFilterName: "",
      tbFilterProject: "",
      tbFilterStatus: "",
      tbFilterStack: "",
      tbFilterLabel: "",
      tbFilterDue: "",
      tbPage: 1,
      tbPageSize: 15,
    };
  },
  watch: {
    tbFilterName: function () {
      this.tbPage = 1;
    },
    tbFilterProject: function () {
      this.tbPage = 1;
    },
    tbFilterStatus: function () {
      this.tbPage = 1;
    },
    tbFilterStack: function () {
      this.tbPage = 1;
    },
    tbFilterLabel: function () {
      this.tbPage = 1;
    },
    tbFilterDue: function () {
      this.tbPage = 1;
    },
  },
  computed: {
    browserTasks: function () {
      return (this.taskBrowser && this.taskBrowser.tasks) || [];
    },
    browserProjects: function () {
      return (this.taskBrowser && this.taskBrowser.projects) || [];
    },
    browserStacks: function () {
      return (this.taskBrowser && this.taskBrowser.stacks) || [];
    },
    browserLabels: function () {
      return (this.taskBrowser && this.taskBrowser.labels) || [];
    },
    filteredTasks: function () {
      var self = this;
      return this.browserTasks.filter(function (t) {
        if (
          self.tbFilterName &&
          t.title.toLowerCase().indexOf(self.tbFilterName.toLowerCase()) === -1
        )
          return false;
        if (self.tbFilterProject && t.project !== self.tbFilterProject)
          return false;
        if (self.tbFilterStatus && t.status !== self.tbFilterStatus)
          return false;
        if (self.tbFilterStack && t.stack !== self.tbFilterStack) return false;
        if (self.tbFilterLabel && t.labels.indexOf(self.tbFilterLabel) === -1)
          return false;
        if (self.tbFilterDue && t.dueBucket !== self.tbFilterDue) return false;
        return true;
      });
    },
    totalPages: function () {
      return Math.max(
        1,
        Math.ceil(this.filteredTasks.length / this.tbPageSize),
      );
    },
    paginatedTasks: function () {
      var start = (this.tbPage - 1) * this.tbPageSize;
      return this.filteredTasks.slice(start, start + this.tbPageSize);
    },
    activeDelayProject: function () {
      return this.taskDelayProjects[this.delayIndex];
    },
    activeCompletionProject: function () {
      return this.taskCompletionProjects[this.completionIndex];
    },
    details: function () {
      return (
        this.performanceDetails || {
          progressDetails: [],
          memberDetails: [],
          delayDetails: [],
          completionDetails: [],
        }
      );
    },
    modalTitle: function () {
      var titles = {
        progress: "Project Progress \u2014 Task Details",
        member: "Member Performance \u2014 Task Details",
        delay: "Task Delay Overview \u2014 Task Details",
        completion: "Task Completion \u2014 Completed Tasks",
      };
      return titles[this.modal] || "";
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
      document.body.style.overflow = "hidden";
    },
    closeModal: function () {
      this.modal = null;
      this.expandedProjects = {};
      document.body.style.overflow = "";
    },
    toggleProject: function (section, name) {
      var key = section + ":" + name;
      this.$set(this.expandedProjects, key, !this.expandedProjects[key]);
    },
    isProjectOpen: function (section, name) {
      return !!this.expandedProjects[section + ":" + name];
    },
    progressBadgeClass: function (pct) {
      if (pct >= 75) return "perf-modal__badge--success";
      if (pct >= 40) return "perf-modal__badge--warning";
      return "perf-modal__badge--danger";
    },
    countByCategory: function (tasks, cat) {
      return tasks.filter(function (t) {
        return t.category === cat;
      }).length;
    },
  },
};
</script>

<style scoped>
.perf-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.perf-panel--embedded {
  background: none;
  border-radius: 0;
  box-shadow: none;
  margin-bottom: 0;
  overflow: visible;
}

/* Header */
.perf-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.perf-panel__header:hover {
  background: #fafbfd;
}

.perf-panel__title {
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

.perf-panel__title svg {
  color: #c878c8;
}

.perf-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.perf-panel__chevron--rotated {
  transform: rotate(180deg);
}

/* Grids */
.perf-panel__top-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md, 16px);
  margin-bottom: var(--spacing-md, 16px);
  padding: 0 var(--spacing-lg, 24px);
}

.perf-panel__bottom-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md, 16px);
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
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

/* Member Performance list */
.perf-panel__member-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md, 16px);
}

.perf-panel__member-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.perf-panel__member-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.perf-panel__member-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.perf-panel__member-avatar {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: linear-gradient(135deg, #c878c8, #d494d4);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.perf-panel__member-stats {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.perf-panel__member-count {
  font-size: 11px;
  font-weight: 500;
  color: var(--color-text-muted, #9ca3af);
}

/* Modal member header */
.perf-modal__member-header {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.perf-modal__member-avatar {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: linear-gradient(135deg, #c878c8, #d494d4);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
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
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
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
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

/* ─── Task Browser ─── */
.perf-panel__task-browser {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

.task-browser__filters {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.task-browser__filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.task-browser__label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.task-browser__input,
.task-browser__select {
  padding: 7px 10px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
}

.task-browser__input:focus,
.task-browser__select:focus {
  border-color: #c878c8;
}

.task-browser__count {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
  margin-bottom: 8px;
}

.task-browser__table-wrap {
  overflow-x: auto;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
}

.task-browser__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.task-browser__table th {
  text-align: left;
  padding: 10px 12px;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  background: #fafbfd;
  border-bottom: 1px solid var(--color-border, #e5e7eb);
  white-space: nowrap;
}

.task-browser__table td {
  padding: 9px 12px;
  border-bottom: 1px solid #f3f4f6;
  color: var(--color-text-primary, #1a1a2e);
  vertical-align: middle;
}

.task-browser__table tbody tr:last-child td {
  border-bottom: none;
}

.task-browser__table tbody tr:hover {
  background: #fafbfd;
}

.task-browser__cell-title {
  font-weight: 500;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-browser__label-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  background: #ede9fe;
  color: #6d28d9;
  margin-right: 4px;
  white-space: nowrap;
}

.task-browser__muted {
  color: var(--color-text-muted, #9ca3af);
}

.task-browser__due--overdue {
  color: #d94040;
  font-weight: 600;
}

.task-browser__due--today {
  color: #b8860b;
  font-weight: 600;
}

.task-browser__due--tomorrow {
  color: #e67e5a;
}

.task-browser__empty {
  text-align: center;
  padding: 24px 12px;
  color: #9ca3af;
  font-style: italic;
}

.task-browser__pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding-top: 14px;
}

.task-browser__page-btn {
  padding: 5px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: #fff;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}

.task-browser__page-btn:hover:not(:disabled) {
  background: #fafbfd;
  border-color: #c878c8;
}

.task-browser__page-btn:disabled {
  opacity: 0.4;
  cursor: default;
}

.task-browser__page-info {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
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

  .task-browser__filters {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .task-browser__filters {
    grid-template-columns: 1fr;
  }
}
</style>
