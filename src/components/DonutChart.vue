<template>
	<div class="donut-chart">
		<div v-if="hasData">
			<canvas ref="chartCanvas" width="220" height="220"></canvas>
		</div>
		<div v-else class="donut-chart__empty">
			<span class="donut-chart__empty-text">No tasks yet</span>
		</div>
		<div class="donut-chart__legend">
			<div
				v-for="(label, index) in chartData.labels"
				:key="index"
				class="donut-chart__legend-item"
			>
				<span
					class="donut-chart__legend-dot"
					:style="{ backgroundColor: chartData.colors[index] }"
				></span>
				<span class="donut-chart__legend-label">{{ label }}</span>
			</div>
		</div>
	</div>
</template>

<script>
import {
	Chart,
	DoughnutController,
	ArcElement,
	Tooltip,
	Legend,
} from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip, Legend)

export default {
	name: 'DonutChart',
	props: {
		chartData: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			chart: null,
		}
	},
	computed: {
		hasData() {
			return this.chartData.data && this.chartData.data.some(v => v > 0)
		},
	},
	mounted() {
		if (this.hasData) {
			this.renderChart()
		}
	},
	beforeDestroy() {
		if (this.chart) {
			this.chart.destroy()
		}
	},
	methods: {
		renderChart() {
			const ctx = this.$refs.chartCanvas.getContext('2d')
			const total = this.chartData.data.reduce((a, b) => a + b, 0)

			this.chart = new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: this.chartData.labels,
					datasets: [
						{
							data: this.chartData.data,
							backgroundColor: this.chartData.colors,
							borderColor: '#ffffff',
							borderWidth: 3,
							hoverBorderWidth: 3,
							hoverOffset: 4,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					cutout: '58%',
					plugins: {
						legend: {
							display: false,
						},
						tooltip: {
							backgroundColor: '#1a1a2e',
							titleFont: { size: 13, weight: '600' },
							bodyFont: { size: 12 },
							padding: 10,
							cornerRadius: 8,
							callbacks: {
								label: function(context) {
									const value = context.parsed
									const percentage = Math.round((value / total) * 100)
									return ` ${context.label}: ${percentage}%`
								},
							},
						},
					},
					layout: {
						padding: 10,
					},
				},
				plugins: [
					{
						id: 'percentageLabels',
						afterDraw: (chart) => {
							const { ctx: context, data: chartDataRef } = chart
							chart.getDatasetMeta(0).data.forEach((arc, i) => {
								const value = chartDataRef.datasets[0].data[i]
								const percentage = Math.round((value / total) * 100)
								const midAngle = (arc.startAngle + arc.endAngle) / 2
								const radius = (arc.outerRadius + arc.innerRadius) / 2
								const x = arc.x + Math.cos(midAngle) * radius
								const y = arc.y + Math.sin(midAngle) * radius

								context.save()
								context.fillStyle = '#ffffff'
								context.font = 'bold 12px -apple-system, BlinkMacSystemFont, sans-serif'
								context.textAlign = 'center'
								context.textBaseline = 'middle'
								context.fillText(`${percentage}%`, x, y)
								context.restore()
							})
						},
					},
				],
			})
		},
	},
}
</script>

<style scoped>
.donut-chart {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: var(--spacing-lg, 24px);
}

.donut-chart canvas {
	max-width: 220px;
	max-height: 220px;
}

.donut-chart__empty {
	width: 220px;
	height: 220px;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2px dashed var(--color-border, #e5e7eb);
	border-radius: 50%;
}

.donut-chart__empty-text {
	font-size: 14px;
	color: var(--color-text-secondary, #6b7280);
	font-weight: 500;
}

.donut-chart__legend {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: var(--spacing-lg, 24px);
	flex-wrap: wrap;
}

.donut-chart__legend-item {
	display: flex;
	align-items: center;
	gap: 6px;
}

.donut-chart__legend-dot {
	width: 10px;
	height: 10px;
	border-radius: 50%;
	flex-shrink: 0;
}

.donut-chart__legend-label {
	font-size: 13px;
	color: var(--color-text-secondary, #6b7280);
	font-weight: 400;
}
</style>
