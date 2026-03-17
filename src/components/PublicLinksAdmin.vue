<template>
  <section class="public-links-admin">
    <!-- ── Collapsible Header ── -->
    <div class="public-links-admin__header" @click="collapsed = !collapsed">
      <h3 class="public-links-admin__title">
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
          <path
            d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"
          />
          <path
            d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
          />
        </svg>
        Public Dashboard Links
      </h3>
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
        class="public-links-admin__chevron"
        :class="{ 'public-links-admin__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="!collapsed" class="public-links-admin__body">
      <p class="public-links-admin__desc">
        Share a read-only view of KPIs and project performance with anyone — no
        login required.
      </p>

      <div class="public-links-admin__create-card">
        <div class="public-links-admin__form-row">
          <div
            class="public-links-admin__field public-links-admin__field--label"
          >
            <label for="pl-label">Label (optional)</label>
            <input
              id="pl-label"
              v-model="newLabel"
              type="text"
              placeholder="e.g. Client review"
            />
          </div>

          <div
            class="public-links-admin__field public-links-admin__field--datetime"
          >
            <label for="pl-expires">Expires at (optional)</label>
            <div
              class="public-links-admin__datetime-wrap"
              @click="openDatePicker"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="public-links-admin__datetime-icon"
              >
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
              <input
                id="pl-expires"
                ref="dateInput"
                v-model="newExpiresAt"
                type="datetime-local"
                step="60"
                @click.stop
              />
              <button
                v-if="newExpiresAt"
                class="public-links-admin__datetime-clear"
                title="Clear date"
                @click.stop="newExpiresAt = ''"
              >
                ✕
              </button>
            </div>
          </div>

          <button
            class="public-links-admin__btn public-links-admin__btn--create"
            :disabled="creating"
            @click="createLink"
          >
            {{ creating ? "Creating…" : "+ Create Link" }}
          </button>
        </div>
      </div>

      <!-- Status Filter -->
      <div v-if="!loading && links.length > 0" class="public-links-admin__filter-row">
        <label class="public-links-admin__filter-label">Filter by status</label>
        <select v-model="statusFilter" class="public-links-admin__filter-select">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="revoked">Revoked</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <div v-if="loading" class="public-links-admin__state">Loading links…</div>
      <div v-else-if="error" class="public-links-admin__error">{{ error }}</div>
      <div v-else-if="links.length === 0" class="public-links-admin__state">
        No public links yet. Create one above.
      </div>
      <div v-else-if="filteredLinks.length === 0" class="public-links-admin__state">
        No links match the selected filter.
      </div>
      <table v-else class="public-links-admin__table">
        <thead>
          <tr>
            <th>Label</th>
            <th>URL</th>
            <th>Status</th>
            <th>Expires</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="link in filteredLinks"
            :key="link.id"
            :class="{
              'public-links-admin__row--disabled':
                !link.enabled || link.expired,
            }"
          >
            <td>{{ link.label || "—" }}</td>
            <td class="public-links-admin__url-cell">
              <code
                class="public-links-admin__url-link"
                :class="{
                  'public-links-admin__url-link--copied':
                    copiedLinkId === link.id,
                }"
                :title="
                  copiedLinkId === link.id ? 'Copied!' : 'Click to copy URL'
                "
                @click="copyLink(link.id, link.token)"
              >
                <span class="public-links-admin__url-text">{{
                  buildUrl(link.token)
                }}</span>
                <span
                  v-if="copiedLinkId === link.id"
                  class="public-links-admin__url-copied-badge"
                  >Copied!</span
                >
              </code>
            </td>
            <td>
              <span
                v-if="!link.enabled"
                class="public-links-admin__badge public-links-admin__badge--revoked"
                >Revoked</span
              >
              <span
                v-else-if="link.expired"
                class="public-links-admin__badge public-links-admin__badge--expired"
                >Expired</span
              >
              <span
                v-else
                class="public-links-admin__badge public-links-admin__badge--active"
                >Active</span
              >
            </td>
            <td>
              {{ link.expires_at ? formatDateTime(link.expires_at) : "Never" }}
            </td>
            <td>{{ formatDateTime(link.created_at) }}</td>
            <td class="public-links-admin__actions-cell">
              <button
                v-if="link.enabled && !link.expired"
                class="public-links-admin__btn public-links-admin__btn--revoke"
                :disabled="revoking === link.id"
                @click="revokeLink(link.id)"
              >
                {{ revoking === link.id ? "Revoking…" : "Revoke" }}
              </button>
              <button
                class="public-links-admin__btn public-links-admin__btn--delete"
                :disabled="deleting === link.id"
                @click="deleteLink(link.id)"
              >
                {{ deleting === link.id ? "Deleting…" : "Delete" }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "PublicLinksAdmin",
  data() {
    return {
      collapsed: true,
      links: [],
      loading: true,
      error: null,
      newLabel: "",
      newExpiresAt: "",
      creating: false,
      revoking: null,
      deleting: null,
      copiedLinkId: null,
      statusFilter: "",
    };
  },
  computed: {
    filteredLinks() {
      if (!this.statusFilter) return this.links;
      return this.links.filter((link) => {
        if (this.statusFilter === "active") return link.enabled && !link.expired;
        if (this.statusFilter === "revoked") return !link.enabled;
        if (this.statusFilter === "expired") return link.enabled && link.expired;
        return true;
      });
    },
  },
  mounted() {
    this.fetchLinks();
  },
  methods: {
    async fetchLinks() {
      this.loading = true;
      this.error = null;
      try {
        const url = generateUrl("/apps/adminpage/api/public-links");
        const response = await axios.get(url);
        this.links = response.data;
      } catch (e) {
        console.error("Failed to load public links", e);
        this.error =
          e.response?.data?.error || e.message || "Failed to load links";
      } finally {
        this.loading = false;
      }
    },

    async createLink() {
      this.creating = true;
      try {
        const url = generateUrl("/apps/adminpage/api/public-links");
        const params = {};
        if (this.newLabel) params.label = this.newLabel;
        if (this.newExpiresAt) params.expiresAt = this.newExpiresAt;
        await axios.post(url, params);
        this.newLabel = "";
        this.newExpiresAt = "";
        await this.fetchLinks();
      } catch (e) {
        console.error("Failed to create public link", e);
        alert(e.response?.data?.error || "Failed to create link");
      } finally {
        this.creating = false;
      }
    },

    async revokeLink(id) {
      if (
        !confirm("Revoke this public link? It will stop working immediately.")
      )
        return;
      this.revoking = id;
      try {
        const url = generateUrl(`/apps/adminpage/api/public-links/${id}`);
        await axios.delete(url);
        await this.fetchLinks();
      } catch (e) {
        console.error("Failed to revoke link", e);
        alert(e.response?.data?.error || "Failed to revoke link");
      } finally {
        this.revoking = null;
      }
    },

    async deleteLink(id) {
      if (!confirm("Permanently delete this link? This cannot be undone."))
        return;
      this.deleting = id;
      try {
        const url = generateUrl(`/apps/adminpage/api/public-links/${id}/delete`);
        await axios.post(url);
        await this.fetchLinks();
      } catch (e) {
        console.error("Failed to delete link", e);
        alert(e.response?.data?.error || "Failed to delete link");
      } finally {
        this.deleting = null;
      }
    },

    openDatePicker() {
      const input = this.$refs.dateInput;
      if (input) {
        input.focus();
        if (typeof input.showPicker === "function") {
          try {
            input.showPicker();
          } catch (e) {
            /* ignore */
          }
        }
      }
    },

    buildUrl(token) {
      return (
        window.location.origin + generateUrl(`/apps/adminpage/public/${token}`)
      );
    },

    async copyLink(linkId, token) {
      const url = this.buildUrl(token);
      try {
        await navigator.clipboard.writeText(url);
      } catch (error) {
        const textArea = document.createElement("textarea");
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
      }

      this.copiedLinkId = linkId;
      setTimeout(() => {
        if (this.copiedLinkId === linkId) {
          this.copiedLinkId = null;
        }
      }, 1500);
    },

    formatDateTime(dateStr) {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      return d.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
  },
};
</script>

<style scoped>
.public-links-admin {
  background: var(--bg-card, #ffffff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* ─── Collapsible Header ─── */
.public-links-admin__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.public-links-admin__header:hover {
  background: #fafbfd;
}

.public-links-admin__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
}

.public-links-admin__title svg {
  color: #4a90d9;
}

.public-links-admin__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.public-links-admin__chevron--rotated {
  transform: rotate(180deg);
}

/* ─── Body ─── */
.public-links-admin__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

.public-links-admin__desc {
  color: var(--color-text-secondary, #6b7280);
  font-size: 13px;
  margin: 0 0 16px;
}

/* ─── Create Card ─── */
.public-links-admin__create-card {
  padding: 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  background: #fafbfc;
  margin-bottom: 16px;
}

.public-links-admin__form-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}

.public-links-admin__field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.public-links-admin__field--label {
  flex: 1.2;
}

.public-links-admin__field--datetime {
  flex: 1;
}

.public-links-admin__field label {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-secondary, #6b7280);
}

.public-links-admin__field input[type="text"] {
  padding: 8px 10px;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 6px;
  font-size: 13px;
  background: #fff;
}

/* ─── DateTime picker (matching perf-panel style) ─── */
.public-links-admin__datetime-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  background: var(--color-main-background, #fff);
  transition: border-color 0.15s, box-shadow 0.15s;
  cursor: pointer;
}

.public-links-admin__datetime-wrap:hover {
  border-color: #2766e5;
  box-shadow: 0 1px 4px rgba(39, 102, 229, 0.1);
}

.public-links-admin__datetime-icon {
  color: #2766e5;
  flex-shrink: 0;
}

.public-links-admin__datetime-wrap input[type="datetime-local"] {
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-primary, #1a1a2e);
  outline: none;
  padding: 0;
  margin: 0;
  min-width: 170px;
  cursor: pointer;
}

.public-links-admin__datetime-wrap
  input[type="datetime-local"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.5;
}

.public-links-admin__datetime-wrap
  input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
}

.public-links-admin__datetime-clear {
  background: none;
  border: none;
  color: var(--color-text-muted, #9ca3af);
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
  padding: 2px 4px;
  border-radius: 4px;
  transition: background 0.15s, color 0.15s;
}

.public-links-admin__datetime-clear:hover {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
}

/* ─── Buttons ─── */
.public-links-admin__btn {
  padding: 7px 16px;
  border: 1px solid transparent;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}

.public-links-admin__btn--create {
  background: #2766e5;
  color: #fff;
}
.public-links-admin__btn--create:hover {
  background: #1f55c7;
}
.public-links-admin__btn--create:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.public-links-admin__btn--revoke {
  background: #fff;
  border-color: #fecaca;
  color: #b91c1c;
}
.public-links-admin__btn--revoke:hover {
  background: #fef2f2;
}
.public-links-admin__btn--revoke:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ─── Table ─── */
.public-links-admin__table {
  width: 100%;
  border-collapse: collapse;
  background: var(--bg-card, #ffffff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  overflow: hidden;
  font-size: 13px;
}

.public-links-admin__table th {
  text-align: left;
  padding: 10px 14px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary, #6b7280);
  background: #f9fafb;
  border-bottom: 1px solid var(--color-border, #e5e7eb);
}

.public-links-admin__table td {
  padding: 10px 14px;
  border-bottom: 1px solid #f3f4f6;
  color: var(--color-text-primary, #1a1a2e);
}

.public-links-admin__row--disabled td {
  opacity: 0.5;
}

.public-links-admin__url-cell {
  max-width: 260px;
  overflow: hidden;
}

.public-links-admin__url-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  max-width: 100%;
}

.public-links-admin__url-link:hover {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #2766e5;
}

.public-links-admin__url-link--copied {
  background: #d1fae5;
  border-color: #34d399;
  color: #059669;
}

.public-links-admin__url-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.public-links-admin__url-copied-badge {
  font-size: 10px;
  font-weight: 600;
  color: #059669;
  flex-shrink: 0;
  animation: publicLinksCopiedFade 0.3s ease-out;
}

@keyframes publicLinksCopiedFade {
  0% {
    opacity: 0;
    transform: translateX(-4px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

/* ─── Badges ─── */
.public-links-admin__badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
}

.public-links-admin__badge--active {
  background: #d1fae5;
  color: #065f46;
}

.public-links-admin__badge--revoked {
  background: #fee2e2;
  color: #b91c1c;
}

.public-links-admin__badge--expired {
  background: #fef3c7;
  color: #92400e;
}

/* ─── States ─── */
.public-links-admin__state {
  padding: 24px;
  text-align: center;
  color: var(--color-text-secondary, #6b7280);
  font-size: 13px;
  background: #fafbfc;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
}

.public-links-admin__error {
  padding: 16px;
  background: #fee2e2;
  color: #b91c1c;
  border-radius: 8px;
  font-size: 13px;
}

/* ─── Filter Row ─── */
.public-links-admin__filter-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.public-links-admin__filter-label {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-secondary, #6b7280);
}

.public-links-admin__filter-select {
  padding: 5px 10px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 6px;
  font-size: 13px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s;
}

.public-links-admin__filter-select:focus {
  border-color: #2766e5;
}

/* ─── Actions Cell ─── */
.public-links-admin__actions-cell {
  display: flex;
  gap: 6px;
  align-items: center;
}

.public-links-admin__btn--delete {
  background: #fff;
  border-color: #e5e7eb;
  color: #6b7280;
}
.public-links-admin__btn--delete:hover {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}
.public-links-admin__btn--delete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.public-links-admin__inactive {
  color: #d1d5db;
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
  .public-links-admin__header {
    padding: 14px 14px;
  }

  .public-links-admin__body {
    padding: 0 14px 14px;
  }

  .public-links-admin__form-row {
    flex-direction: column;
    align-items: stretch;
  }

  .public-links-admin__btn--create {
    width: 100%;
  }

  .public-links-admin__table {
    font-size: 12px;
  }

  .public-links-admin__table th,
  .public-links-admin__table td {
    padding: 8px 10px;
  }
}
</style>
