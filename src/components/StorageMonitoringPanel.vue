<template>
  <!-- No header of its own: embedded under Organization Insights, the section
       title row (icon, "Capacity", status pill, Updated + Refresh) is owned by
       OrgInsightsPanel, the same way every other sub-section's title is. -->
  <div :class="['storage-monitor', embedded ? 'iz-panel--flush' : 'iz-panel']">
    <div v-if="loading && !storage" class="storage-monitor__state iz-empty">
      <span class="iz-spinner" aria-hidden="true"></span>
      <span>Calculating current storage usage...</span>
    </div>
    <div v-else-if="error && !storage" class="storage-monitor__state iz-error">
      <span>{{ error }}</span>
      <button class="iz-btn iz-btn--sm" type="button" @click="$emit('retry')">Try again</button>
    </div>

    <template v-else-if="storage">
      <p class="storage-monitor__headline">
        <strong>{{ formatBytes(summary.usedBytes) }}</strong>
        <span>of {{ formatBytes(summary.capacityBytes) }} configured capacity</span>
      </p>
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

      <p v-if="!summary.complete" class="storage-monitor__notice iz-inset">
        Some usage or quota measurements are unavailable. Totals include known measurements only.
      </p>

      <div class="storage-monitor__lists">
        <StorageEntityList title="Private" empty-text="No organization members found." :items="storage.users || []" />
        <StorageEntityList title="Public" empty-text="No projects found." :items="storage.projects || []" />
      </div>
    </template>
  </div>
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
    embedded: { type: Boolean, default: false },
  },
  computed: {
    summary: function () {
      return this.storage.summary || { private: {}, shared: {} };
    },
    attentionCount: function () {
      var thresholds = this.storage.thresholds || {};
      return (thresholds.warningCount || 0) + (thresholds.criticalCount || 0);
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
/* Layout only. Surface, radius and padding come from .iz-panel / --flush; the
   figure, meter, metrics, notice and state boxes are all primitives. */
.storage-monitor__headline { display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--spacing-sm); margin: 0; color: var(--color-text-secondary); }
/* --iz-fs-xl, not --iz-fs-2xl: 2xl is the ramp's page-level figure and, two
   levels into Insights, outranks the .iz-metric__value row right beneath it. */
.storage-monitor__headline strong { color: var(--color-text-primary); font-size: var(--iz-fs-xl); font-variant-numeric: tabular-nums; }
.storage-monitor__meter { margin-top: var(--spacing-sm); width: 100%; }
.storage-monitor__metrics { margin-top: var(--spacing-md); }
.storage-monitor__notice { margin-top: var(--spacing-md); margin-bottom: 0; }
.storage-monitor__lists { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-lg); }
/* .iz-empty / .iz-error supply the box; only the inline arrangement is local. */
.storage-monitor__state { display: flex; align-items: center; justify-content: center; gap: var(--spacing-sm); }

/* The breakpoint is on the viewport but the section now sits inside Insights'
   own padding (page 1200 − 2×24, then body − 2×24), so the columns run out of
   room before the viewport does. */
@media (max-width: 900px) {
  .storage-monitor__lists { grid-template-columns: 1fr; }
}
</style>
