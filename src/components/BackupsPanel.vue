<template>
  <div :class="['backups-panel', { 'backups-panel--embedded': embedded }]">
    <!-- Empty state -->
    <div v-if="!jobs || jobs.length === 0" class="backups-panel__empty">
      <p class="backups-panel__empty-text">No backup jobs found.</p>
    </div>

    <!-- Backup jobs table -->
    <div v-else class="backups-panel__table-wrap">
      <table class="backups-panel__table">
        <thead>
          <tr>
            <th>Status</th>
            <th>Type</th>
            <th>Trigger</th>
            <th>Artifact</th>
            <th>Size</th>
            <th>Created</th>
            <th>Duration</th>
            <th>Expires</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="job in jobs"
            :key="job.jobId"
            class="backups-panel__row"
          >
            <!-- Status badge -->
            <td>
              <span
                class="backups-panel__badge"
                :class="'backups-panel__badge--' + job.status"
              >{{ job.status }}</span>
            </td>

            <!-- Backup type -->
            <td>
              <span
                class="backups-panel__type"
                :class="'backups-panel__type--' + job.backupType"
              >{{ job.backupType }}</span>
            </td>

            <!-- Trigger source -->
            <td>
              <span class="backups-panel__trigger">
                <svg
                  v-if="job.triggerSource === 'scheduled'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="13"
                  height="13"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12 6 12 12 16 14" />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  width="13"
                  height="13"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                {{ job.triggerSource }}
              </span>
            </td>

            <!-- Artifact name -->
            <td>
              <span
                v-if="job.artifactName"
                class="backups-panel__artifact"
                :title="job.artifactName"
              >{{ truncateArtifact(job.artifactName) }}</span>
              <span v-else class="backups-panel__muted">&mdash;</span>
            </td>

            <!-- Size -->
            <td>
              <span v-if="job.artifactSize" class="backups-panel__size">{{ formatSize(job.artifactSize) }}</span>
              <span v-else class="backups-panel__muted">&mdash;</span>
            </td>

            <!-- Created -->
            <td>
              <span class="backups-panel__date">{{ formatDate(job.createdAt) }}</span>
            </td>

            <!-- Duration -->
            <td>
              <span v-if="job.startedAt && job.finishedAt" class="backups-panel__duration">{{ formatDuration(job.startedAt, job.finishedAt) }}</span>
              <span v-else-if="job.status === 'running'" class="backups-panel__badge backups-panel__badge--running">running</span>
              <span v-else class="backups-panel__muted">&mdash;</span>
            </td>

            <!-- Expires -->
            <td>
              <span
                v-if="job.expiresAt"
                class="backups-panel__date"
                :class="{ 'backups-panel__date--expiring': isExpiringSoon(job.expiresAt) }"
              >{{ formatDate(job.expiresAt) }}</span>
              <span v-else class="backups-panel__muted">&mdash;</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BackupsPanel',
  props: {
    embedded: {
      type: Boolean,
      default: false,
    },
    jobs: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  methods: {
    formatSize: function (bytes) {
      if (!bytes || bytes === 0) return '0 B';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
      if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
      return (bytes / 1073741824).toFixed(2) + ' GB';
    },
    formatDate: function (dateStr) {
      if (!dateStr) return '';
      var d = new Date(dateStr.replace(' ', 'T'));
      if (isNaN(d.getTime())) return dateStr;
      var month = d.toLocaleString('en', { month: 'short' });
      var day = d.getDate();
      var hours = String(d.getHours()).padStart(2, '0');
      var mins = String(d.getMinutes()).padStart(2, '0');
      return month + ' ' + day + ', ' + hours + ':' + mins;
    },
    formatDuration: function (startStr, endStr) {
      var start = new Date(startStr.replace(' ', 'T'));
      var end = new Date(endStr.replace(' ', 'T'));
      var diffSec = Math.round((end - start) / 1000);
      if (diffSec < 0) return '—';
      if (diffSec < 60) return diffSec + 's';
      var mins = Math.floor(diffSec / 60);
      var secs = diffSec % 60;
      if (mins < 60) return mins + 'm ' + secs + 's';
      var hrs = Math.floor(mins / 60);
      mins = mins % 60;
      return hrs + 'h ' + mins + 'm';
    },
    truncateArtifact: function (name) {
      if (!name) return '';
      if (name.length <= 30) return name;
      return name.substring(0, 14) + '...' + name.substring(name.length - 13);
    },
    isExpiringSoon: function (expiresAt) {
      if (!expiresAt) return false;
      var exp = new Date(expiresAt.replace(' ', 'T'));
      var now = new Date();
      var hoursLeft = (exp - now) / (1000 * 60 * 60);
      return hoursLeft >= 0 && hoursLeft < 24;
    },
  },
};
</script>

<style scoped>
.backups-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  padding: var(--spacing-lg, 24px);
}

.backups-panel--embedded {
  background: none;
  border-radius: 0;
  box-shadow: none;
  padding: 0;
}

/* ─── Empty State ─── */
.backups-panel__empty {
  text-align: center;
  padding: var(--spacing-lg, 24px) 0;
}

.backups-panel__empty-text {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  margin: 0;
}

/* ─── Table ─── */
.backups-panel__table-wrap {
  overflow-x: auto;
}

.backups-panel__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.backups-panel__table th {
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-muted, #9ca3af);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0 10px 10px;
  text-align: left;
  white-space: nowrap;
  border-bottom: 1px solid #eef1f5;
}

.backups-panel__row {
  transition: background 0.12s;
}

.backups-panel__row:hover {
  background: #fafbfd;
}

.backups-panel__row td {
  padding: 10px;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
  white-space: nowrap;
}

.backups-panel__row:last-child td {
  border-bottom: none;
}

/* ─── Status Badge ─── */
.backups-panel__badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 8px;
  text-transform: capitalize;
}

.backups-panel__badge--completed {
  background: var(--color-badge-success-bg, #d4edda);
  color: var(--color-badge-success-text, #166534);
}

.backups-panel__badge--failed,
.backups-panel__badge--error {
  background: var(--color-badge-danger-bg, #fde8e8);
  color: var(--color-badge-danger-text, #b91c1c);
}

.backups-panel__badge--running,
.backups-panel__badge--pending,
.backups-panel__badge--queued {
  background: #e8f0fe;
  color: #1e4a8a;
}

.backups-panel__badge--expired {
  background: #f0f1f5;
  color: var(--color-text-muted, #9ca3af);
}

/* ─── Type Pill ─── */
.backups-panel__type {
  display: inline-block;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 8px;
  text-transform: capitalize;
}

.backups-panel__type--full {
  background: #e8f0fe;
  color: #1e4a8a;
}

.backups-panel__type--incremental {
  background: #fef3cd;
  color: #92400e;
}

/* ─── Trigger ─── */
.backups-panel__trigger {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
  text-transform: capitalize;
}

.backups-panel__trigger svg {
  opacity: 0.6;
}

/* ─── Artifact ─── */
.backups-panel__artifact {
  font-size: 11px;
  font-family: 'SF Mono', Monaco, 'Cascadia Code', Consolas, monospace;
  color: var(--color-text-secondary, #6b7280);
}

/* ─── Size ─── */
.backups-panel__size {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
}

/* ─── Dates ─── */
.backups-panel__date {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

.backups-panel__date--expiring {
  color: var(--color-badge-warning-text, #92400e);
  font-weight: 600;
}

/* ─── Duration ─── */
.backups-panel__duration {
  font-size: 12px;
  color: var(--color-text-secondary, #6b7280);
}

/* ─── Muted placeholder ─── */
.backups-panel__muted {
  color: var(--color-text-muted, #9ca3af);
  font-size: 12px;
}
</style>
