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
            v-for="link in pagedLinks"
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

      <!-- Pagination. Rendered whenever there is more than one page — the
           chrome is the theme's .iz-pagination primitive, the windowing is
           local. -->
      <div v-if="!loading && !error && pageCount > 1" class="iz-pagination">
        <span>{{ rangeLabel }}</span>
        <div class="iz-pagination__pages">
          <button
            class="iz-btn"
            :disabled="page === 1"
            aria-label="Previous page"
            @click="goToPage(page - 1)"
          >
            ‹
          </button>
          <template v-for="(p, i) in pageItems">
            <span
              v-if="p === '…'"
              :key="'gap-' + i"
              class="public-links-admin__page-gap"
              >…</span
            >
            <button
              v-else
              :key="'pg-' + p"
              class="iz-btn"
              :class="{ 'iz-btn--active': p === page }"
              :aria-current="p === page ? 'page' : null"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
          </template>
          <button
            class="iz-btn"
            :disabled="page === pageCount"
            aria-label="Next page"
            @click="goToPage(page + 1)"
          >
            ›
          </button>
        </div>
      </div>
    </div>

    <ConfirmDialog
      v-if="dialog"
      :title="dialog.title"
      :message="dialog.message"
      :confirm-label="dialog.confirmLabel"
      :busy-label="dialog.busyLabel"
      :danger="dialog.danger"
      :alert-only="dialog.alertOnly"
      :busy="dialogBusy"
      :error="dialogError"
      @confirm="onDialogConfirm"
      @cancel="closeDialog"
    />
  </section>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

import ConfirmDialog from "./ConfirmDialog.vue";

export default {
  name: "PublicLinksAdmin",
  components: { ConfirmDialog },
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
      page: 1,
      pageSize: 10,
      // The single dialog this panel drives. `dialog.action` is the thing to
      // run on confirm; null means nothing is open.
      dialog: null,
      dialogBusy: false,
      dialogError: "",
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
    pageCount() {
      return Math.max(1, Math.ceil(this.filteredLinks.length / this.pageSize));
    },
    pagedLinks() {
      const start = (this.page - 1) * this.pageSize;
      return this.filteredLinks.slice(start, start + this.pageSize);
    },
    rangeLabel() {
      const total = this.filteredLinks.length;
      const start = (this.page - 1) * this.pageSize + 1;
      return `${start}–${Math.min(start + this.pageSize - 1, total)} of ${total}`;
    },
    /**
     * Page numbers to render, with "…" where the run is elided.
     *
     * Up to seven pages are shown in full; beyond that the list is windowed
     * around the current page so the control keeps a stable width instead of
     * growing a row of buttons as links accumulate. First and last are always
     * reachable in one click.
     */
    pageItems() {
      const last = this.pageCount;
      if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
      }
      const around = [this.page - 1, this.page, this.page + 1].filter(
        (p) => p > 1 && p < last,
      );
      const items = [1];
      if (around[0] > 2) items.push("…");
      items.push(...around);
      if (around[around.length - 1] < last - 1) items.push("…");
      items.push(last);
      return items;
    },
  },
  watch: {
    // Filtering changes the result set under the cursor; jumping back to the
    // first page is less surprising than landing on a page that no longer
    // exists.
    statusFilter() {
      this.page = 1;
    },
    // Revoking or deleting can shrink the list past the current page — clamp
    // rather than render an empty table.
    pageCount(count) {
      if (this.page > count) this.page = count;
    },
  },
  mounted() {
    this.fetchLinks();
  },
  methods: {
    /** Open the shared dialog. `action` is awaited on confirm. */
    ask(opts) {
      this.dialogError = "";
      this.dialogBusy = false;
      this.dialog = opts;
    },
    /** Notice-only variant — this is what used to be alert(). */
    notify(title, message) {
      this.ask({
        title,
        message,
        confirmLabel: "OK",
        alertOnly: true,
        action: null,
      });
    },
    closeDialog() {
      this.dialog = null;
      this.dialogError = "";
      this.dialogBusy = false;
    },
    async onDialogConfirm() {
      const action = this.dialog && this.dialog.action;
      if (!action) {
        this.closeDialog();
        return;
      }
      this.dialogBusy = true;
      this.dialogError = "";
      try {
        await action();
        this.closeDialog();
      } catch (e) {
        // Stay open and say why, rather than closing and popping a second
        // dialog the user has to dismiss separately.
        this.dialogError =
          e.response?.data?.error || e.message || "Something went wrong";
        this.dialogBusy = false;
      }
    },
    goToPage(p) {
      this.page = Math.min(Math.max(1, p), this.pageCount);
    },
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
        this.notify(
          "Could not create link",
          e.response?.data?.error || "Failed to create link",
        );
      } finally {
        this.creating = false;
      }
    },

    revokeLink(id) {
      this.ask({
        title: "Revoke this link?",
        message:
          "Anyone holding the URL will stop being able to open the dashboard immediately. The link stays in this list and can be deleted separately.",
        confirmLabel: "Revoke link",
        busyLabel: "Revoking…",
        danger: true,
        action: async () => {
          this.revoking = id;
          try {
            const url = generateUrl(`/apps/adminpage/api/public-links/${id}`);
            await axios.delete(url);
            await this.fetchLinks();
          } finally {
            this.revoking = null;
          }
        },
      });
    },

    deleteLink(id) {
      this.ask({
        title: "Delete this link?",
        message:
          "The link is removed from this list and its URL can never be reissued. This cannot be undone.",
        confirmLabel: "Delete link",
        busyLabel: "Deleting…",
        danger: true,
        action: async () => {
          this.deleting = id;
          try {
            const url = generateUrl(
              `/apps/adminpage/api/public-links/${id}/delete`,
            );
            await axios.post(url);
            await this.fetchLinks();
          } finally {
            this.deleting = null;
          }
        },
      });
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
  background: var(--bg-subtle);
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
  color: var(--accent);
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
  background: var(--bg-subtle);
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
  background: var(--bg-card);
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
  border-color: var(--accent);
  box-shadow: 0 1px 4px color-mix(in oklab, var(--accent) 10%, transparent);
}

.public-links-admin__datetime-icon {
  color: var(--accent);
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
  color: var(--color-danger);
  background: var(--color-badge-danger-bg);
}

.public-links-admin__page-gap {
  padding: 0 4px;
  color: var(--color-text-muted);
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
  background: var(--accent);
  color: #fff;
}
.public-links-admin__btn--create:hover {
  background: var(--accent-hover);
}
.public-links-admin__btn--create:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.public-links-admin__btn--revoke {
  background: var(--bg-card);
  border-color: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
}
.public-links-admin__btn--revoke:hover {
  background: var(--color-badge-danger-bg);
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
  background: var(--bg-subtle);
  border-bottom: 1px solid var(--color-border, #e5e7eb);
}

.public-links-admin__table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--bg-subtle);
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
  background: var(--accent-bg);
  border-color: var(--accent-bg);
  color: var(--accent);
}

.public-links-admin__url-link--copied {
  background: var(--color-badge-success-bg);
  border-color: var(--color-success);
  color: var(--color-badge-success-text);
}

.public-links-admin__url-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.public-links-admin__url-copied-badge {
  font-size: 10px;
  font-weight: 600;
  color: var(--color-badge-success-text);
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
  background: var(--color-badge-success-bg);
  color: var(--color-badge-success-text);
}

.public-links-admin__badge--revoked {
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
}

.public-links-admin__badge--expired {
  background: var(--color-badge-warning-bg);
  color: var(--color-badge-warning-text);
}

/* ─── States ─── */
.public-links-admin__state {
  padding: 24px;
  text-align: center;
  color: var(--color-text-secondary, #6b7280);
  font-size: 13px;
  background: var(--bg-subtle);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
}

.public-links-admin__error {
  padding: 16px;
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
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
  background: var(--bg-card);
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s;
}

.public-links-admin__filter-select:focus {
  border-color: var(--accent);
}

/* ─── Actions Cell ─── */
.public-links-admin__actions-cell {
  display: flex;
  gap: 6px;
  align-items: center;
}

.public-links-admin__btn--delete {
  background: var(--bg-card);
  border-color: var(--color-border);
  color: var(--color-text-secondary);
}
.public-links-admin__btn--delete:hover {
  background: var(--color-badge-danger-bg);
  border-color: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
}
.public-links-admin__btn--delete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.public-links-admin__inactive {
  color: var(--color-border-strong);
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
