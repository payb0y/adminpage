<template>
  <div class="resources-kpi">
    <div class="resources-kpi__header">
      <div class="resources-kpi__icon">
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
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
          <line x1="8" y1="21" x2="16" y2="21" />
          <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
      </div>
      <span class="resources-kpi__title">Resources</span>
    </div>

    <div class="resources-kpi__grid">
      <!-- Whiteboards -->
      <div class="resources-kpi__block">
        <div class="resources-kpi__block-icon resources-kpi__block-icon--purple">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
          </svg>
        </div>
        <div class="resources-kpi__block-data">
          <span class="resources-kpi__block-value">{{ whiteboards }}</span>
          <span class="resources-kpi__block-label">Whiteboards</span>
        </div>
      </div>

      <!-- Scrumban Boards -->
      <div class="resources-kpi__block">
        <div class="resources-kpi__block-icon resources-kpi__block-icon--blue">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><line x1="3" y1="9" x2="21" y2="9" /><line x1="9" y1="21" x2="9" y2="9" />
          </svg>
        </div>
        <div class="resources-kpi__block-data">
          <span class="resources-kpi__block-value">{{ scrumbanBoards }}</span>
          <span class="resources-kpi__block-label">Scrumban Boards</span>
        </div>
      </div>

      <!-- Files -->
      <div class="resources-kpi__block">
        <div class="resources-kpi__block-icon resources-kpi__block-icon--green">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><polyline points="13 2 13 9 20 9" />
          </svg>
        </div>
        <div class="resources-kpi__block-data">
          <span class="resources-kpi__block-value">{{ filesTotal }}</span>
          <span class="resources-kpi__block-label">Files</span>
          <span class="resources-kpi__block-sub">
            {{ filesPublic }} public · {{ filesPrivate }} private
          </span>
        </div>
      </div>

      <!-- Notes -->
      <div class="resources-kpi__block">
        <div class="resources-kpi__block-icon resources-kpi__block-icon--amber">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />
          </svg>
        </div>
        <div class="resources-kpi__block-data">
          <span class="resources-kpi__block-value">{{ notesTotal }}</span>
          <span class="resources-kpi__block-label">Notes</span>
          <span class="resources-kpi__block-sub">
            {{ notesPublic }} public · {{ notesPrivate }} private
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ResourcesKpiCard",
  props: {
    kpi: {
      type: Object,
      required: true,
    },
  },
  computed: {
    metricsMap: function () {
      var map = {};
      (this.kpi.metrics || []).forEach(function (m) {
        map[m.label] = m.value;
      });
      return map;
    },
    whiteboards: function () {
      return parseInt(this.metricsMap["Whiteboards"], 10) || 0;
    },
    scrumbanBoards: function () {
      return parseInt(this.metricsMap["Scrumban Boards"], 10) || 0;
    },
    filesParts: function () {
      return this.parsePubPriv(this.metricsMap["Files"] || "0 pub / 0 priv");
    },
    filesPublic: function () { return this.filesParts.pub; },
    filesPrivate: function () { return this.filesParts.priv; },
    filesTotal: function () { return this.filesPublic + this.filesPrivate; },
    notesParts: function () {
      return this.parsePubPriv(this.metricsMap["Notes"] || "0 pub / 0 priv");
    },
    notesPublic: function () { return this.notesParts.pub; },
    notesPrivate: function () { return this.notesParts.priv; },
    notesTotal: function () { return this.notesPublic + this.notesPrivate; },
  },
  methods: {
    parsePubPriv: function (val) {
      // Expected format: "12 pub / 3 priv"
      var parts = String(val).split("/");
      var pub = parseInt(parts[0], 10) || 0;
      var priv = parts.length > 1 ? parseInt(parts[1], 10) || 0 : 0;
      return { pub: pub, priv: priv };
    },
  },
};
</script>

<style scoped>
.resources-kpi {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: 20px 24px;
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.resources-kpi:hover {
  box-shadow: var(--shadow-card-hover, 0 4px 12px rgba(0, 0, 0, 0.1));
}

.resources-kpi__header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.resources-kpi__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: rgba(139, 92, 246, 0.1);
  color: #8B5CF6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.resources-kpi__title {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

/* ── 2×2 Grid ── */
.resources-kpi__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.resources-kpi__block {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px;
  background: var(--bg-page, #f0f1f5);
  border-radius: 10px;
}

.resources-kpi__block-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.resources-kpi__block-icon--purple {
  background: rgba(139, 92, 246, 0.12);
  color: #8B5CF6;
}

.resources-kpi__block-icon--blue {
  background: rgba(74, 144, 217, 0.12);
  color: #4A90D9;
}

.resources-kpi__block-icon--green {
  background: rgba(34, 197, 94, 0.12);
  color: #16A34A;
}

.resources-kpi__block-icon--amber {
  background: rgba(245, 158, 11, 0.12);
  color: #D97706;
}

.resources-kpi__block-data {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.resources-kpi__block-value {
  font-size: 20px;
  font-weight: 800;
  color: var(--color-text-primary, #1a1a2e);
  line-height: 1.1;
}

.resources-kpi__block-label {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 500;
}

.resources-kpi__block-sub {
  font-size: 10px;
  color: var(--color-text-muted, #9ca3af);
  font-weight: 400;
  margin-top: 2px;
}
</style>
