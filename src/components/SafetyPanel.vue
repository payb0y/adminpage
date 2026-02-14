<template>
	<section class="safety-panel">
		<!-- SAFETY HEADER -->
		<div class="safety-panel__header">
			<div class="safety-panel__header-text">
				<h2 class="safety-panel__title">Safety</h2>
				<p class="safety-panel__subtitle">
					Real-time visibility into safety incidents, risks, and compliance across all active projects.
				</p>
			</div>
			<button class="safety-panel__collapse-btn" @click="collapsed = !collapsed">
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
					:class="{ 'safety-panel__collapse-icon--rotated': collapsed }"
					class="safety-panel__collapse-icon"
				>
					<polyline points="18 15 12 9 6 15" />
				</svg>
			</button>
		</div>

		<div v-show="!collapsed">
			<!-- SAFETY STAT CARDS -->
			<div class="safety-panel__stats-grid">
				<div
					v-for="(stat, index) in safetyStats"
					:key="'stat-' + index"
					class="safety-panel__stat-card"
				>
					<div class="safety-panel__stat-label">
						<span class="safety-panel__stat-label-text">{{ stat.label }}</span>
						<span v-if="stat.sublabel" class="safety-panel__stat-sublabel">({{ stat.sublabel }})</span>
					</div>
					<div class="safety-panel__stat-value-row">
						<span class="safety-panel__stat-value">{{ stat.value }} {{ stat.unit }}</span>
						<span
							v-if="stat.trend"
							class="safety-panel__stat-trend"
							:class="trendClass(stat.trendType)"
						>
							{{ stat.trend }}
						</span>
					</div>
				</div>
			</div>

			<!-- LOWER THREE-PANEL GRID -->
			<div class="safety-panel__lower-grid">
				<!-- LEFT: Safety Performance per Project -->
				<div class="safety-panel__card safety-panel__card--performance">
					<h3 class="safety-panel__card-title">Safety Performance<br>per Project</h3>
					<div class="safety-panel__project-list">
						<div
							v-for="(project, index) in projectIncidents"
							:key="'project-' + index"
							class="safety-panel__project-row"
						>
							<span class="safety-panel__project-name">{{ project.name }}</span>
							<span
								class="safety-panel__project-badge"
								:class="incidentBadgeClass(project.incidents)"
							>
								{{ incidentLabel(project.incidents) }}
							</span>
						</div>
					</div>
				</div>

				<!-- CENTER: Incident Severity Breakdown -->
				<div class="safety-panel__card safety-panel__card--severity">
					<h3 class="safety-panel__card-title">Incident Severity<br>Breakdown</h3>
					<DonutChart :chart-data="severityChart" />
				</div>

				<!-- RIGHT: Safety Incidents by Cause -->
				<div class="safety-panel__card safety-panel__card--causes">
					<h3 class="safety-panel__card-title">Safety Incidents<br>by Cause</h3>
					<div class="safety-panel__causes-list">
						<span
							v-for="(cause, index) in causes"
							:key="'cause-' + index"
							class="safety-panel__cause-tag"
						>
							{{ cause }}
						</span>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
import DonutChart from './DonutChart.vue'

export default {
	name: 'SafetyPanel',
	components: {
		DonutChart,
	},
	props: {
		safetyStats: {
			type: Array,
			required: true,
		},
		projectIncidents: {
			type: Array,
			required: true,
		},
		severityChart: {
			type: Object,
			required: true,
		},
		causes: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			collapsed: false,
		}
	},
	methods: {
		trendClass(type) {
			return {
				'safety-panel__stat-trend--positive': type === 'positive',
				'safety-panel__stat-trend--negative': type === 'negative',
			}
		},
		incidentBadgeClass(count) {
			if (count === 0) return 'safety-panel__project-badge--green'
			if (count === 1) return 'safety-panel__project-badge--yellow'
			return 'safety-panel__project-badge--orange'
		},
		incidentLabel(count) {
			if (count === 0) return '0 incidents'
			if (count === 1) return '1 incident'
			return `${count} incidents`
		},
	},
}
</script>

<style scoped>
.safety-panel {
	margin-bottom: var(--spacing-xl, 32px);
}

/* ─── Header ─── */
.safety-panel__header {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	margin-bottom: var(--spacing-lg, 24px);
	background: #fcfdff;
	border: 1px solid #eef1f5;
	border-radius: var(--radius-card, 12px);
	padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
}

.safety-panel__header-text {
	flex: 1;
}

.safety-panel__title {
	font-size: 22px;
	font-weight: 600;
	color: var(--color-text-primary, #1a1a2e);
	margin: 0 0 var(--spacing-sm, 8px) 0;
	padding: 0;
	border: none;
}

.safety-panel__subtitle {
	font-size: 14px;
	color: var(--color-text-muted, #9ca3af);
	margin: 0;
	line-height: 1.5;
	max-width: 500px;
}

.safety-panel__collapse-btn {
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

.safety-panel__collapse-btn:hover {
	background-color: var(--bg-page, #f5f6fa);
}

.safety-panel__collapse-icon {
	transition: transform 0.3s ease;
}

.safety-panel__collapse-icon--rotated {
	transform: rotate(180deg);
}

/* ─── Stat Cards ─── */
.safety-panel__stats-grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: var(--spacing-md, 16px);
	margin-bottom: var(--spacing-lg, 24px);
}

.safety-panel__stat-card {
	background: var(--bg-card, #fff);
	border-radius: var(--radius-card, 12px);
	box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
	padding: var(--spacing-lg, 24px);
}

.safety-panel__stat-label {
	margin-bottom: var(--spacing-sm, 8px);
}

.safety-panel__stat-label-text {
	font-size: 13px;
	font-weight: 600;
	color: var(--color-text-primary, #1a1a2e);
}

.safety-panel__stat-sublabel {
	font-size: 13px;
	font-weight: 400;
	color: var(--color-text-muted, #9ca3af);
	margin-left: 4px;
}

.safety-panel__stat-value-row {
	display: flex;
	align-items: center;
	gap: var(--spacing-sm, 8px);
	flex-wrap: wrap;
}

.safety-panel__stat-value {
	font-size: 16px;
	font-weight: 600;
	color: var(--color-text-primary, #1a1a2e);
}

.safety-panel__stat-trend {
	font-size: 11px;
	font-weight: 600;
	padding: 2px 10px;
	border-radius: 12px;
	white-space: nowrap;
}

.safety-panel__stat-trend--positive {
	background-color: var(--color-badge-success-bg, #d4edda);
	color: var(--color-badge-success-text, #166534);
}

.safety-panel__stat-trend--negative {
	background-color: var(--color-badge-danger-bg, #fde8e8);
	color: var(--color-badge-danger-text, #b91c1c);
}

/* ─── Lower Three-Panel Grid ─── */
.safety-panel__lower-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: var(--spacing-md, 16px);
}

.safety-panel__card {
	background: var(--bg-card, #fff);
	border-radius: var(--radius-card, 12px);
	box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
	padding: var(--spacing-lg, 24px);
	transition: box-shadow 0.2s ease;
}

.safety-panel__card:hover {
	box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.safety-panel__card-title {
	font-size: 17px;
	font-weight: 700;
	color: var(--color-text-primary, #1a1a2e);
	margin: 0 0 var(--spacing-lg, 24px) 0;
	line-height: 1.35;
	padding: 0;
	border: none;
}

/* ─── Performance Project List ─── */
.safety-panel__project-list {
	display: flex;
	flex-direction: column;
	gap: var(--spacing-md, 16px);
}

.safety-panel__project-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: var(--spacing-sm, 8px);
}

.safety-panel__project-name {
	font-size: 13px;
	color: var(--color-text-primary, #1a1a2e);
	font-weight: 400;
	flex: 1;
	min-width: 0;
}

.safety-panel__project-badge {
	font-size: 12px;
	font-weight: 600;
	padding: 4px 12px;
	border-radius: 8px;
	white-space: nowrap;
	flex-shrink: 0;
}

.safety-panel__project-badge--green {
	background-color: var(--color-badge-success-bg, #d4edda);
	color: var(--color-badge-success-text, #166534);
}

.safety-panel__project-badge--yellow {
	background-color: var(--color-badge-warning-bg, #fef3cd);
	color: var(--color-badge-warning-text, #92400e);
}

.safety-panel__project-badge--orange {
	background-color: #fff3e0;
	color: #e65100;
}

/* ─── Causes Tag List ─── */
.safety-panel__causes-list {
	display: flex;
	flex-direction: column;
	gap: var(--spacing-sm, 8px);
}

.safety-panel__cause-tag {
	display: inline-flex;
	align-items: center;
	padding: 8px 16px;
	background-color: var(--bg-page, #f5f6fa);
	border: 1px solid var(--color-border, #e5e7eb);
	border-radius: 8px;
	font-size: 13px;
	color: var(--color-text-primary, #1a1a2e);
	font-weight: 400;
	width: fit-content;
}

/* ─── Responsive ─── */
@media (max-width: 1024px) {
	.safety-panel__stats-grid {
		grid-template-columns: repeat(2, 1fr);
	}

	.safety-panel__lower-grid {
		grid-template-columns: 1fr;
	}
}

@media (max-width: 640px) {
	.safety-panel {
		padding: var(--spacing-md, 16px);
	}

	.safety-panel__stats-grid {
		grid-template-columns: 1fr;
	}
}
</style>
