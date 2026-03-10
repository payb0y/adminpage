<template>
  <div class="public-links-admin">
    <h2 class="public-links-admin__title">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
        <path
          d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
        />
      </svg>
      Public Dashboard Links
    </h2>
    <p class="public-links-admin__desc">
      Share a read-only view of KPIs and project performance with anyone — no
      login required.
    </p>

    <!-- ── Create form ── -->
    <div class="public-links-admin__form">
      <div class="public-links-admin__form-row">
        <div class="public-links-admin__field">
          <label for="pl-label">Label (optional)</label>
          <input
            id="pl-label"
            v-model="newLabel"
            type="text"
            placeholder="e.g. Client review"
          />
        </div>
        <div class="public-links-admin__field">
          <label for="pl-expires">Expires (optional)</label>
          <input id="pl-expires" v-model="newExpiresAt" type="date" />
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

    <!-- ── Just-created banner ── -->
    <div v-if="justCreatedUrl" class="public-links-admin__created-banner">
      <span>Link created!</span>
      <input
        ref="createdUrlInput"
        :value="justCreatedUrl"
        readonly
        class="public-links-admin__url-input"
        @click="selectUrl"
      />
      <button
        class="public-links-admin__btn public-links-admin__btn--copy"
        @click="copyUrl"
      >
        {{ copied ? "✓ Copied" : "Copy" }}
      </button>
    </div>

    <!-- ── Links table ── -->
    <div v-if="loading" class="public-links-admin__loading">Loading links…</div>
    <div v-else-if="error" class="public-links-admin__error">{{ error }}</div>
    <div v-else-if="links.length === 0" class="public-links-admin__empty">
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
          <td>{{ link.expires_at ? formatDate(link.expires_at) : "Never" }}</td>
          <td>{{ formatDate(link.created_at) }}</td>
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
  </div>
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
      justCreatedUrl: null,
      copied: false,
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
        const url = generateUrl("/apps/adminpage/api/admin/public-links");
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
      this.justCreatedUrl = null;
      this.copied = false;
      try {
        const url = generateUrl("/apps/adminpage/api/admin/public-links");
        const params = {};
        if (this.newLabel) params.label = this.newLabel;
        if (this.newExpiresAt)
          params.expiresAt = this.newExpiresAt + "T23:59:59";
        const response = await axios.post(url, params);
        const link = response.data;
        this.justCreatedUrl = this.buildUrl(link.token);
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
        const url = generateUrl(`/apps/adminpage/api/admin/public-links/${id}`);
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

    selectUrl() {
      this.$refs.createdUrlInput?.select();
    },

    copyUrl() {
      navigator.clipboard.writeText(this.justCreatedUrl).then(() => {
        this.copied = true;
        setTimeout(() => {
          this.copied = false;
        }, 2000);
      });
    },

    formatDate(dateStr) {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      return d.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
  },
};
</script>

<style scoped>
.public-links-admin {
  max-width: 900px;
  margin: 32px 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.public-links-admin__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 4px;
}

.public-links-admin__desc {
  color: #6b7280;
  font-size: 13px;
  margin: 0 0 20px;
}

/* ── Form ── */
.public-links-admin__form {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 16px 20px;
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
  flex: 1;
}

.public-links-admin__field label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
}

.public-links-admin__field input {
  padding: 7px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
}

/* ── Buttons ── */
.public-links-admin__btn {
  padding: 7px 16px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}

.public-links-admin__btn--create {
  background: #2563eb;
  color: #fff;
}
.public-links-admin__btn--create:hover {
  background: #1d4ed8;
}
.public-links-admin__btn--create:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.public-links-admin__btn--copy {
  background: #e5e7eb;
  color: #1a1a2e;
}
.public-links-admin__btn--copy:hover {
  background: #d1d5db;
}

.public-links-admin__btn--revoke {
  background: #fee2e2;
  color: #b91c1c;
}
.public-links-admin__btn--revoke:hover {
  background: #fecaca;
}
.public-links-admin__btn--revoke:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── Created banner ── */
.public-links-admin__created-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 8px;
  padding: 10px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  color: #065f46;
}

.public-links-admin__url-input {
  flex: 1;
  padding: 5px 8px;
  border: 1px solid #a7f3d0;
  border-radius: 4px;
  font-size: 12px;
  font-family: monospace;
  background: #fff;
}

/* ── Table ── */
.public-links-admin__table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  border: 1px solid #e5e7eb;
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
  color: #6b7280;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.public-links-admin__table td {
  padding: 10px 14px;
  border-bottom: 1px solid #f3f4f6;
  color: #1a1a2e;
}

.public-links-admin__row--disabled td {
  opacity: 0.5;
}

.public-links-admin__url-cell {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.public-links-admin__url-cell code {
  font-size: 11px;
  color: #6b7280;
}

/* ── Badges ── */
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

/* ── States ── */
.public-links-admin__loading,
.public-links-admin__empty {
  padding: 24px;
  text-align: center;
  color: #6b7280;
  font-size: 13px;
}

.public-links-admin__error {
  padding: 16px;
  background: #fee2e2;
  color: #b91c1c;
  border-radius: 8px;
  font-size: 13px;
}

.public-links-admin__inactive {
  color: #d1d5db;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .public-links-admin__form-row {
    flex-direction: column;
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
