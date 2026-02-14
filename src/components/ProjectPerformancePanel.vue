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
				<div class="perf-panel__card">
					<h3 class="perf-panel__card-title">
						Project Progress<br>Comparison
					</h3>
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
				<div class="perf-panel__card">
					<h3 class="perf-panel__card-title">
						Productivity<br>by Discipline
					</h3>
					<div class="perf-panel__card-title-underline"></div>
					<p class="perf-panel__card-desc">Tasks completed in relation to the assigned task</p>
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
				<div class="perf-panel__card">
					<h3 class="perf-panel__card-title">
						Project Tasks Delay<br>Overview
					</h3>
					<div class="perf-panel__card-title-underline"></div>

				<!-- Project navigator -->
				<div class="perf-panel__navigator">
					<button class="perf-panel__nav-btn" @click="prevDelayProject">&lsaquo;</button>
					<span class="perf-panel__nav-label">{{ activeDelayProject.name }}</span>
					<button class="perf-panel__nav-btn" @click="nextDelayProject">&rsaquo;</button>
				</div>

				<DonutChart :chart-data="activeDelayProject.chart" />
				</div>

				<!-- Task Completion Over Time -->
				<div class="perf-panel__card">
					<h3 class="perf-panel__card-title">
						Task Completion<br>Over Time
					</h3>
					<div class="perf-panel__card-title-underline"></div>

				<!-- Project navigator -->
				<div class="perf-panel__navigator">
					<button class="perf-panel__nav-btn" @click="prevCompletionProject">&lsaquo;</button>
					<span class="perf-panel__nav-label">{{ activeCompletionProject.name }}</span>
					<button class="perf-panel__nav-btn" @click="nextCompletionProject">&rsaquo;</button>
				</div>

					<AreaChart
						:key="'area-' + completionIndex"
						:labels="activeCompletionProject.weeks"
						:data="activeCompletionProject.data"
					/>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
import DonutChart from './DonutChart.vue'
import AreaChart from './AreaChart.vue'

export default {
	name: 'ProjectPerformancePanel',
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
	},
	data() {
		return {
			collapsed: false,
			delayIndex: 0,
			completionIndex: 0,
		}
	},
	computed: {
		activeDelayProject() {
			return this.taskDelayProjects[this.delayIndex]
		},
		activeCompletionProject() {
			return this.taskCompletionProjects[this.completionIndex]
		},
	},
	methods: {
		prevDelayProject() {
			this.delayIndex = (this.delayIndex - 1 + this.taskDelayProjects.length) % this.taskDelayProjects.length
		},
		nextDelayProject() {
			this.delayIndex = (this.delayIndex + 1) % this.taskDelayProjects.length
		},
		prevCompletionProject() {
			this.completionIndex = (this.completionIndex - 1 + this.taskCompletionProjects.length) % this.taskCompletionProjects.length
		},
		nextCompletionProject() {
			this.completionIndex = (this.completionIndex + 1) % this.taskCompletionProjects.length
		},
	},
}
</script>

<style scoped>
.perf-panel {
	margin-bottom: var(--spacing-xl, 32px);
}

/* ─── Header ─── */
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

/* ─── Grids ─── */
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

/* ─── Card ─── */
.perf-panel__card {
	background: var(--bg-card, #fff);
	border-radius: var(--radius-card, 12px);
	box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
	padding: var(--spacing-lg, 24px);
	transition: box-shadow 0.2s ease;
}

.perf-panel__card:hover {
	box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
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

/* ─── Progress Bars ─── */
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

/* ─── Project Navigator ─── */
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

/* ─── Responsive ─── */
@media (max-width: 1024px) {
	.perf-panel__top-grid,
	.perf-panel__bottom-grid {
		grid-template-columns: 1fr;
	}
}
</style>
