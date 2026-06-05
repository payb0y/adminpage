<template>
  <component
    :is="embedded ? 'div' : 'section'"
    :class="['members-panel', { 'members-panel--embedded': embedded }]"
  >
    <div
      v-if="!embedded"
      class="members-panel__header"
      @click="collapsed = !collapsed"
    >
      <h3 class="members-panel__title">
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
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        Team Members
        <span class="members-panel__count">{{ members.length }}</span>
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
        class="members-panel__chevron"
        :class="{ 'members-panel__chevron--rotated': collapsed }"
      >
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </div>

    <div v-show="embedded || !collapsed" class="members-panel__body">
      <!-- Filters -->
      <div class="members-panel__filters">
        <input
          v-model="search"
          class="members-panel__search"
          type="text"
          placeholder="Search members…"
        />
        <div class="members-panel__role-badges">
          <span
            class="members-panel__role-badge"
            :class="{ 'members-panel__role-badge--active': roleFilter === '' }"
            @click="
              roleFilter = '';
              currentPage = 1;
            "
            >All</span
          >
          <span
            class="members-panel__role-badge members-panel__role-badge--admin-color"
            :class="{
              'members-panel__role-badge--active': roleFilter === 'admin',
            }"
            @click="
              roleFilter = 'admin';
              currentPage = 1;
            "
            >Admin</span
          >
          <span
            class="members-panel__role-badge members-panel__role-badge--member-color"
            :class="{
              'members-panel__role-badge--active': roleFilter === 'member',
            }"
            @click="
              roleFilter = 'member';
              currentPage = 1;
            "
            >Member</span
          >
        </div>
      </div>

      <div v-if="filteredMembers.length === 0" class="members-panel__empty">
        No members match your filters.
      </div>

      <div
        v-for="member in paginatedMembers"
        :key="'mem-' + member.userId"
        class="members-panel__card"
      >
        <!-- Summary Row (always visible, clickable) -->
        <div class="members-panel__row" @click="toggle(member.userId)">
          <span class="members-panel__avatar">{{
            (member.displayName || member.userId).charAt(0).toUpperCase()
          }}</span>
          <div class="members-panel__info">
            <span class="members-panel__name">{{
              member.displayName || member.userId
            }}</span>
            <span
              class="members-panel__sub"
              v-if="member.title || member.email"
            >
              {{ member.title ? member.title : member.email }}
            </span>
          </div>
          <div class="members-panel__right">
            <!-- Task mini-stats -->
            <span
              v-if="member.assignedTasks > 0"
              class="members-panel__stat-pill"
            >
              {{ member.doneTasks }}/{{ member.assignedTasks }}
              <span class="members-panel__stat-label">tasks</span>
            </span>
            <span
              v-if="member.overdueTasks > 0"
              class="members-panel__stat-pill members-panel__stat-pill--danger"
            >
              {{ member.overdueTasks }}
              <span class="members-panel__stat-label">overdue</span>
            </span>
            <span
              class="members-panel__role"
              :class="'members-panel__role--' + member.role"
            >
              {{ member.role }}
            </span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="members-panel__expand-icon"
              :class="{
                'members-panel__expand-icon--open': expanded[member.userId],
              }"
            >
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </div>
        </div>

        <!-- Expanded Detail -->
        <div v-if="expanded[member.userId]" class="members-panel__detail">
          <div class="members-panel__detail-grid">
            <div class="members-panel__detail-item" v-if="member.userId">
              <span class="members-panel__detail-label">Username</span>
              <span class="members-panel__detail-value">{{
                member.userId
              }}</span>
            </div>
            <div class="members-panel__detail-item" v-if="member.email">
              <span class="members-panel__detail-label">Email</span>
              <span class="members-panel__detail-value">{{
                member.email
              }}</span>
            </div>
            <div class="members-panel__detail-item" v-if="member.phone">
              <span class="members-panel__detail-label">Phone</span>
              <span class="members-panel__detail-value">{{
                member.phone
              }}</span>
            </div>
            <div class="members-panel__detail-item" v-if="member.organisation">
              <span class="members-panel__detail-label">Organisation</span>
              <span class="members-panel__detail-value">{{
                member.organisation
              }}</span>
            </div>
            <div class="members-panel__detail-item" v-if="member.title">
              <span class="members-panel__detail-label">Title / Role</span>
              <span class="members-panel__detail-value">{{
                member.title
              }}</span>
            </div>
            <div class="members-panel__detail-item">
              <span class="members-panel__detail-label">Org Role</span>
              <span
                class="members-panel__detail-value"
                style="text-transform: capitalize"
                >{{ member.role }}</span
              >
            </div>
            <div class="members-panel__detail-item" v-if="member.joinedAt">
              <span class="members-panel__detail-label">Joined</span>
              <span class="members-panel__detail-value">{{
                formatDate(member.joinedAt)
              }}</span>
            </div>
            <div class="members-panel__detail-item" v-if="member.lastActive">
              <span class="members-panel__detail-label">Last Active</span>
              <span class="members-panel__detail-value">{{
                formatDate(member.lastActive)
              }}</span>
            </div>
          </div>

          <!-- Task Stats Bar -->
          <div v-if="member.assignedTasks > 0" class="members-panel__tasks">
            <div class="members-panel__tasks-header">
              <span class="members-panel__tasks-title">Task Performance</span>
              <span class="members-panel__tasks-pct"
                >{{ taskPct(member) }}% complete</span
              >
            </div>
            <div class="members-panel__tasks-bar">
              <div
                class="members-panel__tasks-fill"
                :style="{ width: taskPct(member) + '%' }"
                :class="taskFillClass(member)"
              ></div>
            </div>
            <div class="members-panel__tasks-legend">
              <span class="members-panel__tasks-stat">
                <span
                  class="members-panel__dot members-panel__dot--done"
                ></span>
                {{ member.doneTasks }} done
              </span>
              <span class="members-panel__tasks-stat">
                <span
                  class="members-panel__dot members-panel__dot--open"
                ></span>
                {{
                  member.assignedTasks - member.doneTasks - member.overdueTasks
                }}
                in progress
              </span>
              <span
                v-if="member.overdueTasks > 0"
                class="members-panel__tasks-stat"
              >
                <span
                  class="members-panel__dot members-panel__dot--overdue"
                ></span>
                {{ member.overdueTasks }} overdue
              </span>
            </div>
          </div>
          <div v-else class="members-panel__tasks-empty">
            No task assignments yet
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="members-panel__pagination">
        <button
          class="members-panel__page-btn"
          :disabled="currentPage <= 1"
          @click="currentPage--"
        >
          ‹
        </button>
        <span class="members-panel__page-info">
          {{ currentPage }} / {{ totalPages }}
        </span>
        <button
          class="members-panel__page-btn"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
        >
          ›
        </button>
      </div>
      <div
        v-if="filteredMembers.length > 0"
        class="members-panel__showing-hint"
      >
        Showing {{ paginatedMembers.length }} of
        {{ filteredMembers.length }} members
      </div>
    </div>
  </component>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateOcsUrl } from "@nextcloud/router";

const OCS_HEADERS = {
  "OCS-APIRequest": "true",
  Accept: "application/json",
};

export default {
  name: "MembersPanel",
  props: {
    embedded: {
      type: Boolean,
      default: false,
    },
    members: {
      type: Array,
      default: function () {
        return [];
      },
    },
    orgId: {
      type: Number,
      default: null,
    },
    adminUid: {
      type: String,
      default: null,
    },
    currentUid: {
      type: String,
      default: null,
    },
    ownerUid: {
      type: String,
      default: null,
    },
  },
  data: function () {
    return {
      collapsed: true,
      expanded: {},
      search: "",
      roleFilter: "",
      currentPage: 1,
      pageSize: 5,
      addMode: false,
      addTab: "existing",
      addSearchTerm: "",
      addSearchResults: [],
      addSearchLoading: false,
      addError: null,
      addingUid: null,
      newUser: {
        uid: "",
        displayName: "",
        email: "",
        password: "",
        autoGenerate: true,
      },
      newUserFieldErrors: {},
      newUserBlurred: {},
      newUserSubmitting: false,
      newUserError: null,
      newUserShowPassword: false,
      createdUser: null,
      revealCreatedPassword: false,
      copyFlags: {},
      toast: null,
    };
  },
  created: function () {
    this._addSearchDebounce = null;
    this._addSearchToken = 0;
    this._toastTimer = null;
    this._copyTimers = {};
  },
  beforeDestroy: function () {
    if (this._addSearchDebounce) clearTimeout(this._addSearchDebounce);
    if (this._toastTimer) clearTimeout(this._toastTimer);
    Object.keys(this._copyTimers || {}).forEach((k) => {
      clearTimeout(this._copyTimers[k]);
    });
  },
  computed: {
    filteredMembers: function () {
      var q = (this.search || "").toLowerCase();
      var role = this.roleFilter;
      var result = [];
      for (var i = 0; i < this.members.length; i++) {
        var m = this.members[i];
        if (role && m.role !== role) {
          continue;
        }
        if (q) {
          var name = (m.displayName || "").toLowerCase();
          var uid = (m.userId || "").toLowerCase();
          var email = (m.email || "").toLowerCase();
          if (
            name.indexOf(q) === -1 &&
            uid.indexOf(q) === -1 &&
            email.indexOf(q) === -1
          ) {
            continue;
          }
        }
        result.push(m);
      }
      return result;
    },
    totalPages: function () {
      return Math.max(
        1,
        Math.ceil(this.filteredMembers.length / this.pageSize),
      );
    },
    paginatedMembers: function () {
      var start = (this.currentPage - 1) * this.pageSize;
      return this.filteredMembers.slice(start, start + this.pageSize);
    },
    canAdd: function () {
      return (
        !!this.currentUid &&
        !!this.adminUid &&
        this.currentUid === this.adminUid
      );
    },
    newUserFormValid: function () {
      var nu = this.newUser;
      var uid = (nu.uid || "").trim();
      var display = (nu.displayName || "").trim();
      var email = (nu.email || "").trim();
      if (!uid || !/^[A-Za-z0-9._@-]+$/.test(uid)) return false;
      if (!display) return false;
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return false;
      if (!nu.autoGenerate) {
        if (!nu.password || nu.password.length < 10) return false;
      }
      return true;
    },
  },
  watch: {
    search: function () {
      this.currentPage = 1;
    },
    filteredMembers: function () {
      if (this.currentPage > this.totalPages) {
        this.currentPage = this.totalPages;
      }
    },
    addTab: function (val) {
      if (val === "new" && this.newUser.autoGenerate && !this.newUser.password) {
        this.newUser.password = this.generatePassword();
      }
    },
  },
  methods: {
    toggle: function (uid) {
      this.$set(this.expanded, uid, !this.expanded[uid]);
    },
    formatDate: function (dateStr) {
      if (!dateStr) return "—";
      var d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    },
    taskPct: function (m) {
      if (!m.assignedTasks || m.assignedTasks === 0) return 0;
      return Math.round((m.doneTasks / m.assignedTasks) * 100);
    },
    taskFillClass: function (m) {
      var pct = this.taskPct(m);
      if (pct >= 75) return "members-panel__tasks-fill--high";
      if (pct >= 40) return "members-panel__tasks-fill--mid";
      return "members-panel__tasks-fill--low";
    },
    isOwner: function (member) {
      return !!this.ownerUid && member && member.userId === this.ownerUid;
    },
    openAddMode: function () {
      this.addMode = true;
      this.addSearchTerm = "";
      this.addSearchResults = [];
      this.addError = null;
      this.addingUid = null;
      this.$nextTick(() => {
        const el = this.$el.querySelector(".members-panel__add-form-input");
        if (el && el.focus) el.focus();
      });
    },
    closeAddMode: function () {
      this.addMode = false;
      if (this._addSearchDebounce) clearTimeout(this._addSearchDebounce);
      this.addSearchTerm = "";
      this.addSearchResults = [];
      this.addSearchLoading = false;
      this.addError = null;
      this.addingUid = null;
      this.addTab = "existing";
      this.createdUser = null;
      this.revealCreatedPassword = false;
      this.resetNewUserForm();
    },
    onAddSearchInput: function (ev) {
      this.addSearchTerm = ev.target.value;
      if (this._addSearchDebounce) clearTimeout(this._addSearchDebounce);
      if (this.addSearchTerm.trim().length < 2) {
        this.addSearchResults = [];
        this.addSearchLoading = false;
        return;
      }
      this._addSearchDebounce = setTimeout(() => this.runAddSearch(), 300);
    },
    runAddSearch: async function () {
      const term = this.addSearchTerm.trim();
      if (term.length < 2) return;
      const token = ++this._addSearchToken;
      this.addSearchLoading = true;
      this.addError = null;
      try {
        const res = await axios.get(
          generateOcsUrl(
            "/apps/organization/organizations/" + this.orgId + "/available-users"
          ),
          {
            params: { search: term, format: "json" },
            headers: OCS_HEADERS,
          }
        );
        if (token !== this._addSearchToken) return;
        const data =
          (res.data && res.data.ocs && res.data.ocs.data) || res.data || {};
        this.addSearchResults = data.users || [];
      } catch (e) {
        if (token !== this._addSearchToken) return;
        const code = (e && e.response && e.response.status) || "network";
        this.addError = "Search failed (" + code + ")";
        this.addSearchResults = [];
      } finally {
        if (token === this._addSearchToken) {
          this.addSearchLoading = false;
        }
      }
    },
    addMember: async function (uid) {
      if (this.addingUid !== null) return;
      this.addingUid = uid;
      this.addError = null;
      try {
        await axios.post(
          generateOcsUrl(
            "/apps/organization/organizations/" + this.orgId + "/members"
          ),
          null,
          {
            params: { userId: uid, format: "json" },
            headers: OCS_HEADERS,
          }
        );
        var msg = "Added " + uid;
        this.closeAddMode();
        this.showToast(msg);
        this.$emit("reload");
      } catch (e) {
        const code = (e && e.response && e.response.status) || "network";
        this.addError = "Failed to add (" + code + ")";
      } finally {
        this.addingUid = null;
      }
    },
    generatePassword: function () {
      var alphabet =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*-_=+";
      var len = 20;
      var out = "";
      var arr = new Uint32Array(len);
      window.crypto.getRandomValues(arr);
      for (var i = 0; i < len; i++) {
        out += alphabet.charAt(arr[i] % alphabet.length);
      }
      return out;
    },
    onAutoGenerateChange: function () {
      if (this.newUser.autoGenerate) {
        this.newUser.password = this.generatePassword();
      }
    },
    markBlurred: function (field) {
      this.$set(this.newUserBlurred, field, true);
      this.validateNewUser();
    },
    validateNewUser: function () {
      var errs = {};
      var nu = this.newUser;
      var uid = nu.uid.trim();
      if (!uid) {
        errs.uid = "Required";
      } else if (!/^[A-Za-z0-9._@-]+$/.test(uid)) {
        errs.uid = "Use letters, digits, or . _ @ - (no spaces).";
      }
      if (!nu.displayName.trim()) {
        errs.displayName = "Required";
      }
      var email = nu.email.trim();
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errs.email = "Invalid email address.";
      }
      if (!nu.autoGenerate) {
        if (!nu.password) {
          errs.password = "Required";
        } else if (nu.password.length < 10) {
          errs.password = "At least 10 characters.";
        }
      }
      this.newUserFieldErrors = errs;
      return Object.keys(errs).length === 0;
    },
    submitNewUser: async function () {
      this.newUserBlurred = {
        uid: true,
        displayName: true,
        email: true,
        password: true,
      };
      if (!this.validateNewUser()) return;
      if (this.newUserSubmitting) return;

      var nu = this.newUser;
      var params = {
        userId: nu.uid.trim(),
        password: nu.password,
        format: "json",
      };
      if (nu.displayName.trim()) params.displayName = nu.displayName.trim();
      if (nu.email.trim()) params.email = nu.email.trim();

      this.newUserSubmitting = true;
      this.newUserError = null;
      try {
        await axios.post(
          generateOcsUrl(
            "/apps/organization/organizations/" + this.orgId + "/users"
          ),
          null,
          { params: params, headers: OCS_HEADERS }
        );
        if (nu.autoGenerate) {
          this.createdUser = { uid: params.userId, password: nu.password };
          this.revealCreatedPassword = false;
          this.$emit("reload");
        } else {
          var msg = "User " + params.userId + " created and added";
          this.closeAddMode();
          this.showToast(msg);
          this.$emit("reload");
        }
      } catch (e) {
        this.newUserError = this.extractServerError(
          e,
          "Couldn't create user. Please try again."
        );
      } finally {
        this.newUserSubmitting = false;
      }
    },
    extractServerError: function (err, fallback) {
      if (!err) return fallback;
      if (!err.response) return "Couldn't reach the server. Try again.";
      var ocsMsg =
        err.response.data &&
        err.response.data.ocs &&
        err.response.data.ocs.meta &&
        err.response.data.ocs.meta.message;
      if (ocsMsg) return ocsMsg;
      return fallback + " (HTTP " + err.response.status + ")";
    },
    confirmCreatedUser: function () {
      var uid = this.createdUser ? this.createdUser.uid : null;
      this.closeAddMode();
      if (uid) this.showToast("User " + uid + " created and added");
    },
    resetNewUserForm: function () {
      this.newUser = {
        uid: "",
        displayName: "",
        email: "",
        password: "",
        autoGenerate: true,
      };
      this.newUserFieldErrors = {};
      this.newUserBlurred = {};
      this.newUserError = null;
      this.newUserShowPassword = false;
      this.copyFlags = {};
    },
    copyToClipboard: function (text, key) {
      var setFlag = (val) => {
        this.$set(this.copyFlags, key, val);
      };
      var schedule = () => {
        if (this._copyTimers[key]) clearTimeout(this._copyTimers[key]);
        this._copyTimers[key] = setTimeout(() => {
          setFlag(false);
        }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(text)
          .then(() => {
            setFlag(true);
            schedule();
          })
          .catch(() => {
            setFlag(false);
          });
      } else {
        setFlag(false);
      }
    },
    showToast: function (message) {
      if (this._toastTimer) clearTimeout(this._toastTimer);
      this.toast = { message: message };
      this._toastTimer = setTimeout(() => {
        this.toast = null;
      }, 3000);
    },
  },
};
</script>

<style scoped>
.members-panel {
  background: var(--bg-card, #fff);
  border-radius: var(--radius-card, 12px);
  box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.08));
  margin-bottom: var(--spacing-xl, 32px);
  overflow: hidden;
}

.members-panel--embedded {
  background: none;
  border-radius: 0;
  box-shadow: none;
  margin-bottom: 0;
  overflow: visible;
}

.members-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md, 16px) var(--spacing-lg, 24px);
  cursor: pointer;
  user-select: none;
  transition: background 0.15s;
}

.members-panel__header:hover {
  background: #fafbfd;
}

.members-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
  margin: 0;
  padding: 0;
  border: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.members-panel__title svg {
  color: #4a90d9;
}

.members-panel__count {
  font-size: 11px;
  font-weight: 600;
  background: #e8f0fe;
  color: #1e4a8a;
  padding: 2px 8px;
  border-radius: 8px;
}

.members-panel__chevron {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.3s;
}

.members-panel__chevron--rotated {
  transform: rotate(180deg);
}

.members-panel__body {
  padding: 0 var(--spacing-lg, 24px) var(--spacing-lg, 24px);
}

/* ── Filters ── */
.members-panel__filters {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.members-panel__search {
  flex: 1;
  min-width: 120px;
  padding: 7px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 12px;
  color: var(--color-text-primary, #1a1a2e);
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
}

.members-panel__search:focus {
  border-color: #4a90d9;
}

.members-panel__search::placeholder {
  color: #b0b5be;
}

.members-panel__role-badges {
  display: flex;
  gap: 5px;
}

.members-panel__role-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 12px;
  cursor: pointer;
  background: #f0f1f5;
  color: #6b7280;
  transition: all 0.15s ease;
  user-select: none;
  border: 1.5px solid transparent;
}

.members-panel__role-badge:hover {
  background: #e5e7eb;
}

.members-panel__role-badge--active {
  font-weight: 600;
  border-color: currentColor;
}

.members-panel__role-badge--admin-color {
  color: #1e4a8a;
}
.members-panel__role-badge--admin-color.members-panel__role-badge--active {
  background: #e8f0fe;
}

.members-panel__role-badge--member-color {
  color: #475569;
}
.members-panel__role-badge--member-color.members-panel__role-badge--active {
  background: #e2e8f0;
}

/* ── Pagination ── */
.members-panel__pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 12px;
}

.members-panel__page-btn {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff;
  font-size: 16px;
  font-weight: 600;
  color: #4a90d9;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.members-panel__page-btn:hover:not(:disabled) {
  background: #e8f0fe;
  border-color: #4a90d9;
}

.members-panel__page-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.members-panel__page-info {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
}

.members-panel__showing-hint {
  text-align: center;
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  margin-top: 6px;
}

.members-panel__empty {
  font-size: 13px;
  color: var(--color-text-muted, #9ca3af);
  padding: 16px 0;
  text-align: center;
}

/* ─── Member Card ─── */
.members-panel__card {
  border: 1px solid #f3f4f6;
  border-radius: 10px;
  margin-bottom: 8px;
  overflow: hidden;
  transition: border-color 0.15s;
}

.members-panel__card:last-child {
  margin-bottom: 0;
}

.members-panel__card:hover {
  border-color: #e0e3e9;
}

/* ─── Summary Row ─── */
.members-panel__row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  cursor: pointer;
  transition: background 0.15s;
}

.members-panel__row:hover {
  background: #fafbfd;
}

.members-panel__avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4a90d9, #6cb0f0);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.members-panel__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.members-panel__name {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.members-panel__sub {
  font-size: 11px;
  color: var(--color-text-muted, #9ca3af);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.members-panel__right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.members-panel__stat-pill {
  font-size: 11px;
  font-weight: 600;
  background: #e8f0fe;
  color: #1e4a8a;
  padding: 2px 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 3px;
}

.members-panel__stat-pill--danger {
  background: #fde8e8;
  color: #b91c1c;
}

.members-panel__stat-label {
  font-weight: 400;
  opacity: 0.8;
}

.members-panel__role {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 8px;
  text-transform: capitalize;
  flex-shrink: 0;
}

.members-panel__role--admin {
  background: #e8f0fe;
  color: #1e4a8a;
}
.members-panel__role--owner {
  background: #f3e8ff;
  color: #6b21a8;
}
.members-panel__role--member {
  background: #f0f1f5;
  color: #6b7280;
}

.members-panel__expand-icon {
  color: var(--color-text-muted, #9ca3af);
  transition: transform 0.2s;
  flex-shrink: 0;
}

.members-panel__expand-icon--open {
  transform: rotate(180deg);
}

/* ─── Expanded Detail ─── */
.members-panel__detail {
  padding: 0 14px 14px;
  background: #fafbfd;
  border-top: 1px solid #f3f4f6;
}

.members-panel__detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 var(--spacing-lg, 24px);
  padding-top: 12px;
}

.members-panel__detail-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid #eef1f5;
}

.members-panel__detail-item:last-child {
  border-bottom: none;
}

.members-panel__detail-label {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  font-weight: 500;
}

.members-panel__detail-value {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-primary, #1a1a2e);
  text-align: right;
  word-break: break-word;
}

/* ─── Task Performance ─── */
.members-panel__tasks {
  margin-top: 12px;
  padding: 12px;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #eef1f5;
}

.members-panel__tasks-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.members-panel__tasks-title {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.members-panel__tasks-pct {
  font-size: 12px;
  font-weight: 700;
  color: var(--color-text-primary, #1a1a2e);
}

.members-panel__tasks-bar {
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 8px;
}

.members-panel__tasks-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.members-panel__tasks-fill--high {
  background: #2e9e5a;
}
.members-panel__tasks-fill--mid {
  background: #f4a261;
}
.members-panel__tasks-fill--low {
  background: #e63946;
}

.members-panel__tasks-legend {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.members-panel__tasks-stat {
  font-size: 11px;
  color: var(--color-text-secondary, #6b7280);
  display: flex;
  align-items: center;
  gap: 4px;
}

.members-panel__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.members-panel__dot--done {
  background: #2e9e5a;
}
.members-panel__dot--open {
  background: #4a90d9;
}
.members-panel__dot--overdue {
  background: #e63946;
}

.members-panel__tasks-empty {
  margin-top: 12px;
  font-size: 12px;
  color: var(--color-text-muted, #9ca3af);
  font-style: italic;
  padding: 8px 0;
}

@media (max-width: 700px) {
  .members-panel__detail-grid {
    grid-template-columns: 1fr;
  }
  .members-panel__right {
    flex-wrap: wrap;
  }
}
</style>
