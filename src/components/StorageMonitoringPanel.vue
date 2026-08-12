<template>
  <section class="storage-monitor iz-panel" aria-labelledby="storage-monitor-title">
    <header class="storage-monitor__header">
      <div>
        <p class="storage-monitor__eyebrow">Capacity monitoring</p>
        <h2 id="storage-monitor-title" class="storage-monitor__title">Storage pulse</h2>
      </div>
      <div v-if="storage" class="storage-monitor__actions">
        <span class="iz-pill iz-pill--neutral">Updated {{ updatedAt }}</span>
        <button class="iz-btn iz-btn--secondary" type="button" :disabled="loading" @click="$emit('retry')">
          {{ loading ? "Refreshing..." : "Refresh" }}
        </button>
      </div>
    </header>

    <div v-if="loading && !storage" class="storage-monitor__state">
      <span class="iz-spinner" aria-hidden="true"></span>
      <span>Calculating current storage usage...</span>
    </div>
    <div v-else-if="error && !storage" class="storage-monitor__state storage-monitor__state--error">
      <span>{{ error }}</span>
      <button class="iz-btn iz-btn--secondary" type="button" @click="$emit('retry')">Try again</button>
    </div>

    <template v-else-if="storage">
      <div class="storage-monitor__summary">
        <div class="storage-monitor__headline">
          <strong>{{ formatBytes(summary.usedBytes) }}</strong>
          <span>of {{ formatBytes(summary.capacityBytes) }} configured capacity</span>
        </div>
        <span :class="['iz-pill', summaryPillClass]">{{ summaryStatus }}</span>
      </div>
      <div class="storage-monitor__meter iz-meter" role="progressbar" aria-label="Organization storage used" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="summary.percentage || 0">
        <span class="iz-meter__fill" :style="{ width: meterWidth(summary.percentage) }"></span>
      </div>

      <div class="storage-monitor__metrics iz-metrics">
        <div class="iz-metric">
          <span class="iz-metric__label">Private user storage</span>
          <strong class="iz-metric__value">{{ formatBytes(summary.private.usedBytes) }}</strong>
          <span class="iz-metric__label">{{ formatBytes(summary.private.capacityBytes) }} capacity</span>
        </div>
        <div class="iz-metric">
          <span class="iz-metric__label">Shared project storage</span>
          <strong class="iz-metric__value">{{ formatBytes(summary.shared.usedBytes) }}</strong>
          <span class="iz-metric__label">{{ formatBytes(summary.shared.capacityBytes) }} capacity</span>
        </div>
        <div class="iz-metric">
          <span class="iz-metric__label">Needs attention</span>
          <strong class="iz-metric__value">{{ attentionCount }}</strong>
          <span class="iz-metric__label">at or above 80%</span>
        </div>
      </div>

      <p v-if="!summary.complete" class="storage-monitor__notice iz-note">
        Some usage or quota measurements are unavailable. Totals include known measurements only.
      </p>

      <div class="storage-monitor__lists">
        <StorageEntityList title="People" empty-text="No organization members found." :items="storage.users || []" />
        <StorageEntityList title="Projects" empty-text="No projects found." :items="storage.projects || []" />
      </div>
    </template>
  </section>
</template>

<script>
import StorageEntityList from "./StorageEntityList.vue";

export default {
  name: "StorageMonitoringPanel",
  components: { StorageEntityList },
  props: {
    storage: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
  },
  computed: {
    summary: function () {
      return this.storage.summary || { private: {}, shared: {} };
    },
    attentionCount: function () {
      var thresholds = this.storage.thresholds || {};
      return (thresholds.warningCount || 0) + (thresholds.criticalCount || 0);
    },
    summaryStatus: function () {
      var percentage = this.summary.percentage;
      if (percentage === null) return "Capacity unavailable";
      if (percentage >= 100) return "Action needed";
      if (percentage >= 80) return "Watch closely";
      return "Healthy";
    },
    summaryPillClass: function () {
      var percentage = this.summary.percentage;
      if (percentage === null) return "iz-pill--neutral";
      if (percentage >= 100) return "iz-pill--danger";
      if (percentage >= 80) return "iz-pill--warning";
      return "iz-pill--success";
    },
    updatedAt: function () {
      if (!this.summary.calculatedAt) return "recently";
      return new Intl.DateTimeFormat(undefined, { hour: "2-digit", minute: "2-digit" }).format(new Date(this.summary.calculatedAt));
    },
  },
  methods: {
    formatBytes: function (bytes) {
      if (bytes === null || bytes === undefined) return "Unavailable";
      if (bytes === 0) return "0 B";
      var units = ["B", "KB", "MB", "GB", "TB", "PB"];
      var index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      return new Intl.NumberFormat(undefined, { maximumFractionDigits: index > 2 ? 2 : 1 }).format(bytes / Math.pow(1024, index)) + " " + units[index];
    },
    meterWidth: function (percentage) {
      return Math.min(100, Math.max(0, percentage || 0)) + "%";
    },
  },
};
</script>

<style scoped>
.storage-monitor { margin-bottom: var(--spacing-xl); }
.storage-monitor__header, .storage-monitor__summary { display: flex; align-items: center; justify-content: space-between; gap: var(--spacing-md); }
.storage-monitor__actions { display: flex; align-items: center; gap: var(--spacing-sm); }
.storage-monitor__eyebrow { margin: 0 0 var(--spacing-xs); color: var(--color-text-secondary); font-size: var(--iz-fs-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.storage-monitor__title { margin: 0; padding: 0; border: 0; color: var(--color-text-primary); }
.storage-monitor__summary { margin-top: var(--spacing-lg); }
.storage-monitor__headline { display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--spacing-sm); color: var(--color-text-secondary); }
.storage-monitor__headline strong { color: var(--color-text-primary); font-size: var(--iz-fs-2xl); font-variant-numeric: tabular-nums; }
.storage-monitor__meter { margin-top: var(--spacing-md); width: 100%; }
.storage-monitor__metrics { margin-top: var(--spacing-lg); }
.storage-monitor__notice { margin-top: var(--spacing-md); }
.storage-monitor__lists { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-xl); }
.storage-monitor__state { min-height: 180px; display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); color: var(--color-text-secondary); }
.storage-monitor__state--error { flex-direction: column; }
@media (max-width: 768px) {
  .storage-monitor__header, .storage-monitor__summary { align-items: flex-start; flex-direction: column; }
  .storage-monitor__lists { grid-template-columns: 1fr; }
}
</style>
