<template>
  <section class="storage-list">
    <header class="storage-list__header">
      <h3>{{ title }}</h3>
      <span>{{ items.length }}</span>
    </header>
    <p v-if="items.length === 0" class="storage-list__empty">{{ emptyText }}</p>
    <ol v-else class="storage-list__items">
      <li v-for="item in sortedItems" :key="item.id" class="storage-list__item">
        <div class="storage-list__identity">
          <strong :title="item.name">{{ item.name }}</strong>
          <span :class="['iz-pill', pillClass(item.status)]">{{ statusLabel(item.status) }}</span>
        </div>
        <div class="storage-list__amount">
          <span>{{ formatBytes(item.usedBytes) }}</span>
          <span>of {{ formatBytes(item.quotaBytes) }}</span>
        </div>
        <div class="storage-list__meter iz-meter" role="progressbar" :aria-label="item.name + ' storage used'" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="item.percentage || 0">
          <span :class="['iz-meter__fill', meterClass(item.status)]" :style="{ width: meterWidth(item.percentage) }"></span>
        </div>
      </li>
    </ol>
  </section>
</template>

<script>
export default {
  name: "StorageEntityList",
  props: {
    title: { type: String, required: true },
    emptyText: { type: String, required: true },
    items: { type: Array, default: function () { return []; } },
  },
  computed: {
    sortedItems: function () {
      return this.items.slice().sort(function (a, b) {
        return (b.percentage === null ? -1 : b.percentage) - (a.percentage === null ? -1 : a.percentage);
      });
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
    meterWidth: function (percentage) { return Math.min(100, Math.max(0, percentage || 0)) + "%"; },
    pillClass: function (status) {
      return { healthy: "iz-pill--success", warning: "iz-pill--warning", critical: "iz-pill--danger" }[status] || "iz-pill--neutral";
    },
    meterClass: function (status) {
      return { healthy: "iz-meter__fill--ok", warning: "iz-meter__fill--warn", critical: "iz-meter__fill--danger" }[status] || "iz-meter__fill--neutral";
    },
    statusLabel: function (status) {
      return { healthy: "Healthy", warning: "Over 80%", critical: "At capacity" }[status] || "Unavailable";
    },
  },
};
</script>

<style scoped>
.storage-list { min-width: 0; }
.storage-list__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-sm); }
.storage-list__header h3 { margin: 0; padding: 0; border: 0; color: var(--color-text-primary); }
.storage-list__header span, .storage-list__empty { color: var(--color-text-secondary); }
.storage-list__items { list-style: none; padding: 0; margin: 0; display: grid; gap: var(--spacing-sm); }
.storage-list__item { border-top: 1px solid var(--color-border); padding-top: var(--spacing-sm); }
.storage-list__identity, .storage-list__amount { display: flex; justify-content: space-between; align-items: center; gap: var(--spacing-sm); }
.storage-list__identity strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--color-text-primary); }
.storage-list__amount { margin-top: var(--spacing-xs); color: var(--color-text-secondary); font-variant-numeric: tabular-nums; }
.storage-list__meter { width: 100%; margin-top: var(--spacing-xs); }
</style>
