<template>
	<div class="alert-card">
		<div class="alert-card__badge" :class="badgeClass">
			{{ badgeLabel }}
		</div>
		<p class="alert-card__description">{{ description }}</p>
	</div>
</template>

<script>
export default {
	name: 'AlertCard',
	props: {
		badgeType: {
			type: String,
			required: true,
			validator: (value) => ['action', 'attention', 'ontrack'].includes(value),
		},
		badgeLabel: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
	},
	computed: {
		badgeClass() {
			return {
				'alert-card__badge--action': this.badgeType === 'action',
				'alert-card__badge--attention': this.badgeType === 'attention',
				'alert-card__badge--ontrack': this.badgeType === 'ontrack',
			}
		},
	},
}
</script>

<style scoped>
.alert-card {
	background: var(--bg-card, #fff);
	border-radius: var(--radius-card, 12px);
	box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
	padding: var(--spacing-lg, 24px);
	display: flex;
	flex-direction: column;
	gap: var(--spacing-md, 16px);
	transition: box-shadow 0.2s ease;
}

.alert-card:hover {
	box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.alert-card__badge {
	display: inline-flex;
	align-items: center;
	padding: 4px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	width: fit-content;
	line-height: 1.5;
}

.alert-card__badge--action {
	background-color: var(--color-badge-danger-bg, #fde8e8);
	color: var(--color-badge-danger-text, #b91c1c);
}

.alert-card__badge--attention {
	background-color: var(--color-badge-warning-bg, #fef3cd);
	color: var(--color-badge-warning-text, #92400e);
}

.alert-card__badge--ontrack {
	background-color: var(--color-badge-success-bg, #d4edda);
	color: var(--color-badge-success-text, #166534);
}

.alert-card__description {
	font-size: 14px;
	color: var(--color-text-primary, #1a1a2e);
	line-height: 1.5;
	margin: 0;
	font-weight: 400;
}
</style>
