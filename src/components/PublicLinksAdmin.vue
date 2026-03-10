<template>
  <section class="public-links-admin">
    <div class="public-links-admin__header">
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
      <p class="public-links-admin__desc">
        Share a read-only view of KPIs and project performance with anyone — no
        login required.
      </p>
    </div>

    <div class="public-links-admin__create-card">
      <div class="public-links-admin__form-row">
        <div class="public-links-admin__field public-links-admin__field--label">
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
          <input
            id="pl-expires"
            v-model="newExpiresAt"
            type="datetime-local"
            step="60"
          />
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

    <div v-if="loading" class="public-links-admin__state">Loading links…</div>
    <div v-else-if="error" class="public-links-admin__error">{{ error }}</div>
    <div v-else-if="links.length === 0" class="public-links-admin__state">
      No public links yet. Create one above.
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
          v-for="link in links"
          :key="link.id"
          :class="{
            'public-links-admin__row--disabled': !link.enabled || link.expired,
          }"
        >
          <td>{{ link.label || "—" }}</td>
          <td class="public-links-admin__url-cell">
            <code>{{ buildUrl(link.token) }}</code>
            <button
              class="public-links-admin__copy-icon"
              :title="copiedLinkId === link.id ? 'Copied' : 'Copy link URL'"
              @click="copyLink(link.id, link.token)"
            >
              <svg
                v-if="copiedLinkId !== link.id"
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                <path
                  d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"
                />
              </svg>
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </button>
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
          <td>
            <button
              v-if="link.enabled && !link.expired"
              class="public-links-admin__btn public-links-admin__btn--revoke"
              :disabled="revoking === link.id"
              @click="revokeLink(link.id)"
            >
              {{ revoking === link.id ? "Revoking…" : "Revoke" }}
            </button>
            <span v-else class="public-links-admin__inactive">—</span>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "PublicLinksAdmin",
  data() {
    return {
      links: [],
      loading: true,
      error: null,
      newLabel: "",
      newExpiresAt: "",
      creating: false,
      revoking: null,
      copiedLinkId: null,
    };
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
  border: 1px solid var(--color-border, #e5e7eb);
  margin-top: 24px;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.public-links-admin__header {
  padding: 18px 20px 10px;
}

.public-links-admin__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
}

.public-links-admin__desc {
  color: var(--color-text-secondary, #6b7280);
  font-size: 13px;
  margin: 8px 0 0;
}

.public-links-admin__create-card {
  margin: 0 20px 16px;
  padding: 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
  background: #fafbfc;
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

.public-links-admin__field input {
  padding: 8px 10px;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 6px;
  font-size: 13px;
  background: #fff;
}

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

.public-links-admin__table {
  margin: 0 20px 20px;
  width: calc(100% - 40px);
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
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: 220px;
  overflow: hidden;
}

.public-links-admin__url-cell code {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.public-links-admin__copy-icon {
  border: none;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  flex-shrink: 0;
}

.public-links-admin__copy-icon:hover {
  background: #eef2ff;
  color: #2766e5;
}

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

.public-links-admin__state {
  margin: 0 20px 20px;
  padding: 24px;
  text-align: center;
  color: var(--color-text-secondary, #6b7280);
  font-size: 13px;
  background: #fafbfc;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 10px;
}

.public-links-admin__error {
  margin: 0 20px 20px;
  padding: 16px;
  background: #fee2e2;
  color: #b91c1c;
  border-radius: 8px;
  font-size: 13px;
}

.public-links-admin__inactive {
  color: #d1d5db;
}

@media (max-width: 768px) {
  .public-links-admin__header {
    padding: 14px 14px 8px;
  }

  .public-links-admin__create-card,
  .public-links-admin__table,
  .public-links-admin__state,
  .public-links-admin__error {
    margin-left: 14px;
    margin-right: 14px;
  }

  .public-links-admin__table {
    width: calc(100% - 28px);
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
