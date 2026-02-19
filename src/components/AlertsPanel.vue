<template>
	<section class="alerts-panel">
		<!-- HEADER -->
		<div class="alerts-panel__header">
			<div class="alerts-panel__header-text">
				<h2 class="alerts-panel__title">Alerts &amp; Exceptions</h2>
			</div>
			<button class="alerts-panel__collapse-btn" @click="collapsed = !collapsed">
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
			<!-- SUMMARY STRIP -->
			<div class="alerts-panel__summary-strip">
				<div
					v-for="(item, idx) in alerts.summary"
					:key="'sum-' + idx"
					class="alerts-panel__summary-card"
					:class="'alerts-panel__summary-card--' + item.type"
				>
					<span class="alerts-panel__summary-value">{{ item.value }}</span>
					<span class="alerts-panel__summary-label">{{ item.label }}</span>
				</div>
			</div>

			<!-- DETAIL WIDGETS ROW -->
			<div class="alerts-panel__details-grid">
				<!-- Overdue Tasks Widget -->
				<div class="alerts-panel__widget">
					<div class="alerts-panel__widget-header">
						<h3 class="alerts-panel__widget-title">
							<span class="alerts-panel__widget-icon alerts-panel__widget-icon--danger">!</span>
							Overdue Tasks
						</h3>
						<span class="alerts-panel__widget-count alerts-panel__widget-count--danger">
							{{ overdueTotal }}
						</span>
					</div>
					<div class="alerts-panel__widget-body">
						<div v-if="alerts.overdueTasks.length === 0" class="alerts-panel__empty">
							<span class="alerts-panel__empty-check">✓</span>
							No overdue tasks
						</div>
						<div
							v-for="group in alerts.overdueTasks"
							:key="'od-' + group.project_name"
							class="alerts-panel__project-group"
						>
							<div class="alerts-panel__project-header">
								<span class="alerts-panel__project-name">{{ group.project_name }}</span>
								<span class="alerts-panel__project-count alerts-panel__project-count--danger">
									{{ group.count }} task{{ group.count > 1 ? 's' : '' }}
								</span>
							</div>
							<ul class="alerts-panel__task-list">
								<li
									v-for="task in group.tasks"
									:key="'od-t-' + task.task_id"
									class="alerts-panel__task-item"
								>
									<span class="alerts-panel__task-name">{{ task.task_title }}</span>
									<div class="alerts-panel__task-meta">
										<span class="alerts-panel__task-stack">{{ task.stack_title }}</span>
										<span class="alerts-panel__task-overdue">
											{{ task.days_overdue }}d overdue
										</span>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>

				<!-- Unassigned Tasks Widget -->
				<div class="alerts-panel__widget">
					<div class="alerts-panel__widget-header">
						<h3 class="alerts-panel__widget-title">
							<span class="alerts-panel__widget-icon alerts-panel__widget-icon--warning">?</span>
							Unassigned Tasks
						</h3>
						<span class="alerts-panel__widget-count alerts-panel__widget-count--warning">
							{{ unassignedTotal }}
						</span>
					</div>
					<div class="alerts-panel__widget-body">
						<div v-if="alerts.unassignedTasks.length === 0" class="alerts-panel__empty">
							<span class="alerts-panel__empty-check">✓</span>
							All tasks have an assignee
						</div>
						<div
							v-for="group in alerts.unassignedTasks"
							:key="'ua-' + group.project_name"
							class="alerts-panel__project-group"
						>
							<div class="alerts-panel__project-header">
								<span class="alerts-panel__project-name">{{ group.project_name }}</span>
								<span class="alerts-panel__project-count alerts-panel__project-count--warning">
									{{ group.count }} task{{ group.count > 1 ? 's' : '' }}
								</span>
							</div>
							<ul class="alerts-panel__task-list">
								<li
									v-for="task in group.tasks.slice(0, expandedUnassigned[group.project_name] ? undefined : 3)"
									:key="'ua-t-' + task.task_id"
									class="alerts-panel__task-item"
								>
									<span class="alerts-panel__task-name">{{ task.task_title }}</span>
									<span class="alerts-panel__task-stack">{{ task.stack_title }}</span>
								</li>
							</ul>
							<button
								v-if="group.tasks.length > 3"
								class="alerts-panel__show-more"
								@click="$set(expandedUnassigned, group.project_name, !expandedUnassigned[group.project_name])"
							>
								{{ expandedUnassigned[group.project_name] ? 'Show less' : `+${group.tasks.length - 3} more` }}
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- SECOND ROW -->
			<div class="alerts-panel__details-grid">
				<!-- No Due Date Widget -->
				<div class="alerts-panel__widget">
					<div class="alerts-panel__widget-header">
						<h3 class="alerts-panel__widget-title">
							<span class="alerts-panel__widget-icon alerts-panel__widget-icon--warning">⏱</span>
							Tasks Without Due Date
						</h3>
						<span class="alerts-panel__widget-count alerts-panel__widget-count--warning">
							{{ noDueDateTotal }}
						</span>
					</div>
					<div class="alerts-panel__widget-body">
						<div v-if="alerts.noDueDateTasks.length === 0" class="alerts-panel__empty">
							<span class="alerts-panel__empty-check">✓</span>
							All tasks have a due date
						</div>
						<div
							v-for="group in alerts.noDueDateTasks"
							:key="'nd-' + group.project_name"
							class="alerts-panel__project-group"
						>
							<div class="alerts-panel__project-header">
								<span class="alerts-panel__project-name">{{ group.project_name }}</span>
								<span class="alerts-panel__project-count alerts-panel__project-count--warning">
									{{ group.count }} task{{ group.count > 1 ? 's' : '' }}
								</span>
							</div>
							<ul class="alerts-panel__task-list">
								<li
									v-for="task in group.tasks.slice(0, expandedNoDueDate[group.project_name] ? undefined : 3)"
									:key="'nd-t-' + task.task_id"
									class="alerts-panel__task-item"
								>
									<span class="alerts-panel__task-name">{{ task.task_title }}</span>
									<span class="alerts-panel__task-stack">{{ task.stack_title }}</span>
								</li>
							</ul>
							<button
								v-if="group.tasks.length > 3"
								class="alerts-panel__show-more"
								@click="$set(expandedNoDueDate, group.project_name, !expandedNoDueDate[group.project_name])"
							>
								{{ expandedNoDueDate[group.project_name] ? 'Show less' : `+${group.tasks.length - 3} more` }}
							</button>
						</div>
					</div>
				</div>

				<!-- Stalled Projects + Zero Progress + Updates -->
				<div class="alerts-panel__widget">
					<div class="alerts-panel__widget-header">
						<h3 class="alerts-panel__widget-title">
							<span class="alerts-panel__widget-icon alerts-panel__widget-icon--warning">⚡</span>
							Project Health
						</h3>
					</div>
					<div class="alerts-panel__widget-body">
						<!-- Stalled Projects -->
						<div v-if="alerts.stalledProjects.length > 0" class="alerts-panel__subsection">
							<h4 class="alerts-panel__subsection-title">Stalled Projects</h4>
							<div
								v-for="proj in alerts.stalledProjects"
								:key="'stalled-' + proj.project_name"
								class="alerts-panel__stalled-item"
							>
								<div class="alerts-panel__stalled-row">
									<span class="alerts-panel__project-name">{{ proj.project_name }}</span>
									<span class="alerts-panel__stalled-days">
										{{ proj.days_inactive }}d inactive
									</span>
								</div>
								<div class="alerts-panel__stalled-meta">
									{{ proj.done_tasks }}/{{ proj.total_tasks }} tasks done · Last activity {{ proj.last_activity }}
								</div>
							</div>
						</div>

						<!-- Zero Progress Projects -->
						<div v-if="alerts.zeroProgress.length > 0" class="alerts-panel__subsection">
							<h4 class="alerts-panel__subsection-title">Zero Progress</h4>
							<div
								v-for="proj in alerts.zeroProgress"
								:key="'zp-' + proj.project_name"
								class="alerts-panel__stalled-item"
							>
								<div class="alerts-panel__stalled-row">
									<span class="alerts-panel__project-name">{{ proj.project_name }}</span>
									<span class="alerts-panel__stalled-days">
										{{ proj.total_tasks }} tasks, 0 done
									</span>
								</div>
							</div>
						</div>

						<!-- App Updates -->
						<div v-if="alerts.pendingUpdates.length > 0" class="alerts-panel__subsection">
							<h4 class="alerts-panel__subsection-title">Pending App Updates</h4>
							<div
								v-for="(upd, idx) in alerts.pendingUpdates"
								:key="'upd-' + idx"
								class="alerts-panel__update-item"
							>
								<span class="alerts-panel__update-app">{{ upd.app_name }}</span>
							</div>
						</div>

						<!-- All clear -->
						<div
							v-if="alerts.stalledProjects.length === 0 && alerts.zeroProgress.length === 0 && alerts.pendingUpdates.length === 0"
							class="alerts-panel__empty"
						>
							<span class="alerts-panel__empty-check">✓</span>
							All projects healthy, no pending updates
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
export default {
	name: 'AlertsPanel',
	props: {
		alerts: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			collapsed: false,
			expandedUnassigned: {},
			expandedNoDueDate: {},
		}
	},
	computed: {
		overdueTotal() {
			return this.alerts.overdueTasks.reduce((sum, g) => sum + g.count, 0)
		},
		unassignedTotal() {
			return this.alerts.unassignedTasks.reduce((sum, g) => sum + g.count, 0)
		},
		noDueDateTotal() {
			return this.alerts.noDueDateTasks.reduce((sum, g) => sum + g.count, 0)
		},
	},
}
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

/* ─── Detail Grid ─── */
.alerts-panel__details-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: var(--spacing-md, 16px);
	margin-bottom: var(--spacing-md, 16px);
}

/* ─── Widget Card ─── */
.alerts-panel__widget {
	background: var(--bg-card, #fff);
	border-radius: var(--radius-card, 12px);
	box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
	padding: var(--spacing-lg, 24px);
	transition: box-shadow 0.2s ease;
}

.alerts-panel__widget:hover {
	box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.alerts-panel__widget-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: var(--spacing-md, 16px);
	padding-bottom: var(--spacing-sm, 8px);
	border-bottom: 1px solid #f0f1f5;
}

.alerts-panel__widget-title {
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

.alerts-panel__widget-icon {
	width: 24px;
	height: 24px;
	border-radius: 50%;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 12px;
	font-weight: 700;
	flex-shrink: 0;
}

.alerts-panel__widget-icon--danger {
	background-color: #fde8e8;
	color: #b91c1c;
}

.alerts-panel__widget-icon--warning {
	background-color: #fef3cd;
	color: #92400e;
}

.alerts-panel__widget-count {
	font-size: 20px;
	font-weight: 700;
	border-radius: 8px;
	padding: 2px 10px;
}

.alerts-panel__widget-count--danger {
	background-color: #fde8e8;
	color: #b91c1c;
}

.alerts-panel__widget-count--warning {
	background-color: #fef3cd;
	color: #92400e;
}

/* ─── Widget Body ─── */
.alerts-panel__widget-body {
	max-height: 320px;
	overflow-y: auto;
}

.alerts-panel__widget-body::-webkit-scrollbar {
	width: 4px;
}

.alerts-panel__widget-body::-webkit-scrollbar-thumb {
	background: #d1d5db;
	border-radius: 2px;
}

/* ─── Project Group ─── */
.alerts-panel__project-group {
	margin-bottom: var(--spacing-md, 16px);
}

.alerts-panel__project-group:last-child {
	margin-bottom: 0;
}

.alerts-panel__project-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 6px;
}

.alerts-panel__project-name {
	font-size: 13px;
	font-weight: 600;
	color: var(--color-text-primary, #1a1a2e);
}

.alerts-panel__project-count {
	font-size: 11px;
	font-weight: 600;
	padding: 2px 8px;
	border-radius: 10px;
}

.alerts-panel__project-count--danger {
	background-color: #fde8e8;
	color: #b91c1c;
}

.alerts-panel__project-count--warning {
	background-color: #fef3cd;
	color: #92400e;
}

/* ─── Task List ─── */
.alerts-panel__task-list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.alerts-panel__task-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 6px 0;
	border-bottom: 1px solid #f8f9fa;
	gap: 8px;
}

.alerts-panel__task-item:last-child {
	border-bottom: none;
}

.alerts-panel__task-name {
	font-size: 13px;
	color: var(--color-text-primary, #1a1a2e);
	font-weight: 400;
	flex: 1;
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.alerts-panel__task-meta {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-shrink: 0;
}

.alerts-panel__task-stack {
	font-size: 11px;
	color: var(--color-text-muted, #9ca3af);
	background: #f5f6fa;
	padding: 2px 6px;
	border-radius: 4px;
	white-space: nowrap;
	flex-shrink: 0;
}

.alerts-panel__task-overdue {
	font-size: 11px;
	font-weight: 600;
	color: #b91c1c;
	background-color: #fde8e8;
	padding: 2px 6px;
	border-radius: 4px;
	white-space: nowrap;
}

/* ─── Show More Button ─── */
.alerts-panel__show-more {
	background: none;
	border: none;
	color: #4A90D9;
	font-size: 12px;
	font-weight: 500;
	cursor: pointer;
	padding: 4px 0;
	margin-top: 4px;
}

.alerts-panel__show-more:hover {
	text-decoration: underline;
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

/* ─── Subsections (Project Health) ─── */
.alerts-panel__subsection {
	margin-bottom: var(--spacing-md, 16px);
}

.alerts-panel__subsection:last-child {
	margin-bottom: 0;
}

.alerts-panel__subsection-title {
	font-size: 12px;
	font-weight: 600;
	color: var(--color-text-secondary, #6b7280);
	text-transform: uppercase;
	letter-spacing: 0.05em;
	margin: 0 0 8px 0;
	padding: 0;
	border: none;
}

.alerts-panel__stalled-item {
	padding: 8px 0;
	border-bottom: 1px solid #f8f9fa;
}

.alerts-panel__stalled-item:last-child {
	border-bottom: none;
}

.alerts-panel__stalled-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.alerts-panel__stalled-days {
	font-size: 11px;
	font-weight: 600;
	color: #92400e;
	background: #fef3cd;
	padding: 2px 6px;
	border-radius: 4px;
}

.alerts-panel__stalled-meta {
	font-size: 11px;
	color: var(--color-text-muted, #9ca3af);
	margin-top: 2px;
}

.alerts-panel__update-item {
	padding: 6px 0;
	border-bottom: 1px solid #f8f9fa;
}

.alerts-panel__update-item:last-child {
	border-bottom: none;
}

.alerts-panel__update-app {
	font-size: 13px;
	color: var(--color-text-primary, #1a1a2e);
}

/* ─── Responsive ─── */
@media (max-width: 1024px) {
	.alerts-panel__summary-strip {
		grid-template-columns: repeat(3, 1fr);
	}

	.alerts-panel__details-grid {
		grid-template-columns: 1fr;
	}
}

@media (max-width: 640px) {
	.alerts-panel__summary-strip {
		grid-template-columns: repeat(2, 1fr);
	}
}
</style>
