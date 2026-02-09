<template>
	<div class="adminpage-dashboard">
		<!-- TOP KPI STRIP -->
		<section class="adminpage-dashboard__kpi-strip">
			<KpiCard
				v-for="kpi in data.kpis"
				:key="kpi.id"
				:title="kpi.title"
				:icon="kpi.icon"
				:icon-color="kpi.iconColor"
				:metrics="kpi.metrics"
			/>
		</section>

		<!-- ALERTS & EXCEPTIONS -->
		<section class="adminpage-dashboard__section">
			<h2 class="adminpage-dashboard__section-title">Alerts &amp; Exceptions</h2>
			<div class="adminpage-dashboard__alerts-grid">
				<AlertCard
					v-for="(alert, index) in data.alerts"
					:key="'alert-' + index"
					:badge-type="alert.badgeType"
					:badge-label="alert.badgeLabel"
					:description="alert.description"
				/>
			</div>
		</section>

		<!-- SAFETY SECTION -->
		<SafetyPanel
			:safety-stats="data.safetyStats"
			:project-incidents="data.projectIncidents"
			:severity-chart="data.severityChart"
			:causes="data.causes"
		/>
	</div>
</template>

<script>
import KpiCard from './KpiCard.vue'
import AlertCard from './AlertCard.vue'
import SafetyPanel from './SafetyPanel.vue'

export default {
	name: 'Dashboard',
	components: {
		KpiCard,
		AlertCard,
		SafetyPanel,
	},
	props: {
		data: {
			type: Object,
			required: true,
		},
	},
}
</script>

<style>
#app-content:has(.adminpage-dashboard) {
	background-color: #f0f1f5 !important;
}

#adminpage-root {
	background-color: #f0f1f5 !important;
	min-height: 100vh;
}
</style>

<style scoped>
.adminpage-dashboard {
	--bg-page: #f0f1f5;
	--bg-card: #ffffff;
	--shadow-card: 0 1px 3px rgba(0, 0, 0, 0.08);
	--shadow-card-hover: 0 4px 12px rgba(0, 0, 0, 0.1);
	--radius-card: 12px;
	--color-text-primary: #1a1a2e;
	--color-text-secondary: #6b7280;
	--color-text-muted: #9ca3af;
	--color-danger: #d94040;
	--color-warning: #b8860b;
	--color-success: #2e7d32;
	--color-badge-danger-bg: #fde8e8;
	--color-badge-danger-text: #b91c1c;
	--color-badge-warning-bg: #fef3cd;
	--color-badge-warning-text: #92400e;
	--color-badge-success-bg: #d4edda;
	--color-badge-success-text: #166534;
	--color-chart-minor: #2ec4b6;
	--color-chart-moderate: #f4a261;
	--color-chart-severe: #e63946;
	--color-border: #e5e7eb;
	--spacing-xs: 4px;
	--spacing-sm: 8px;
	--spacing-md: 16px;
	--spacing-lg: 24px;
	--spacing-xl: 32px;
	--spacing-2xl: 40px;

	background-color: var(--bg-page);
	max-width: 1200px;
	margin: 0 auto;
	padding: var(--spacing-lg);
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, sans-serif;
	color: var(--color-text-primary);
}

.adminpage-dashboard__kpi-strip {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: var(--spacing-md);
	margin-bottom: var(--spacing-xl);
}

.adminpage-dashboard__section {
	margin-bottom: var(--spacing-xl);
}

.adminpage-dashboard__section-title {
	font-size: 22px;
	font-weight: 600;
	color: var(--color-text-primary);
	margin: 0 0 var(--spacing-lg) 0;
	padding: 0;
	border: none;
}

.adminpage-dashboard__alerts-grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: var(--spacing-md);
}

@media (max-width: 1024px) {
	.adminpage-dashboard__kpi-strip {
		grid-template-columns: 1fr;
	}

	.adminpage-dashboard__alerts-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 640px) {
	.adminpage-dashboard__alerts-grid {
		grid-template-columns: 1fr;
	}
}
</style>
