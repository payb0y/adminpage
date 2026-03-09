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
      <!-- Date Range Filter -->
      <div class="perf-panel__date-filter-row">
        <label class="perf-panel__date-label">Filter by Date</label>
        <div
          class="perf-panel__date-range"
          @click="showDatePicker = !showDatePicker"
        >
          <span class="perf-panel__date-range-value">{{
            perfDateFrom || "Start"
          }}</span>
          <span class="perf-panel__date-range-arrow">→</span>
          <span class="perf-panel__date-range-value">{{
            perfDateTo || "End"
          }}</span>
          <button
            v-if="perfDateFrom || perfDateTo"
            class="perf-panel__date-range-clear"
            title="Clear dates"
            @click.stop="clearPerfDates"
          >
            ✕
          </button>
        </div>
        <div
          v-if="showDatePicker"
          v-click-outside="closePerfDatePicker"
          class="perf-panel__date-picker-dropdown"
        >
          <div class="perf-panel__date-picker-months">
            <div class="perf-panel__date-picker-month">
              <div class="perf-panel__date-picker-header">
                <button
                  class="perf-panel__date-picker-nav"
                  @click.stop="shiftPerfCalendar(-1)"
                >
                  ‹
                </button>
                <span class="perf-panel__date-picker-title">{{
                  perfCalendarLabel(0)
                }}</span>
                <span></span>
              </div>
              <div class="perf-panel__date-picker-grid">
                <span
                  v-for="d in ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']"
                  :key="d"
                  class="perf-panel__date-picker-dow"
                  >{{ d }}</span
                >
                <span
                  v-for="(cell, ci) in perfCalendarCells(0)"
                  :key="'PL' + ci"
                  :class="perfDateCellClass(cell)"
                  @click.stop="cell.date && pickPerfDate(cell.date)"
                  >{{ cell.day }}</span
                >
              </div>
            </div>
            <div class="perf-panel__date-picker-month">
              <div class="perf-panel__date-picker-header">
                <span></span>
                <span class="perf-panel__date-picker-title">{{
                  perfCalendarLabel(1)
                }}</span>
                <button
                  class="perf-panel__date-picker-nav"
                  @click.stop="shiftPerfCalendar(1)"
                >
                  ›
                </button>
              </div>
              <div class="perf-panel__date-picker-grid">
                <span
                  v-for="d in ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']"
                  :key="d"
                  class="perf-panel__date-picker-dow"
                  >{{ d }}</span
                >
                <span
                  v-for="(cell, ci) in perfCalendarCells(1)"
                  :key="'PR' + ci"
                  :class="perfDateCellClass(cell)"
                  @click.stop="cell.date && pickPerfDate(cell.date)"
                  >{{ cell.day }}</span
                >
              </div>
            </div>
          </div>
          <div class="perf-panel__date-picker-footer">
            <button
              class="perf-panel__date-picker-btn"
              @click.stop="
                perfDateFrom = '';
                perfDateTo = '';
                perfDateStep = 'from';
              "
            >
              Clear
            </button>
            <button
              class="perf-panel__date-picker-btn perf-panel__date-picker-btn--apply"
              @click.stop="showDatePicker = false"
            >
              Apply
            </button>
          </div>
        </div>
      </div>

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
              v-for="(item, idx) in previewProjectProgress"
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
          <div
            v-if="effectiveProjectProgress.length > progressPreviewLimit"
            class="perf-panel__bar-hint"
          >
            Showing {{ previewProjectProgress.length }} of
            {{ effectiveProjectProgress.length }} projects (click for full
            details)
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
              v-for="(item, idx) in previewMemberPerformance"
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
          <div
            v-if="effectiveMemberPerformance.length > memberPreviewLimit"
            class="perf-panel__bar-hint"
          >
            Showing {{ previewMemberPerformance.length }} of
            {{ effectiveMemberPerformance.length }} members (click for full
            details)
          </div>
        </div>
      </div>

      <!-- BOTTOM ROW: Delay Donut + Completion Area -->
      <div class="perf-panel__bottom-grid">
        <!-- Project Tasks Delay Overview -->
        <div class="perf-panel__card">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Project Tasks Delay<br />Overview
            </h3>
            <span
              class="perf-panel__card-drill perf-panel__card-drill--visible"
              title="Click for details"
              @click="openModal('delay')"
            >
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

          <div class="perf-panel__status-badges">
            <span
              class="perf-panel__status-badge"
              :class="{
                'perf-panel__status-badge--active': delayStatusFilter === '',
              }"
              @click="delayStatusFilter = ''"
              >All</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--green"
              :class="{
                'perf-panel__status-badge--active':
                  delayStatusFilter === 'Active',
              }"
              @click="delayStatusFilter = 'Active'"
              >Active</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--amber"
              :class="{
                'perf-panel__status-badge--active':
                  delayStatusFilter === 'Waiting on Customer',
              }"
              @click="delayStatusFilter = 'Waiting on Customer'"
              >W.o.c.</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--orange"
              :class="{
                'perf-panel__status-badge--active':
                  delayStatusFilter === 'On Hold',
              }"
              @click="delayStatusFilter = 'On Hold'"
              >On Hold</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--slate"
              :class="{
                'perf-panel__status-badge--active':
                  delayStatusFilter === 'Done',
              }"
              @click="delayStatusFilter = 'Done'"
              >Done</span
            >
          </div>

          <div class="perf-panel__chart-row">
            <!-- Project list panel -->
            <div class="perf-panel__proj-list">
              <input
                v-model="delaySearch"
                class="perf-panel__proj-search"
                type="text"
                placeholder="Filter…"
                @click.stop
              />
              <div class="perf-panel__proj-items">
                <div
                  v-for="(proj, i) in filteredDelayProjects"
                  :key="'dp-' + i"
                  class="perf-panel__proj-item"
                  :class="{
                    'perf-panel__proj-item--active':
                      delayIndex === proj.originalIndex,
                  }"
                  @click.stop="delayIndex = proj.originalIndex"
                >
                  {{ proj.name }}
                </div>
                <div
                  v-if="filteredDelayProjects.length === 0"
                  class="perf-panel__proj-empty"
                >
                  No match
                </div>
              </div>
            </div>
            <!-- Chart -->
            <div class="perf-panel__chart-area">
              <DonutChart
                :key="'donut-' + delayIndex"
                :chart-data="activeDelayProject.chart"
              />
            </div>
          </div>
        </div>

        <!-- Task Completion Over Time -->
        <div class="perf-panel__card">
          <div class="perf-panel__card-header-row">
            <h3 class="perf-panel__card-title">
              Task Completion<br />Over Time
            </h3>
            <span
              class="perf-panel__card-drill perf-panel__card-drill--visible"
              title="Click for details"
              @click="openModal('completion')"
            >
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

          <div class="perf-panel__status-badges">
            <span
              class="perf-panel__status-badge"
              :class="{
                'perf-panel__status-badge--active':
                  completionStatusFilter === '',
              }"
              @click="completionStatusFilter = ''"
              >All</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--green"
              :class="{
                'perf-panel__status-badge--active':
                  completionStatusFilter === 'Active',
              }"
              @click="completionStatusFilter = 'Active'"
              >Active</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--amber"
              :class="{
                'perf-panel__status-badge--active':
                  completionStatusFilter === 'Waiting on Customer',
              }"
              @click="completionStatusFilter = 'Waiting on Customer'"
              >W.o.c.</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--orange"
              :class="{
                'perf-panel__status-badge--active':
                  completionStatusFilter === 'On Hold',
              }"
              @click="completionStatusFilter = 'On Hold'"
              >On Hold</span
            >
            <span
              class="perf-panel__status-badge perf-panel__status-badge--slate"
              :class="{
                'perf-panel__status-badge--active':
                  completionStatusFilter === 'Done',
              }"
              @click="completionStatusFilter = 'Done'"
              >Done</span
            >
          </div>

          <div class="perf-panel__chart-row">
            <!-- Project list panel -->
            <div class="perf-panel__proj-list">
              <input
                v-model="completionSearch"
                class="perf-panel__proj-search"
                type="text"
                placeholder="Filter…"
                @click.stop
              />
              <div class="perf-panel__proj-items">
                <div
                  v-for="(proj, i) in filteredCompletionProjects"
                  :key="'cp-' + i"
                  class="perf-panel__proj-item"
                  :class="{
                    'perf-panel__proj-item--active':
                      completionIndex === proj.originalIndex,
                  }"
                  @click.stop="completionIndex = proj.originalIndex"
                >
                  {{ proj.name }}
                </div>
                <div
                  v-if="filteredCompletionProjects.length === 0"
                  class="perf-panel__proj-empty"
                >
                  No match
                </div>
              </div>
            </div>
            <!-- Chart -->
            <div class="perf-panel__chart-area">
              <AreaChart
                :key="'area-' + completionIndex"
                :labels="activeCompletionProject.weeks"
                :data="activeCompletionProject.data"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ── PER PROJECT DETAILS ── -->
      <div class="perf-panel__project-details">
        <div class="perf-panel__card">
          <h3 class="perf-panel__card-title">Per Project Details</h3>
          <div class="perf-panel__card-title-underline"></div>
          <ProjectDetailsPanel ref="detailsPanel" :projects="projectDetails" />
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
            <div class="perf-modal__sort-bar" @click.stop>
              <span class="perf-modal__sort-label">% Done:</span>
              <button
                class="perf-modal__sort-btn perf-modal__sort-btn--active"
                @click="
                  progressSortBy = progressSortBy === 'desc' ? 'asc' : 'desc'
                "
              >
                {{ progressSortBy === "desc" ? "High → Low" : "Low → High" }}
              </button>
            </div>
            <div
              v-for="proj in sortedProgressDetails"
              :key="'mp-' + proj.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('progress', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <button
                    class="perf-modal__goto-btn"
                    title="Open in Per Project Details"
                    @click.stop="goToProjectDetails(proj)"
                  >
                    &#8594; Details
                  </button>
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
            <div class="perf-modal__sort-bar">
              <span class="perf-modal__sort-label">Sort by:</span>
              <button
                class="perf-modal__sort-btn"
                :class="{
                  'perf-modal__sort-btn--active': delaySortBy === 'latest',
                }"
                @click="delaySortBy = 'latest'"
              >
                Latest Opened
              </button>
              <button
                class="perf-modal__sort-btn"
                :class="{
                  'perf-modal__sort-btn--active': delaySortBy === 'oldest',
                }"
                @click="delaySortBy = 'oldest'"
              >
                Oldest Opened
              </button>
            </div>
            <div
              v-for="proj in sortedDelayDetails"
              :key="'mdl-' + proj.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('delay', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <button
                    class="perf-modal__goto-btn"
                    title="Open in Per Project Details"
                    @click.stop="goToProjectDetails(proj)"
                  >
                    &#8594; Details
                  </button>
                  <span class="perf-modal__badge perf-modal__badge--success">
                    {{ countByCategory(proj.tasks, "on-time") }} on-time
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--warning">
                    {{ countByCategory(proj.tasks, "delayed") }} delayed
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--danger">
                    {{ countByCategory(proj.tasks, "blocked") }} blocked
                  </span>
                  <span class="perf-modal__badge perf-modal__badge--neutral">
                    &#9202; {{ formatAge(proj.avgDaysActive) }} avg
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
                        <th>Opened</th>
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
                        <td>
                          <span
                            v-if="task.createdAt"
                            class="perf-modal__age-badge"
                            :title="formatDateShort(task.createdAt)"
                            >{{ taskAge(task.createdAt) }}</span
                          >
                          <span v-else>—</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="sortedDelayDetails.length === 0"
              class="perf-modal__empty"
            >
              No delay data available
            </div>
          </template>

          <!-- Completion Detail -->
          <template v-if="modal === 'completion'">
            <div class="perf-modal__sort-bar" @click.stop>
              <span class="perf-modal__sort-label">Completion Rate:</span>
              <button
                class="perf-modal__sort-btn perf-modal__sort-btn--active"
                @click="
                  completionSortBy =
                    completionSortBy === 'desc' ? 'asc' : 'desc'
                "
              >
                {{
                  completionSortBy === "desc"
                    ? "High \u2192 Low"
                    : "Low \u2192 High"
                }}
              </button>
            </div>
            <div
              v-for="proj in sortedCompletionDetails"
              :key="'mc-' + proj.name"
              class="perf-modal__project"
            >
              <div
                class="perf-modal__project-header"
                @click="toggleProject('completion', proj.name)"
              >
                <span class="perf-modal__project-name">{{ proj.name }}</span>
                <div class="perf-modal__project-stats">
                  <button
                    class="perf-modal__goto-btn"
                    title="Open in Per Project Details"
                    @click.stop="goToProjectDetails(proj)"
                  >
                    &#8594; Details
                  </button>
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
                        <th>Stack</th>
                        <th>Completed</th>
                        <th>Due Date</th>
                        <th>Opened</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(task, ti) in proj.tasks" :key="'mct-' + ti">
                        <td>{{ task.title }}</td>
                        <td>
                          <span class="perf-modal__stack-badge">{{
                            task.stack
                          }}</span>
                        </td>
                        <td>{{ task.completed_at }}</td>
                        <td>{{ task.due || "\u2014" }}</td>
                        <td>
                          <span
                            v-if="task.created_at"
                            class="perf-modal__age-badge"
                            :title="formatDateShort(task.created_at)"
                            >{{ taskAge(task.created_at) }}</span
                          >
                          <span v-else>—</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </transition>
            </div>
            <div
              v-if="sortedCompletionDetails.length === 0"
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
      delaySortBy: "latest",
      progressSortBy: "desc",
      completionSortBy: "desc",
      delaySearch: "",
      completionSearch: "",
      delayStatusFilter: "",
      completionStatusFilter: "",
      // Date range filter
      perfDateFrom: "",
      perfDateTo: "",
      showDatePicker: false,
      perfDateStep: "from",
      perfCalendarBase: new Date(),
    };
  },
  computed: {
    progressPreviewLimit: function () {
      return 5;
    },
    memberPreviewLimit: function () {
      return 5;
    },
    hasDateFilter: function () {
      return !!(this.perfDateFrom || this.perfDateTo);
    },
    /* ── Date-filtered card data (re-aggregated from detail tasks) ── */
    effectiveProjectProgress: function () {
      if (!this.hasDateFilter) return this.projectProgress || [];
      var from = this.perfDateFrom;
      var to = this.perfDateTo;
      var list = this.details.progressDetails || [];
      var result = [];
      for (var i = 0; i < list.length; i++) {
        var proj = list[i];
        var total = 0;
        var done = 0;
        for (var j = 0; j < (proj.tasks || []).length; j++) {
          var t = proj.tasks[j];
          var ca = (t.created_at || "").substring(0, 10);
          if (from && ca < from) continue;
          if (to && ca > to) continue;
          total++;
          if (t.status === "done") done++;
        }
        result.push({
          name: proj.name,
          progress: total > 0 ? Math.round((done / total) * 100) : 0,
        });
      }
      return result;
    },
    effectiveMemberPerformance: function () {
      if (!this.hasDateFilter) return this.memberPerformance || [];
      var from = this.perfDateFrom;
      var to = this.perfDateTo;
      var list = this.details.memberDetails || [];
      var result = [];
      for (var i = 0; i < list.length; i++) {
        var member = list[i];
        var total = 0;
        var done = 0;
        for (var j = 0; j < (member.tasks || []).length; j++) {
          var t = member.tasks[j];
          var ca = (t.created_at || "").substring(0, 10);
          if (from && ca < from) continue;
          if (to && ca > to) continue;
          total++;
          if (t.status === "done") done++;
        }
        if (total > 0) {
          result.push({
            name: member.name,
            total: total,
            done: done,
            progress: Math.round((done / total) * 100),
          });
        }
      }
      if (result.length === 0) {
        result.push({ name: "No Assignments", total: 0, done: 0, progress: 0 });
      }
      return result;
    },
    effectiveDelayProjects: function () {
      if (!this.hasDateFilter) return this.taskDelayProjects || [];
      var from = this.perfDateFrom;
      var to = this.perfDateTo;
      var list = this.details.delayDetails || [];
      var result = [];
      for (var i = 0; i < list.length; i++) {
        var proj = list[i];
        var onTime = 0;
        var delayed = 0;
        var blocked = 0;
        for (var j = 0; j < (proj.tasks || []).length; j++) {
          var t = proj.tasks[j];
          var ca = (t.createdAt || "").substring(0, 10);
          if (from && ca < from) continue;
          if (to && ca > to) continue;
          if (t.category === "delayed") delayed++;
          else if (t.category === "blocked") blocked++;
          else onTime++;
        }
        var total = onTime + delayed + blocked;
        result.push({
          name: proj.name,
          status: "",
          chart: {
            labels: ["On-time Tasks", "Delayed Tasks", "Blocked Tasks"],
            data: [
              total > 0 ? Math.round((onTime / total) * 100) : 0,
              total > 0 ? Math.round((delayed / total) * 100) : 0,
              total > 0 ? Math.round((blocked / total) * 100) : 0,
            ],
            colors: ["#2ec4b6", "#f4a261", "#e63946"],
          },
        });
      }
      return result;
    },
    effectiveCompletionProjects: function () {
      if (!this.hasDateFilter) return this.taskCompletionProjects || [];
      var from = this.perfDateFrom;
      var to = this.perfDateTo;
      var list = this.details.completionDetails || [];
      var result = [];
      for (var i = 0; i < list.length; i++) {
        var proj = list[i];
        var completedDates = [];
        for (var j = 0; j < (proj.tasks || []).length; j++) {
          var t = proj.tasks[j];
          var ca = (t.completed_at || "").substring(0, 10);
          if (from && ca < from) continue;
          if (to && ca > to) continue;
          completedDates.push(new Date(t.completed_at));
        }
        if (completedDates.length === 0) {
          result.push({
            name: proj.name,
            status: "",
            weeks: ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5", "Week 6"],
            data: [0, 0, 0, 0, 0, 0],
          });
          continue;
        }
        var now = new Date();
        var weekLabels = [];
        var weekCounts = [];
        for (var w = 5; w >= 0; w--) {
          var weekStart = new Date(now);
          weekStart.setDate(weekStart.getDate() - w * 7);
          var day = weekStart.getDay();
          var diff = day === 0 ? -6 : 1 - day;
          weekStart.setDate(weekStart.getDate() + diff);
          weekStart.setHours(0, 0, 0, 0);
          var weekEnd = new Date(weekStart);
          weekEnd.setDate(weekEnd.getDate() + 6);
          weekEnd.setHours(23, 59, 59, 999);
          var months = [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
          ];
          weekLabels.push(
            months[weekStart.getMonth()] +
              " " +
              (weekStart.getDate() < 10 ? "0" : "") +
              weekStart.getDate(),
          );
          var count = 0;
          for (var k = 0; k < completedDates.length; k++) {
            if (completedDates[k] >= weekStart && completedDates[k] <= weekEnd)
              count++;
          }
          weekCounts.push(count);
        }
        result.push({
          name: proj.name,
          status: "",
          weeks: weekLabels,
          data: weekCounts,
        });
      }
      return result;
    },
    previewProjectProgress: function () {
      return this.effectiveProjectProgress
        .slice()
        .sort(function (a, b) {
          return (b.progress || 0) - (a.progress || 0);
        })
        .slice(0, this.progressPreviewLimit);
    },
    previewMemberPerformance: function () {
      return this.effectiveMemberPerformance
        .slice()
        .sort(function (a, b) {
          var progressDiff = (b.progress || 0) - (a.progress || 0);
          if (progressDiff !== 0) {
            return progressDiff;
          }
          return (b.done || 0) - (a.done || 0);
        })
        .slice(0, this.memberPreviewLimit);
    },
    filteredDelayProjects: function () {
      var q = (this.delaySearch || "").toLowerCase();
      var statusFilter = this.delayStatusFilter;
      var result = [];
      for (var i = 0; i < this.effectiveDelayProjects.length; i++) {
        var proj = this.effectiveDelayProjects[i];
        if (q && proj.name.toLowerCase().indexOf(q) === -1) {
          continue;
        }
        if (statusFilter && proj.status !== statusFilter) {
          continue;
        }
        result.push({ name: proj.name, originalIndex: i });
      }
      return result;
    },
    filteredCompletionProjects: function () {
      var q = (this.completionSearch || "").toLowerCase();
      var statusFilter = this.completionStatusFilter;
      var result = [];
      for (var i = 0; i < this.effectiveCompletionProjects.length; i++) {
        var proj = this.effectiveCompletionProjects[i];
        if (q && proj.name.toLowerCase().indexOf(q) === -1) {
          continue;
        }
        if (statusFilter && proj.status !== statusFilter) {
          continue;
        }
        result.push({ name: proj.name, originalIndex: i });
      }
      return result;
    },
    activeDelayProject: function () {
      return (
        this.effectiveDelayProjects[this.delayIndex] || {
          name: "",
          chart: {
            labels: [],
            data: [0, 0, 0],
            colors: ["#2ec4b6", "#f4a261", "#e63946"],
          },
        }
      );
    },
    activeCompletionProject: function () {
      return (
        this.effectiveCompletionProjects[this.completionIndex] || {
          name: "",
          weeks: [],
          data: [],
        }
      );
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
    sortedProgressDetails: function () {
      var list = (this.details.progressDetails || []).slice();
      if (this.progressSortBy === "asc") {
        list.sort(function (a, b) {
          return a.progress - b.progress;
        });
      } else {
        list.sort(function (a, b) {
          return b.progress - a.progress;
        });
      }
      return list;
    },
    sortedDelayDetails: function () {
      var list = (this.details.delayDetails || []).slice();
      var sortBy = this.delaySortBy;
      if (sortBy === "latest") {
        list.sort(function (a, b) {
          var da = a.latestTaskOpened
            ? new Date(a.latestTaskOpened).getTime()
            : 0;
          var db = b.latestTaskOpened
            ? new Date(b.latestTaskOpened).getTime()
            : 0;
          return db - da;
        });
      } else if (sortBy === "oldest") {
        list.sort(function (a, b) {
          var da = a.oldestTaskOpened
            ? new Date(a.oldestTaskOpened).getTime()
            : Infinity;
          var db = b.oldestTaskOpened
            ? new Date(b.oldestTaskOpened).getTime()
            : Infinity;
          return da - db;
        });
      } else {
        list.sort(function (a, b) {
          return a.name.localeCompare(b.name);
        });
      }
      return list;
    },
    sortedCompletionDetails: function () {
      var list = (this.details.completionDetails || []).slice();
      var sortBy = this.completionSortBy;
      if (sortBy === "asc") {
        list.sort(function (a, b) {
          var rateA = a.total_tasks > 0 ? a.completed / a.total_tasks : 0;
          var rateB = b.total_tasks > 0 ? b.completed / b.total_tasks : 0;
          return rateA - rateB;
        });
      } else {
        list.sort(function (a, b) {
          var rateA = a.total_tasks > 0 ? a.completed / a.total_tasks : 0;
          var rateB = b.total_tasks > 0 ? b.completed / b.total_tasks : 0;
          return rateB - rateA;
        });
      }
      return list;
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
  watch: {
    filteredDelayProjects: function (list) {
      if (list.length > 0) {
        var found = false;
        for (var i = 0; i < list.length; i++) {
          if (list[i].originalIndex === this.delayIndex) {
            found = true;
            break;
          }
        }
        if (!found) {
          this.delayIndex = list[0].originalIndex;
        }
      }
    },
    filteredCompletionProjects: function (list) {
      if (list.length > 0) {
        var found = false;
        for (var i = 0; i < list.length; i++) {
          if (list[i].originalIndex === this.completionIndex) {
            found = true;
            break;
          }
        }
        if (!found) {
          this.completionIndex = list[0].originalIndex;
        }
      }
    },
    effectiveDelayProjects: function (list) {
      if (this.delayIndex >= list.length) {
        this.delayIndex = 0;
      }
    },
    effectiveCompletionProjects: function (list) {
      if (this.completionIndex >= list.length) {
        this.completionIndex = 0;
      }
    },
  },
  methods: {
    prevDelayProject: function () {
      this.delayIndex =
        (this.delayIndex - 1 + this.effectiveDelayProjects.length) %
        this.effectiveDelayProjects.length;
    },
    nextDelayProject: function () {
      this.delayIndex =
        (this.delayIndex + 1) % this.effectiveDelayProjects.length;
    },
    prevCompletionProject: function () {
      this.completionIndex =
        (this.completionIndex - 1 + this.effectiveCompletionProjects.length) %
        this.effectiveCompletionProjects.length;
    },
    nextCompletionProject: function () {
      this.completionIndex =
        (this.completionIndex + 1) % this.effectiveCompletionProjects.length;
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
    formatAge: function (days) {
      if (!days || days < 1) return "0d";
      if (days < 7) return days + "d";
      var weeks = Math.floor(days / 7);
      return weeks + (weeks === 1 ? " week" : " weeks");
    },
    taskAge: function (createdAt) {
      if (!createdAt) return "\u2014";
      var created = new Date(createdAt);
      if (isNaN(created.getTime())) return "\u2014";
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
    formatDateShort: function (d) {
      if (!d) return "\u2014";
      var date = new Date(d);
      if (isNaN(date.getTime())) return d;
      return date.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    },
    filterProjectsByStatus: function (statusLabel) {
      if (this.$refs.detailsPanel) {
        this.$refs.detailsPanel.applyProjectFilter(statusLabel);
      }
    },
    filterTasks: function (filterType, filterValue) {
      if (this.$refs.detailsPanel) {
        this.$refs.detailsPanel.applyTaskFilter(filterType, filterValue);
      }
    },
    gotoOldestTask: function (oldestTask) {
      if (this.$refs.detailsPanel) {
        this.$refs.detailsPanel.selectProjectAndFilterTask(
          oldestTask.projectId,
          oldestTask.fullTitle || oldestTask.taskTitle,
        );
      }
    },
    goToProjectDetails: function (proj) {
      this.closeModal();
      var self = this;
      this.$nextTick(function () {
        if (self.$refs.detailsPanel) {
          self.$refs.detailsPanel.selectProject(proj.id, proj.name);
        }
      });
    },
    /* ── Date range picker helpers ── */
    perfPad: function (n) {
      return n < 10 ? "0" + n : "" + n;
    },
    perfToDateStr: function (d) {
      return (
        d.getFullYear() +
        "-" +
        this.perfPad(d.getMonth() + 1) +
        "-" +
        this.perfPad(d.getDate())
      );
    },
    perfCalendarLabel: function (offset) {
      var d = new Date(
        this.perfCalendarBase.getFullYear(),
        this.perfCalendarBase.getMonth() + offset,
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
    shiftPerfCalendar: function (dir) {
      var d = new Date(this.perfCalendarBase);
      d.setMonth(d.getMonth() + dir);
      this.perfCalendarBase = d;
    },
    perfCalendarCells: function (offset) {
      var year = this.perfCalendarBase.getFullYear();
      var month = this.perfCalendarBase.getMonth() + offset;
      var first = new Date(year, month, 1);
      var dayOfWeek = first.getDay();
      var startPad = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
      var daysInMonth = new Date(year, month + 1, 0).getDate();
      var cells = [];
      for (var i = 0; i < startPad; i++) {
        cells.push({ day: "", date: null });
      }
      for (var d = 1; d <= daysInMonth; d++) {
        cells.push({
          day: d,
          date: this.perfToDateStr(new Date(year, month, d)),
        });
      }
      return cells;
    },
    pickPerfDate: function (dateStr) {
      if (this.perfDateStep === "from") {
        this.perfDateFrom = dateStr;
        this.perfDateTo = "";
        this.perfDateStep = "to";
      } else {
        if (dateStr < this.perfDateFrom) {
          this.perfDateFrom = dateStr;
          this.perfDateTo = "";
          this.perfDateStep = "to";
        } else {
          this.perfDateTo = dateStr;
          this.perfDateStep = "from";
        }
      }
    },
    perfDateCellClass: function (cell) {
      var cls = ["perf-panel__date-picker-cell"];
      if (!cell.date) {
        cls.push("perf-panel__date-picker-cell--empty");
        return cls;
      }
      var from = this.perfDateFrom;
      var to = this.perfDateTo;
      if (cell.date === from) cls.push("perf-panel__date-picker-cell--start");
      if (cell.date === to) cls.push("perf-panel__date-picker-cell--end");
      if (from && to && cell.date > from && cell.date < to)
        cls.push("perf-panel__date-picker-cell--in-range");
      if (cell.date === from && !to)
        cls.push("perf-panel__date-picker-cell--solo");
      var today = this.perfToDateStr(new Date());
      if (cell.date === today) cls.push("perf-panel__date-picker-cell--today");
      return cls;
    },
    closePerfDatePicker: function () {
      this.showDatePicker = false;
    },
    clearPerfDates: function () {
      this.perfDateFrom = "";
      this.perfDateTo = "";
      this.perfDateStep = "from";
      this.showDatePicker = false;
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

/* ── Date Range Filter ── */
.perf-panel__date-filter-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 0 var(--spacing-lg, 24px);
  margin-bottom: 16px;
}

.perf-panel__date-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.perf-panel__date-range {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: var(--color-main-background, #fff);
  cursor: pointer;
  font-size: 12px;
  transition: border-color 0.15s;
}

.perf-panel__date-range:hover {
  border-color: #4a90d9;
}

.perf-panel__date-range-value {
  color: var(--color-text-primary, #1a1a2e);
  font-weight: 500;
  min-width: 70px;
  text-align: center;
}

.perf-panel__date-range-arrow {
  color: var(--color-text-muted, #9ca3af);
  font-size: 13px;
}

.perf-panel__date-range-clear {
  margin-left: 4px;
  background: none;
  border: none;
  color: var(--color-text-muted, #9ca3af);
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
  padding: 0 2px;
}

.perf-panel__date-range-clear:hover {
  color: #ef4444;
}

.perf-panel__date-picker-dropdown {
  display: inline-flex;
  flex-direction: column;
  margin-top: 4px;
  background: var(--color-main-background, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  padding: 12px 14px 8px;
  min-width: 460px;
  width: fit-content;
}

.perf-panel__date-picker-months {
  display: flex;
  gap: 16px;
}

.perf-panel__date-picker-month {
  flex: 1;
  min-width: 200px;
}

.perf-panel__date-picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.perf-panel__date-picker-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

.perf-panel__date-picker-nav {
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

.perf-panel__date-picker-nav:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.perf-panel__date-picker-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px 0;
  text-align: center;
}

.perf-panel__date-picker-dow {
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  padding: 2px 0 4px;
  text-transform: uppercase;
}

.perf-panel__date-picker-cell {
  font-size: 12px;
  padding: 5px 0;
  cursor: pointer;
  border-radius: 0;
  transition: background 0.1s;
  color: var(--color-text-primary, #1a1a2e);
}

.perf-panel__date-picker-cell:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.perf-panel__date-picker-cell--empty {
  cursor: default;
}

.perf-panel__date-picker-cell--today {
  font-weight: 700;
  text-decoration: underline;
}

.perf-panel__date-picker-cell--start {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 6px 0 0 6px;
}

.perf-panel__date-picker-cell--end {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 0 6px 6px 0;
}

.perf-panel__date-picker-cell--solo {
  background: #c878c8 !important;
  color: #fff !important;
  border-radius: 6px;
}

.perf-panel__date-picker-cell--in-range {
  background: rgba(200, 120, 200, 0.15);
}

.perf-panel__date-picker-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid var(--color-border, #e5e7eb);
}

.perf-panel__date-picker-btn {
  padding: 4px 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  background: var(--color-main-background, #fff);
  font-size: 12px;
  cursor: pointer;
  color: var(--color-text-primary, #1a1a2e);
}

.perf-panel__date-picker-btn:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.perf-panel__date-picker-btn--apply {
  background: #c878c8;
  color: #fff;
  border-color: #c878c8;
}

.perf-panel__date-picker-btn--apply:hover {
  background: #b060b0;
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

.perf-panel__bar-hint {
  margin-top: 10px;
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
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

/* Chart Row: list + chart side by side */
.perf-panel__chart-row {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

/* Project list sidebar */
.perf-panel__proj-list {
  width: 150px;
  min-width: 150px;
  display: flex;
  flex-direction: column;
  border: 1px solid #eef1f5;
  border-radius: 8px;
  overflow: hidden;
  background: #fafbfd;
}

.perf-panel__proj-search {
  width: 100%;
  padding: 7px 10px;
  border: none;
  border-bottom: 1px solid #eef1f5;
  font-size: 12px;
  color: var(--color-text-primary, #1a1a2e);
  background: transparent;
  outline: none;
  box-sizing: border-box;
}

.perf-panel__proj-search::placeholder {
  color: #b0b5be;
}

.perf-panel__status-badges {
  display: flex;
  gap: 6px;
  padding: 8px 0 4px;
  flex-wrap: wrap;
}

.perf-panel__status-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 12px;
  cursor: pointer;
  background: #f0f1f5;
  color: #6b7280;
  transition: all 0.15s ease;
  user-select: none;
  border: 1.5px solid transparent;
}

.perf-panel__status-badge:hover {
  background: #e5e7eb;
}

.perf-panel__status-badge--active {
  font-weight: 600;
  border-color: currentColor;
}

.perf-panel__status-badge--green {
  color: #16a34a;
}
.perf-panel__status-badge--green.perf-panel__status-badge--active {
  background: #dcfce7;
}

.perf-panel__status-badge--amber {
  color: #d97706;
}
.perf-panel__status-badge--amber.perf-panel__status-badge--active {
  background: #fef3c7;
}

.perf-panel__status-badge--orange {
  color: #ea580c;
}
.perf-panel__status-badge--orange.perf-panel__status-badge--active {
  background: #ffedd5;
}

.perf-panel__status-badge--slate {
  color: #475569;
}
.perf-panel__status-badge--slate.perf-panel__status-badge--active {
  background: #e2e8f0;
}

.perf-panel__proj-items {
  overflow-y: auto;
  height: 200px;
}

.perf-panel__proj-items::-webkit-scrollbar {
  width: 4px;
}

.perf-panel__proj-items::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}

.perf-panel__proj-item {
  padding: 6px 10px;
  font-size: 12px;
  color: var(--color-text-primary, #1a1a2e);
  cursor: pointer;
  transition: background 0.12s;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  border-left: 3px solid transparent;
}

.perf-panel__proj-item:hover {
  background: #f0f1f5;
}

.perf-panel__proj-item--active {
  background: #f3edf7;
  font-weight: 600;
  border-left-color: #c878c8;
}

.perf-panel__proj-empty {
  padding: 10px;
  font-size: 11px;
  color: #9ca3af;
  text-align: center;
}

/* Chart fills remaining space */
.perf-panel__chart-area {
  flex: 1;
  min-width: 0;
}

/* Drill icon always visible (not hidden by card hover) */
.perf-panel__card-drill--visible {
  opacity: 1;
  cursor: pointer;
}

.perf-panel__card-drill--visible:hover {
  color: #c878c8;
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

.perf-modal__goto-btn {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 12px;
  border: 1px solid #c878c8;
  background: #fff;
  color: #a855a8;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s, color 0.15s;
}

.perf-modal__goto-btn:hover {
  background: #c878c8;
  color: #fff;
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

.perf-modal__badge--neutral {
  background: #f0f1f5;
  color: #555;
}

/* Sort bar */
.perf-modal__sort-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 0 12px;
  border-bottom: 1px solid #eee;
  margin-bottom: 8px;
}

.perf-modal__sort-label {
  font-size: 12px;
  color: #777;
  font-weight: 500;
}

.perf-modal__sort-btn {
  font-size: 12px;
  padding: 4px 12px;
  border-radius: 14px;
  border: 1px solid #ddd;
  background: #fff;
  color: #555;
  cursor: pointer;
  transition: all 0.15s ease;
}

.perf-modal__sort-btn:hover {
  border-color: #aaa;
}

.perf-modal__sort-btn--active {
  background: #5b2c6f;
  color: #fff;
  border-color: #5b2c6f;
}

/* Age badge in modal */
.perf-modal__age-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 500;
  background: #f0f1f5;
  color: #555;
  white-space: nowrap;
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
