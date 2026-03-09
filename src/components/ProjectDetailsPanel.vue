<template>
  <div class="proj-details">
    <!-- ── #4: Project Selector → Tabbed pill strip ── -->
    <div class="proj-details__tabs">
      <div class="proj-details__tabs-toolbar">
        <input
          v-model="tabSearch"
          type="text"
          class="proj-details__tabs-search-input"
          placeholder="Search projects…"
        />
        <select
          v-model="tabStatusFilter"
          class="proj-details__tabs-status-select"
        >
          <option value="">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Waiting on Customer">Waiting on Customer</option>
          <option value="On Hold">On Hold</option>
          <option value="Done">Done</option>
        </select>
        <select
          v-model="tabTaskDueFilter"
          class="proj-details__tabs-status-select"
        >
          <option value="">All Task Due</option>
          <option value="overdue">Has Overdue</option>
          <option value="today">Has Due Today</option>
          <option value="nextSevenDays">Has Upcoming</option>
          <option value="nodue">Has No Due Date</option>
        </select>
        <select
          v-model="tabTaskStatusFilter"
          class="proj-details__tabs-status-select"
        >
          <option value="">All Task Status</option>
          <option value="open">Has Open Tasks</option>
          <option value="done">Has Done Tasks</option>
        </select>
        <button
          v-if="
            tabSearch ||
            tabStatusFilter ||
            tabTaskDueFilter ||
            tabTaskStatusFilter
          "
          class="proj-details__tabs-clear"
          @click="clearProjectFilters"
        >
          ✕ Clear
        </button>
      </div>
      <div class="proj-details__tabs-strip">
        <button
          v-for="p in visibleProjects"
          :key="p.id"
          class="proj-details__tab"
          :class="{ 'proj-details__tab--active': selectedProjectId === p.id }"
          @click="selectedProjectId = p.id"
        >
          <span
            class="proj-details__tab-dot"
            :class="
              'proj-details__tab-dot--' +
              p.statusLabel.toLowerCase().replace(/ /g, '-')
            "
          ></span>
          <span class="proj-details__tab-name">{{ p.name }}</span>
          <span v-if="p.number" class="proj-details__tab-num">{{
            p.number
          }}</span>
        </button>
        <span v-if="projects.length === 0" class="proj-details__tabs-empty">
          No projects
        </span>
      </div>
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
      <!-- ── #5: Row 1 — Split Info: Project Overview + Client Contact ── -->
      <div class="proj-details__row proj-details__row--two">
        <!-- Project Overview -->
        <div class="proj-details__card">
          <div class="proj-details__card-header">
            <h4 class="proj-details__card-title">Project Overview</h4>
            <span
              class="proj-details__badge"
              :class="
                'proj-details__badge--' +
                selectedProject.statusLabel.toLowerCase().replace(/ /g, '-')
              "
              >{{ selectedProject.statusLabel }}</span
            >
          </div>
          <div class="proj-details__info-grid">
            <div class="proj-details__info-item">
              <span class="proj-details__info-label">Project Name</span>
              <span class="proj-details__info-value">{{
                selectedProject.name
              }}</span>
            </div>
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
          <!-- Compact Completion bar inside overview card -->
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
        </div>

        <!-- Client Contact + Resources -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Client &amp; Resources</h4>
          <div class="proj-details__info-grid" v-if="hasClientInfo">
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
              <a
                :href="'mailto:' + selectedProject.clientEmail"
                class="proj-details__info-link"
                >{{ selectedProject.clientEmail }}</a
              >
            </div>
            <div
              class="proj-details__info-item"
              v-if="selectedProject.clientPhone"
            >
              <span class="proj-details__info-label">Phone</span>
              <a
                :href="'tel:' + selectedProject.clientPhone"
                class="proj-details__info-link"
                >{{ selectedProject.clientPhone }}</a
              >
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
          </div>
          <div v-else class="proj-details__no-client">
            No client information
          </div>
          <!-- Resource summary -->
          <h4 class="proj-details__card-title proj-details__card-title--sub">
            Resources
          </h4>
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
          </div>
        </div>
      </div>

      <!-- ── #2 & #3: Row 2 — Donut (no table) + Due Dates + Team Workload ── -->
      <div class="proj-details__row proj-details__row--three">
        <!-- Task Distribution by Stack (donut only, no table) -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Task Distribution</h4>
          <div
            v-if="stackChartData.data.length > 0"
            class="proj-details__chart-wrap"
          >
            <DonutChart :chart-data="stackChartData" />
          </div>
          <div v-else class="proj-details__no-data">No tasks found</div>
        </div>

        <!-- Due Date Breakdown (standalone) -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Due Dates</h4>
          <div class="proj-details__due-grid">
            <div
              class="proj-details__due-item"
              :class="{
                'proj-details__due-item--danger': true,
                'proj-details__due-item--pulse':
                  selectedProject.dueStats.overdue > 0,
              }"
            >
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
        </div>

        <!-- #2: Team Workload (separated assignees with progress bars) -->
        <div class="proj-details__card">
          <h4 class="proj-details__card-title">Team Workload</h4>
          <div
            v-if="selectedProject.assignees.length > 0"
            class="proj-details__team-list"
          >
            <div
              v-for="(a, i) in selectedProject.assignees"
              :key="i"
              class="proj-details__team-member"
            >
              <div class="proj-details__team-row">
                <div class="proj-details__team-info">
                  <span class="proj-details__team-avatar">{{
                    a.userId.charAt(0).toUpperCase()
                  }}</span>
                  <span class="proj-details__team-name">{{ a.userId }}</span>
                </div>
                <span class="proj-details__team-stats">
                  <span class="proj-details__team-count"
                    >{{ a.doneTasks || 0 }}/{{ a.tasks }}</span
                  >
                  <span class="proj-details__team-pct"
                    >{{ assigneePct(a) }}%</span
                  >
                </span>
              </div>
              <div class="proj-details__team-bar">
                <div
                  class="proj-details__team-bar-fill"
                  :style="{ width: assigneePct(a) + '%' }"
                ></div>
              </div>
            </div>
          </div>
          <div v-else class="proj-details__no-data">No team assignments</div>
        </div>
      </div>

      <!-- ── Row 2b — Per-project Timeline KPI ── -->
      <div
        class="proj-details__row proj-details__row--three proj-details__row--kpi"
      >
        <div class="proj-details__kpi-stat">
          <span class="proj-details__kpi-stat-value"
            >{{ selectedProject.completionPct }}%</span
          >
          <span class="proj-details__kpi-stat-label">Completion Rate</span>
          <div class="proj-details__kpi-bar">
            <div
              class="proj-details__kpi-bar-fill"
              :style="{ width: selectedProject.completionPct + '%' }"
              :class="{
                'proj-details__kpi-bar-fill--low':
                  selectedProject.completionPct < 25,
                'proj-details__kpi-bar-fill--mid':
                  selectedProject.completionPct >= 25 &&
                  selectedProject.completionPct < 75,
                'proj-details__kpi-bar-fill--high':
                  selectedProject.completionPct >= 75,
              }"
            ></div>
          </div>
          <span class="proj-details__kpi-stat-sub"
            >{{ selectedProject.doneTasks }}/{{
              selectedProject.totalTasks
            }}
            tasks</span
          >
        </div>
        <div class="proj-details__kpi-stat">
          <span class="proj-details__kpi-stat-value">{{
            coordinationPending
          }}</span>
          <span class="proj-details__kpi-stat-label">Coordination Pending</span>
          <span class="proj-details__kpi-stat-sub"
            >Weeks since Request Date</span
          >
        </div>
        <div class="proj-details__kpi-stat">
          <span class="proj-details__kpi-stat-value">{{
            requiredPrepTime
          }}</span>
          <span class="proj-details__kpi-stat-label">Required Prep Time</span>
          <span class="proj-details__kpi-stat-sub"
            >Planned preparation period</span
          >
        </div>
      </div>

      <!-- ── #1: Row 3 — Gantt Timeline ── -->
      <div v-if="selectedProject.timeline.length > 0" class="proj-details__row">
        <div class="proj-details__card proj-details__card--full">
          <h4 class="proj-details__card-title">Project Timeline</h4>
          <TimelineChart :timeline="selectedProject.timeline" />
        </div>
      </div>

      <!-- ── #6: Row 4 — Task Browser with summary strip + sorting ── -->
      <div class="proj-details__row">
        <div class="proj-details__card proj-details__card--full">
          <h4 class="proj-details__card-title">Task Browser</h4>

          <!-- Urgency summary strip -->
          <div class="proj-details__tb-summary">
            <button
              class="proj-details__tb-summary-pill proj-details__tb-summary-pill--danger"
              :class="{
                'proj-details__tb-summary-pill--active':
                  tbFilterDue === 'overdue',
              }"
              @click="toggleDueFilter('overdue')"
            >
              <span
                class="proj-details__tb-summary-dot proj-details__tb-summary-dot--danger"
              ></span>
              {{ dueCounts.overdue }} Overdue
            </button>
            <button
              class="proj-details__tb-summary-pill proj-details__tb-summary-pill--warning"
              :class="{
                'proj-details__tb-summary-pill--active':
                  tbFilterDue === 'today',
              }"
              @click="toggleDueFilter('today')"
            >
              <span
                class="proj-details__tb-summary-dot proj-details__tb-summary-dot--warning"
              ></span>
              {{ dueCounts.today }} Due Today
            </button>
            <button
              class="proj-details__tb-summary-pill proj-details__tb-summary-pill--success"
              :class="{
                'proj-details__tb-summary-pill--active':
                  tbFilterStatus === 'done',
              }"
              @click="toggleStatusFilter('done')"
            >
              <span
                class="proj-details__tb-summary-dot proj-details__tb-summary-dot--success"
              ></span>
              {{ statusCounts.done }} Done
            </button>
            <button
              class="proj-details__tb-summary-pill proj-details__tb-summary-pill--info"
              :class="{
                'proj-details__tb-summary-pill--active':
                  tbFilterStatus === 'open',
              }"
              @click="toggleStatusFilter('open')"
            >
              <span
                class="proj-details__tb-summary-dot proj-details__tb-summary-dot--info"
              ></span>
              {{ statusCounts.open }} Open
            </button>
          </div>

          <!-- Filters -->
          <div class="proj-details__tb-filters">
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Search</label>
              <input
                v-model="tbFilterName"
                type="text"
                class="proj-details__tb-input"
                placeholder="Task name…"
              />
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Status</label>
              <select v-model="tbFilterStatus" class="proj-details__tb-select">
                <option value="">All</option>
                <option value="open">Open</option>
                <option value="done">Done</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Stack</label>
              <select v-model="tbFilterStack" class="proj-details__tb-select">
                <option value="">All</option>
                <option v-for="s in projectStacks" :key="s" :value="s">
                  {{ s }}
                </option>
              </select>
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Label</label>
              <select v-model="tbFilterLabel" class="proj-details__tb-select">
                <option value="">All</option>
                <option v-for="l in projectLabels" :key="l" :value="l">
                  {{ l }}
                </option>
              </select>
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Assignee</label>
              <select
                v-model="tbFilterAssignee"
                class="proj-details__tb-select"
              >
                <option value="">All</option>
                <option v-for="a in projectAssigneeNames" :key="a" :value="a">
                  {{ a }}
                </option>
              </select>
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Due</label>
              <select v-model="tbFilterDue" class="proj-details__tb-select">
                <option value="">All</option>
                <option value="overdue">Overdue</option>
                <option value="today">Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="nextSevenDays">Next 7 Days</option>
                <option value="later">Later</option>
                <option value="nodue">No Due Date</option>
              </select>
            </div>
            <div class="proj-details__tb-filter">
              <label class="proj-details__tb-label">Opened</label>
              <div
                class="proj-details__date-range"
                @click="showDateRangePicker = !showDateRangePicker"
              >
                <span class="proj-details__date-range-value">
                  {{ tbFilterDateFrom || "Start" }}
                </span>
                <span class="proj-details__date-range-arrow">→</span>
                <span class="proj-details__date-range-value">
                  {{ tbFilterDateTo || "End" }}
                </span>
                <button
                  v-if="tbFilterDateFrom || tbFilterDateTo"
                  class="proj-details__date-range-clear"
                  title="Clear dates"
                  @click.stop="
                    tbFilterDateFrom = '';
                    tbFilterDateTo = '';
                    showDateRangePicker = false;
                  "
                >
                  ✕
                </button>
              </div>
              <div
                v-if="showDateRangePicker"
                v-click-outside="
                  function () {
                    showDateRangePicker = false;
                  }
                "
                class="proj-details__date-picker-dropdown"
              >
                <div class="proj-details__date-picker-months">
                  <div class="proj-details__date-picker-month">
                    <div class="proj-details__date-picker-header">
                      <button
                        class="proj-details__date-picker-nav"
                        @click.stop="shiftCalendar(-1)"
                      >
                        ‹
                      </button>
                      <span class="proj-details__date-picker-title">{{
                        calendarMonthLabel(0)
                      }}</span>
                      <span></span>
                    </div>
                    <div class="proj-details__date-picker-grid">
                      <span
                        v-for="d in ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']"
                        :key="d"
                        class="proj-details__date-picker-dow"
                        >{{ d }}</span
                      >
                      <span
                        v-for="(cell, ci) in calendarCells(0)"
                        :key="'L' + ci"
                        :class="dateCellClass(cell)"
                        @click.stop="cell.date && pickDate(cell.date)"
                        >{{ cell.day }}</span
                      >
                    </div>
                  </div>
                  <div class="proj-details__date-picker-month">
                    <div class="proj-details__date-picker-header">
                      <span></span>
                      <span class="proj-details__date-picker-title">{{
                        calendarMonthLabel(1)
                      }}</span>
                      <button
                        class="proj-details__date-picker-nav"
                        @click.stop="shiftCalendar(1)"
                      >
                        ›
                      </button>
                    </div>
                    <div class="proj-details__date-picker-grid">
                      <span
                        v-for="d in ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']"
                        :key="d"
                        class="proj-details__date-picker-dow"
                        >{{ d }}</span
                      >
                      <span
                        v-for="(cell, ci) in calendarCells(1)"
                        :key="'R' + ci"
                        :class="dateCellClass(cell)"
                        @click.stop="cell.date && pickDate(cell.date)"
                        >{{ cell.day }}</span
                      >
                    </div>
                  </div>
                </div>
                <div class="proj-details__date-picker-footer">
                  <button
                    class="proj-details__date-picker-btn"
                    @click.stop="
                      tbFilterDateFrom = '';
                      tbFilterDateTo = '';
                      datePickStep = 'from';
                    "
                  >
                    Clear
                  </button>
                  <button
                    class="proj-details__date-picker-btn proj-details__date-picker-btn--apply"
                    @click.stop="showDateRangePicker = false"
                  >
                    Apply
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Count -->
          <div class="proj-details__tb-count">
            {{ filteredTasks.length }} of {{ projectTasks.length }} tasks
          </div>

          <!-- Table with sortable headers -->
          <div class="proj-details__tb-table-wrap">
            <table class="proj-details__table proj-details__tb-table">
              <thead>
                <tr>
                  <th
                    class="proj-details__th-sort"
                    :class="{
                      'proj-details__th-sort--active': tbSortKey === 'title',
                    }"
                    @click="toggleSort('title')"
                  >
                    Task
                    <span class="proj-details__sort-arrow">{{
                      sortArrow("title")
                    }}</span>
                  </th>
                  <th>Stack</th>
                  <th
                    class="proj-details__th-sort"
                    :class="{
                      'proj-details__th-sort--active': tbSortKey === 'status',
                    }"
                    @click="toggleSort('status')"
                  >
                    Status
                    <span class="proj-details__sort-arrow">{{
                      sortArrow("status")
                    }}</span>
                  </th>
                  <th>Labels</th>
                  <th>Assignees</th>
                  <th
                    class="proj-details__th-sort"
                    :class="{
                      'proj-details__th-sort--active': tbSortKey === 'due',
                    }"
                    @click="toggleSort('due')"
                  >
                    Due Date
                    <span class="proj-details__sort-arrow">{{
                      sortArrow("due")
                    }}</span>
                  </th>
                  <th
                    class="proj-details__th-sort"
                    :class="{
                      'proj-details__th-sort--active': tbSortKey === 'age',
                    }"
                    @click="toggleSort('age')"
                  >
                    Opened
                    <span class="proj-details__sort-arrow">{{
                      sortArrow("age")
                    }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredTasks.length === 0">
                  <td colspan="7" class="proj-details__tb-empty">
                    No tasks match the current filters
                  </td>
                </tr>
                <tr v-for="task in paginatedTasks" :key="'tb-' + task.id">
                  <td class="proj-details__tb-cell-title">{{ task.title }}</td>
                  <td>
                    <span class="proj-details__tb-stack-badge">{{
                      task.stack
                    }}</span>
                  </td>
                  <td>
                    <span
                      class="proj-details__tb-status"
                      :class="'proj-details__tb-status--' + task.status"
                      >{{ task.status }}</span
                    >
                  </td>
                  <td>
                    <span
                      v-for="lbl in task.labels"
                      :key="lbl"
                      class="proj-details__tb-label-badge"
                      >{{ lbl }}</span
                    >
                    <span
                      v-if="task.labels.length === 0"
                      class="proj-details__tb-muted"
                      >&mdash;</span
                    >
                  </td>
                  <td>
                    <span v-if="task.assignees.length">{{
                      task.assignees.join(", ")
                    }}</span>
                    <span v-else class="proj-details__tb-muted">&mdash;</span>
                  </td>
                  <td>
                    <span
                      v-if="task.due"
                      class="proj-details__tb-due"
                      :class="'proj-details__tb-due--' + task.dueBucket"
                      >{{ formatDate(task.due) }}</span
                    >
                    <span v-else class="proj-details__tb-muted">&mdash;</span>
                  </td>
                  <td>
                    <span
                      v-if="task.createdAt"
                      class="proj-details__tb-age"
                      :title="formatDate(task.createdAt)"
                    >
                      {{ taskAge(task.createdAt) }}
                    </span>
                    <span v-else class="proj-details__tb-muted">&mdash;</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="proj-details__tb-pagination">
            <button
              class="proj-details__tb-page-btn"
              :disabled="tbPage <= 1"
              @click="tbPage--"
            >
              &lsaquo; Prev
            </button>
            <span class="proj-details__tb-page-info"
              >Page {{ tbPage }} of {{ totalPages }}</span
            >
            <button
              class="proj-details__tb-page-btn"
              :disabled="tbPage >= totalPages"
              @click="tbPage++"
            >
              Next &rsaquo;
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import DonutChart from "./DonutChart.vue";
import TimelineChart from "./TimelineChart.vue";

export default {
  name: "ProjectDetailsPanel",
  components: { DonutChart, TimelineChart },
  directives: {
    "click-outside": {
      bind: function (el, binding) {
        el.__clickOutsideHandler = function (e) {
          if (!el.contains(e.target)) {
            binding.value(e);
          }
        };
        document.addEventListener("pointerdown", el.__clickOutsideHandler);
      },
      unbind: function (el) {
        document.removeEventListener("pointerdown", el.__clickOutsideHandler);
        delete el.__clickOutsideHandler;
      },
    },
  },
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
      tabSearch: "",
      tabStatusFilter: "",
      tabTaskDueFilter: "",
      tabTaskStatusFilter: "",
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
      // Task browser
      tbFilterName: "",
      tbFilterStatus: "",
      tbFilterStack: "",
      tbFilterLabel: "",
      tbFilterAssignee: "",
      tbFilterDue: "",
      tbFilterDateFrom: "",
      tbFilterDateTo: "",
      showDateRangePicker: false,
      datePickStep: "from",
      calendarBase: new Date(),
      tbPage: 1,
      tbPageSize: 15,
      tbSortKey: "",
      tbSortDir: "asc",
    };
  },
  watch: {
    selectedProjectId: function () {
      this.resetFilters();
    },
    tbFilterName: function () {
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
    tbFilterAssignee: function () {
      this.tbPage = 1;
    },
    tbFilterDue: function () {
      this.tbPage = 1;
    },
    tbFilterDateFrom: function () {
      this.tbPage = 1;
    },
    tbFilterDateTo: function () {
      this.tbPage = 1;
    },
  },
  computed: {
    // Tab strip
    visibleProjects: function () {
      var self = this;
      var list = this.projects;
      if (this.tabStatusFilter) {
        list = list.filter(function (p) {
          return p.statusLabel === self.tabStatusFilter;
        });
      }
      if (this.tabTaskDueFilter) {
        var dueVal = this.tabTaskDueFilter;
        list = list.filter(function (p) {
          var tasks = p.tasks || [];
          for (var i = 0; i < tasks.length; i++) {
            if (tasks[i].dueBucket === dueVal) return true;
          }
          return false;
        });
      }
      if (this.tabTaskStatusFilter) {
        var statusVal = this.tabTaskStatusFilter;
        list = list.filter(function (p) {
          var tasks = p.tasks || [];
          for (var i = 0; i < tasks.length; i++) {
            if (tasks[i].status === statusVal) return true;
          }
          return false;
        });
      }
      if (this.tabSearch) {
        var q = this.tabSearch.toLowerCase();
        list = list.filter(function (p) {
          return (
            p.name.toLowerCase().indexOf(q) !== -1 ||
            (p.number && p.number.toLowerCase().indexOf(q) !== -1)
          );
        });
      }
      return list;
    },
    selectedProject: function () {
      if (!this.selectedProjectId) return null;
      var id = Number(this.selectedProjectId);
      return (
        this.projects.find(function (p) {
          return p.id === id;
        }) || null
      );
    },
    hasClientInfo: function () {
      if (!this.selectedProject) return false;
      var p = this.selectedProject;
      return !!(
        p.clientName ||
        p.clientEmail ||
        p.clientPhone ||
        (p.location && p.location !== ",")
      );
    },
    // Stack donut — build single chartData object
    stackChartData: function () {
      if (!this.selectedProject) return { labels: [], data: [], colors: [] };
      var self = this;
      var filtered = this.selectedProject.tasksByStack.filter(function (s) {
        return s.total > 0;
      });
      return {
        labels: filtered.map(function (s) {
          return s.stack;
        }),
        data: filtered.map(function (s) {
          return s.total;
        }),
        colors: filtered.map(function (_, i) {
          return self.stackColors[i % self.stackColors.length];
        }),
      };
    },
    // Task browser
    projectTasks: function () {
      if (!this.selectedProject) return [];
      return this.selectedProject.tasks || [];
    },
    projectStacks: function () {
      if (!this.selectedProject) return [];
      return this.selectedProject.taskStacks || [];
    },
    projectLabels: function () {
      if (!this.selectedProject) return [];
      return this.selectedProject.taskLabels || [];
    },
    // Unique assignee names for the filter dropdown
    projectAssigneeNames: function () {
      var names = {};
      this.projectTasks.forEach(function (t) {
        (t.assignees || []).forEach(function (a) {
          names[a] = true;
        });
      });
      return Object.keys(names).sort();
    },
    // Per-project Timeline KPIs
    coordinationPending: function () {
      if (!this.selectedProject) return "—";
      var tl = this.selectedProject.timeline || [];
      var item = null;
      for (var i = 0; i < tl.length; i++) {
        if (tl[i].systemKey === "request_date") {
          item = tl[i];
          break;
        }
      }
      if (!item || !item.startDate) return "—";
      var start = new Date(item.startDate);
      if (isNaN(start.getTime())) return "—";
      var now = new Date();
      var days = Math.floor(
        (now.getTime() - start.getTime()) / (1000 * 60 * 60 * 24),
      );
      var weeks = Math.round(days / 7);
      return weeks + " wk" + (weeks !== 1 ? "s" : "");
    },
    requiredPrepTime: function () {
      if (!this.selectedProject) return "—";
      var tl = this.selectedProject.timeline || [];
      var item = null;
      for (var i = 0; i < tl.length; i++) {
        if (tl[i].systemKey === "required_preparation") {
          item = tl[i];
          break;
        }
      }
      if (!item || !item.startDate || !item.endDate) return "—";
      var s = new Date(item.startDate);
      var e = new Date(item.endDate);
      if (isNaN(s.getTime()) || isNaN(e.getTime())) return "—";
      var days = Math.floor(
        (e.getTime() - s.getTime()) / (1000 * 60 * 60 * 24),
      );
      var weeks = Math.round(days / 7);
      return weeks + " wk" + (weeks !== 1 ? "s" : "");
    },
    // Summary counts for the urgency strip
    dueCounts: function () {
      var counts = { overdue: 0, today: 0 };
      this.projectTasks.forEach(function (t) {
        if (t.dueBucket === "overdue") counts.overdue++;
        if (t.dueBucket === "today") counts.today++;
      });
      return counts;
    },
    statusCounts: function () {
      var counts = { open: 0, done: 0 };
      this.projectTasks.forEach(function (t) {
        if (t.status === "open") counts.open++;
        if (t.status === "done") counts.done++;
      });
      return counts;
    },
    filteredTasks: function () {
      var self = this;
      return this.projectTasks.filter(function (t) {
        if (
          self.tbFilterName &&
          t.title.toLowerCase().indexOf(self.tbFilterName.toLowerCase()) === -1
        )
          return false;
        if (self.tbFilterStatus && t.status !== self.tbFilterStatus)
          return false;
        if (self.tbFilterStack && t.stack !== self.tbFilterStack) return false;
        if (self.tbFilterLabel && t.labels.indexOf(self.tbFilterLabel) === -1)
          return false;
        if (
          self.tbFilterAssignee &&
          (t.assignees || []).indexOf(self.tbFilterAssignee) === -1
        )
          return false;
        if (self.tbFilterDue && t.dueBucket !== self.tbFilterDue) return false;
        if (self.tbFilterDateFrom || self.tbFilterDateTo) {
          if (!t.createdAt) return false;
          var taskDate = t.createdAt.substring(0, 10);
          if (self.tbFilterDateFrom && taskDate < self.tbFilterDateFrom)
            return false;
          if (self.tbFilterDateTo && taskDate > self.tbFilterDateTo)
            return false;
        }
        return true;
      });
    },
    sortedTasks: function () {
      if (!this.tbSortKey) return this.filteredTasks;
      var key = this.tbSortKey;
      var dir = this.tbSortDir === "asc" ? 1 : -1;
      var arr = this.filteredTasks.slice();
      arr.sort(function (a, b) {
        var va, vb;
        if (key === "title") {
          va = (a.title || "").toLowerCase();
          vb = (b.title || "").toLowerCase();
        } else if (key === "status") {
          var order = { open: 1, done: 2, archived: 3 };
          va = order[a.status] || 4;
          vb = order[b.status] || 4;
        } else if (key === "due") {
          va = a.due ? new Date(a.due).getTime() : 9999999999999;
          vb = b.due ? new Date(b.due).getTime() : 9999999999999;
        } else if (key === "age") {
          va = a.createdAt ? new Date(a.createdAt).getTime() : 9999999999999;
          vb = b.createdAt ? new Date(b.createdAt).getTime() : 9999999999999;
        }
        if (va < vb) return -1 * dir;
        if (va > vb) return 1 * dir;
        return 0;
      });
      return arr;
    },
    totalPages: function () {
      return Math.max(1, Math.ceil(this.sortedTasks.length / this.tbPageSize));
    },
    paginatedTasks: function () {
      var start = (this.tbPage - 1) * this.tbPageSize;
      return this.sortedTasks.slice(start, start + this.tbPageSize);
    },
  },
  methods: {
    resetFilters: function () {
      this.tbFilterName = "";
      this.tbFilterStatus = "";
      this.tbFilterStack = "";
      this.tbFilterLabel = "";
      this.tbFilterAssignee = "";
      this.tbFilterDue = "";
      this.tbFilterDateFrom = "";
      this.tbFilterDateTo = "";
      this.showDateRangePicker = false;
      this.datePickStep = "from";
      this.tbPage = 1;
      this.tbSortKey = "";
      this.tbSortDir = "asc";
    },
    /* ---- Date range picker helpers ---- */
    pad: function (n) {
      return n < 10 ? "0" + n : "" + n;
    },
    toDateStr: function (d) {
      return (
        d.getFullYear() +
        "-" +
        this.pad(d.getMonth() + 1) +
        "-" +
        this.pad(d.getDate())
      );
    },
    calendarMonthLabel: function (offset) {
      var d = new Date(
        this.calendarBase.getFullYear(),
        this.calendarBase.getMonth() + offset,
        1,
      );
      var months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ];
      return months[d.getMonth()] + " " + d.getFullYear();
    },
    shiftCalendar: function (dir) {
      var d = new Date(this.calendarBase);
      d.setMonth(d.getMonth() + dir);
      this.calendarBase = d;
    },
    calendarCells: function (offset) {
      var year = this.calendarBase.getFullYear();
      var month = this.calendarBase.getMonth() + offset;
      var first = new Date(year, month, 1);
      var dayOfWeek = first.getDay(); // 0=Sun
      var startPad = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Mon-based
      var daysInMonth = new Date(year, month + 1, 0).getDate();
      var cells = [];
      for (var i = 0; i < startPad; i++) {
        cells.push({ day: "", date: null });
      }
      for (var d = 1; d <= daysInMonth; d++) {
        cells.push({ day: d, date: this.toDateStr(new Date(year, month, d)) });
      }
      return cells;
    },
    pickDate: function (dateStr) {
      if (this.datePickStep === "from") {
        this.tbFilterDateFrom = dateStr;
        this.tbFilterDateTo = "";
        this.datePickStep = "to";
      } else {
        if (dateStr < this.tbFilterDateFrom) {
          this.tbFilterDateFrom = dateStr;
          this.tbFilterDateTo = "";
          this.datePickStep = "to";
        } else {
          this.tbFilterDateTo = dateStr;
          this.datePickStep = "from";
        }
      }
    },
    dateCellClass: function (cell) {
      var cls = ["proj-details__date-picker-cell"];
      if (!cell.date) {
        cls.push("proj-details__date-picker-cell--empty");
        return cls;
      }
      var from = this.tbFilterDateFrom;
      var to = this.tbFilterDateTo;
      if (cell.date === from) cls.push("proj-details__date-picker-cell--start");
      if (cell.date === to) cls.push("proj-details__date-picker-cell--end");
      if (from && to && cell.date > from && cell.date < to)
        cls.push("proj-details__date-picker-cell--in-range");
      if (cell.date === from && !to)
        cls.push("proj-details__date-picker-cell--solo");
      var today = this.toDateStr(new Date());
      if (cell.date === today)
        cls.push("proj-details__date-picker-cell--today");
      return cls;
    },
    assigneePct: function (a) {
      if (!a.tasks || a.tasks === 0) return 0;
      return Math.round(((a.doneTasks || 0) / a.tasks) * 100);
    },
    toggleSort: function (key) {
      if (this.tbSortKey === key) {
        this.tbSortDir = this.tbSortDir === "asc" ? "desc" : "asc";
      } else {
        this.tbSortKey = key;
        this.tbSortDir = "asc";
      }
      this.tbPage = 1;
    },
    sortArrow: function (key) {
      if (this.tbSortKey !== key) return "↕";
      return this.tbSortDir === "asc" ? "↑" : "↓";
    },
    toggleDueFilter: function (val) {
      this.tbFilterDue = this.tbFilterDue === val ? "" : val;
    },
    toggleStatusFilter: function (val) {
      this.tbFilterStatus = this.tbFilterStatus === val ? "" : val;
    },
    clearProjectFilters: function () {
      this.tabSearch = "";
      this.tabStatusFilter = "";
      this.tabTaskDueFilter = "";
      this.tabTaskStatusFilter = "";
    },
    taskAge: function (createdAt) {
      if (!createdAt) return "—";
      var created = new Date(createdAt);
      if (isNaN(created.getTime())) return "—";
      var now = new Date();
      var diffMs = now.getTime() - created.getTime();
      var days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      if (days < 1) return "Today";
      if (days === 1) return "1 day";
      if (days < 7) return days + " days";
      var weeks = Math.floor(days / 7);
      if (weeks < 5) return weeks + (weeks === 1 ? " week" : " weeks");
      var months = Math.floor(days / 30);
      if (months < 12) return months + (months === 1 ? " month" : " months");
      var years = Math.floor(days / 365);
      return years + (years === 1 ? " year" : " years");
    },
    applyProjectFilter: function (statusLabel) {
      // Deselect current project so user sees filtered list
      this.selectedProjectId = "";
      // Clear all existing filters
      this.tabSearch = "";
      this.tabStatusFilter = "";
      this.tabTaskDueFilter = "";
      this.tabTaskStatusFilter = "";
      // Set the matching project status filter
      this.tabStatusFilter = statusLabel;
      var self = this;
      this.$nextTick(function () {
        var el = self.$el;
        if (el) {
          el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    },
    applyTaskFilter: function (filterType, filterValue) {
      // Deselect current project so user sees filtered list
      this.selectedProjectId = "";
      // Clear existing project filters
      this.tabSearch = "";
      this.tabStatusFilter = "";
      this.tabTaskDueFilter = "";
      this.tabTaskStatusFilter = "";
      // Set the matching tab-level filter
      if (filterType === "due") {
        this.tabTaskDueFilter = filterValue;
      } else if (filterType === "status") {
        this.tabTaskStatusFilter = filterValue;
      }
      // Scroll to the project selector
      var self = this;
      this.$nextTick(function () {
        var el = self.$el;
        if (el) {
          el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    },
    selectProject: function (boardId, projectName) {
      // Find the project by name (progressDetails uses board_id, projectDetails uses custom id)
      var match = this.projects.find(function (p) {
        return p.name === projectName;
      });
      // Clear filters and select
      this.tabSearch = projectName || "";
      this.tabStatusFilter = "";
      this.tabTaskDueFilter = "";
      this.tabTaskStatusFilter = "";
      this.selectedProjectId = match ? match.id : "";
      var self = this;
      this.$nextTick(function () {
        self.$el.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    },
    selectProjectAndFilterTask: function (projectId, taskTitle) {
      // Find the project name for the search field
      var match = this.projects.find(function (p) {
        return p.id === projectId || String(p.id) === String(projectId);
      });
      // Clear all tab-level filters
      this.tabSearch = match ? match.name : "";
      this.tabStatusFilter = "";
      this.tabTaskDueFilter = "";
      this.tabTaskStatusFilter = "";
      // Select the project
      this.selectedProjectId = projectId;
      // Set the task name filter in the task browser
      var self = this;
      this.$nextTick(function () {
        self.resetFilters();
        self.tbFilterName = taskTitle;
        self.$nextTick(function () {
          var tbEl = self.$el.querySelector(".proj-details__tb-table-wrap");
          if (tbEl) {
            tbEl.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        });
      });
    },
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
  },
};
</script>

<style scoped>
/* ── Tabs strip (#4) ── */
.proj-details__tabs {
  margin-bottom: 20px;
}

.proj-details__tabs-toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.proj-details__tabs-search-input {
  padding: 6px 12px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  width: 200px;
  transition: border-color 0.15s;
}

.proj-details__tabs-search-input:focus {
  border-color: #4a90d9;
}

.proj-details__tabs-status-select {
  padding: 6px 10px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
  cursor: pointer;
}

.proj-details__tabs-status-select:focus {
  border-color: #4a90d9;
}

.proj-details__tabs-clear {
  padding: 5px 12px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  background: #fff;
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-muted, #9ca3af);
  cursor: pointer;
  transition: all 0.15s;
}

.proj-details__tabs-clear:hover {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}

.proj-details__tabs-strip {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: thin;
}

.proj-details__tabs-strip::-webkit-scrollbar {
  height: 4px;
}

.proj-details__tabs-strip::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}

.proj-details__tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 20px;
  background: #fff;
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-primary, #1a1a2e);
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  transition: all 0.15s;
}

.proj-details__tab:hover {
  background: #f8fafc;
  border-color: #c878c8;
}

.proj-details__tab--active {
  background: linear-gradient(135deg, #c878c8, #d494d4);
  color: #fff;
  border-color: #c878c8;
}

.proj-details__tab-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.proj-details__tab--active .proj-details__tab-dot {
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.4);
}

.proj-details__tab-dot--active {
  background: #22c55e;
}
.proj-details__tab-dot--waiting-on-customer {
  background: #f59e0b;
}
.proj-details__tab-dot--on-hold {
  background: #ef4444;
}
.proj-details__tab-dot--done {
  background: #6366f1;
}

.proj-details__tab-name {
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.proj-details__tab-num {
  font-size: 10px;
  opacity: 0.7;
}

.proj-details__tabs-empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 8px 0;
}

/* ── Empty State ── */
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

.proj-details__row--three {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
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

.proj-details__info-link {
  font-size: 13px;
  color: #4a90d9;
  text-decoration: none;
  word-break: break-word;
}

.proj-details__info-link:hover {
  text-decoration: underline;
}

.proj-details__no-client {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 12px 0;
}

/* ── Progress ── */
.proj-details__progress {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border, #e5e7eb);
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
  margin: 0 auto 8px;
}

.proj-details__no-data {
  text-align: center;
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 24px;
}

/* ── Due Grid ── */
.proj-details__due-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
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

.proj-details__due-item--pulse {
  border: 2px solid #ef4444;
  animation: pulse-border 2s ease-in-out infinite;
}

@keyframes pulse-border {
  0%,
  100% {
    border-color: #ef4444;
  }
  50% {
    border-color: #fecaca;
  }
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

/* ── #2: Team Workload ── */
.proj-details__team-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.proj-details__team-member {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.proj-details__team-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.proj-details__team-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.proj-details__team-avatar {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(135deg, #c878c8, #d494d4);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.proj-details__team-name {
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.proj-details__team-stats {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.proj-details__team-count {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
}

.proj-details__team-pct {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__team-bar {
  height: 4px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
}

.proj-details__team-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #c878c8, #d494d4);
  border-radius: 2px;
  transition: width 0.4s ease;
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

/* ── #6 Summary strip ── */
.proj-details__tb-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.proj-details__tb-summary-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 20px;
  background: #fff;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  cursor: pointer;
  transition: all 0.15s;
}

.proj-details__tb-summary-pill:hover {
  background: #fafbfd;
}

.proj-details__tb-summary-pill--active {
  border-color: #c878c8;
  background: #fdf4ff;
}

.proj-details__tb-summary-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.proj-details__tb-summary-dot--danger {
  background: #ef4444;
}
.proj-details__tb-summary-dot--warning {
  background: #f59e0b;
}
.proj-details__tb-summary-dot--success {
  background: #22c55e;
}
.proj-details__tb-summary-dot--info {
  background: #4a90d9;
}

/* ── #6 Sortable headers ── */
.proj-details__th-sort {
  cursor: pointer;
  user-select: none;
  transition: color 0.15s;
}

.proj-details__th-sort:hover {
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__th-sort--active {
  color: #c878c8 !important;
}

.proj-details__sort-arrow {
  font-size: 10px;
  margin-left: 2px;
}

/* ── Task Browser ── */
.proj-details__tb-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.proj-details__tb-filter {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 120px;
  max-width: 200px;
}

.proj-details__tb-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.proj-details__tb-input,
.proj-details__tb-select {
  padding: 7px 10px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
}

.proj-details__tb-input:focus,
.proj-details__tb-select:focus {
  border-color: #4a90d9;
}

.proj-details__tb-input--date {
  min-width: 130px;
  font-size: 12px;
}

/* ── Date Range Picker ── */
.proj-details__tb-filter {
  position: relative;
}

.proj-details__date-range {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: var(--color-main-background, #fff);
  cursor: pointer;
  font-size: 12px;
  min-width: 180px;
  transition: border-color 0.15s;
}

.proj-details__date-range:hover {
  border-color: #4a90d9;
}

.proj-details__date-range-value {
  color: var(--color-text-primary, #1a1a2e);
  font-weight: 500;
  min-width: 70px;
  text-align: center;
}

.proj-details__date-range-value:empty,
.proj-details__date-range-value:not(:empty) {
  /* ensure placeholder text visible */
}

.proj-details__date-range-arrow {
  color: var(--color-text-muted, #9ca3af);
  font-size: 13px;
}

.proj-details__date-range-clear {
  margin-left: auto;
  background: none;
  border: none;
  color: var(--color-text-muted, #9ca3af);
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
  padding: 0 2px;
}

.proj-details__date-range-clear:hover {
  color: #ef4444;
}

.proj-details__date-picker-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 200;
  margin-top: 4px;
  background: var(--color-main-background, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  padding: 12px 14px 8px;
  min-width: 460px;
}

.proj-details__date-picker-months {
  display: flex;
  gap: 16px;
}

.proj-details__date-picker-month {
  flex: 1;
  min-width: 200px;
}

.proj-details__date-picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.proj-details__date-picker-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__date-picker-nav {
  background: none;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 4px;
  width: 24px;
  height: 24px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-primary, #1a1a2e);
  transition: background 0.15s;
}

.proj-details__date-picker-nav:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.proj-details__date-picker-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px 0;
  text-align: center;
}

.proj-details__date-picker-dow {
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  padding: 2px 0 4px;
  text-transform: uppercase;
}

.proj-details__date-picker-cell {
  font-size: 12px;
  padding: 5px 0;
  cursor: pointer;
  border-radius: 0;
  transition: background 0.1s;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__date-picker-cell:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.proj-details__date-picker-cell--empty {
  cursor: default;
}

.proj-details__date-picker-cell--today {
  font-weight: 700;
  text-decoration: underline;
}

.proj-details__date-picker-cell--start {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 6px 0 0 6px;
}

.proj-details__date-picker-cell--end {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 0 6px 6px 0;
}

.proj-details__date-picker-cell--solo {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 6px;
}

.proj-details__date-picker-cell--in-range {
  background: rgba(200, 120, 200, 0.15);
}

.proj-details__date-picker-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid var(--color-border, #e5e7eb);
}

.proj-details__date-picker-btn {
  padding: 4px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: var(--color-main-background, #fff);
  font-size: 12px;
  cursor: pointer;
  color: var(--color-text-primary, #1a1a2e);
}

.proj-details__date-picker-btn:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.proj-details__date-picker-btn--apply {
  background: #c878c8;
  color: #fff;
  border-color: #c878c8;
}

.proj-details__date-picker-btn--apply:hover {
  background: #b060b0;
}

.proj-details__tb-count {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
  margin-bottom: 8px;
}

.proj-details__tb-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
}

.proj-details__tb-table th {
  background: #fafbfd;
  white-space: nowrap;
}
.proj-details__tb-table tbody tr:hover {
  background: #fafbfd;
}

.proj-details__tb-cell-title {
  font-weight: 500;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.proj-details__tb-stack-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  background: #f0f4ff;
  color: #4a90d9;
  white-space: nowrap;
}

.proj-details__tb-status {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  text-transform: capitalize;
}

.proj-details__tb-status--open {
  background: #d4edda;
  color: #166534;
}
.proj-details__tb-status--done {
  background: #e0e7ff;
  color: #3730a3;
}
.proj-details__tb-status--archived {
  background: #f3f4f6;
  color: #6b7280;
}

.proj-details__tb-label-badge {
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

.proj-details__tb-muted {
  color: var(--color-text-muted, #9ca3af);
}
.proj-details__tb-due--overdue {
  color: #d94040;
  font-weight: 600;
}
.proj-details__tb-due--today {
  color: #b8860b;
  font-weight: 600;
}
.proj-details__tb-due--tomorrow {
  color: #e67e5a;
}

.proj-details__tb-empty {
  text-align: center;
  padding: 24px 12px;
  color: #9ca3af;
  font-style: italic;
}

.proj-details__tb-age {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  white-space: nowrap;
}

.proj-details__tb-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding-top: 14px;
}

.proj-details__tb-page-btn {
  padding: 5px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: #fff;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}

.proj-details__tb-page-btn:hover:not(:disabled) {
  background: #fafbfd;
  border-color: #4a90d9;
}
.proj-details__tb-page-btn:disabled {
  opacity: 0.4;
  cursor: default;
}

.proj-details__tb-page-info {
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
}

/* ── Per-project Timeline KPI ── */
.proj-details__row--kpi {
  gap: 16px;
}

.proj-details__kpi-stat {
  background: var(--bg-card, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.proj-details__kpi-stat-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1.2;
}

.proj-details__kpi-stat-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.proj-details__kpi-stat-sub {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 4px;
}

.proj-details__kpi-bar {
  width: 100%;
  max-width: 160px;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
  margin-top: 10px;
}

.proj-details__kpi-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.5s ease;
}

.proj-details__kpi-bar-fill--low {
  background: #ef4444;
}
.proj-details__kpi-bar-fill--mid {
  background: #f59e0b;
}
.proj-details__kpi-bar-fill--high {
  background: #10b981;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .proj-details__row--three {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .proj-details__row--two,
  .proj-details__row--three {
    grid-template-columns: 1fr;
  }

  .proj-details__info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
