<template>
  <div class="cp-modal__overlay" @click.self="onOverlayClick">
    <div class="cp-modal" role="dialog" aria-modal="true">
      <header class="cp-modal__header">
        <h3 class="cp-modal__title">
          New project
          <span class="cp-modal__step">Step {{ step }} of 2</span>
        </h3>
        <button
          type="button"
          class="cp-modal__close"
          :disabled="submitting"
          aria-label="Close"
          @click="$emit('cancel')"
        >×</button>
      </header>

      <!-- Step 1: Basics + Location -->
      <section v-if="step === 1" class="cp-modal__body">
        <div class="cp-modal__field">
          <label class="cp-modal__label" for="cp-name">
            Name <span class="cp-modal__required">*</span>
          </label>
          <input
            id="cp-name"
            type="text"
            class="cp-modal__input"
            v-model="form.name"
            :disabled="submitting"
            @blur="markBlurred('name')"
          />
          <div
            v-if="blurred.name && fieldErrors.name"
            class="cp-modal__field-error"
          >{{ fieldErrors.name }}</div>
        </div>

        <div class="cp-modal__field">
          <label class="cp-modal__label" for="cp-number">
            Number <span class="cp-modal__required">*</span>
          </label>
          <input
            id="cp-number"
            type="text"
            class="cp-modal__input"
            v-model="form.number"
            placeholder="e.g. P-2026-014"
            :disabled="submitting"
            @blur="markBlurred('number')"
          />
          <div
            v-if="blurred.number && fieldErrors.number"
            class="cp-modal__field-error"
          >{{ fieldErrors.number }}</div>
        </div>

        <div class="cp-modal__field">
          <label class="cp-modal__label" for="cp-type">
            Type <span class="cp-modal__required">*</span>
          </label>
          <select
            id="cp-type"
            class="cp-modal__input"
            v-model.number="form.type"
            :disabled="submitting"
          >
            <option
              v-for="t in projectTypes"
              :key="'pt-' + t.id"
              :value="t.id"
            >{{ t.label }}</option>
          </select>
        </div>

        <div class="cp-modal__field">
          <label class="cp-modal__label" for="cp-desc">Description</label>
          <textarea
            id="cp-desc"
            class="cp-modal__input cp-modal__textarea"
            rows="2"
            v-model="form.description"
            :disabled="submitting"
          ></textarea>
        </div>

        <div class="cp-modal__group">
          <span class="cp-modal__group-title">Project Location</span>
          <div class="cp-modal__row">
            <div class="cp-modal__field cp-modal__field--grow">
              <label class="cp-modal__label" for="cp-street">Street</label>
              <input
                id="cp-street"
                type="text"
                class="cp-modal__input"
                v-model="form.loc_street"
                :disabled="submitting"
              />
            </div>
          </div>
          <div class="cp-modal__row">
            <div class="cp-modal__field cp-modal__field--grow">
              <label class="cp-modal__label" for="cp-city">City</label>
              <input
                id="cp-city"
                type="text"
                class="cp-modal__input"
                v-model="form.loc_city"
                :disabled="submitting"
              />
            </div>
            <div class="cp-modal__field">
              <label class="cp-modal__label" for="cp-zip">ZIP</label>
              <input
                id="cp-zip"
                type="text"
                class="cp-modal__input cp-modal__input--zip"
                v-model="form.loc_zip"
                :disabled="submitting"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Step 2: Client + Members + Timing -->
      <section v-else class="cp-modal__body">
        <div class="cp-modal__group">
          <span class="cp-modal__group-title">Client</span>
          <div class="cp-modal__field">
            <label class="cp-modal__label" for="cp-cname">Name</label>
            <input
              id="cp-cname"
              type="text"
              class="cp-modal__input"
              v-model="form.client_name"
              :disabled="submitting"
            />
          </div>
          <div class="cp-modal__field">
            <label class="cp-modal__label" for="cp-crole">Role</label>
            <input
              id="cp-crole"
              type="text"
              class="cp-modal__input"
              v-model="form.client_role"
              :disabled="submitting"
            />
          </div>
          <div class="cp-modal__field">
            <label class="cp-modal__label" for="cp-cemail">Email</label>
            <input
              id="cp-cemail"
              type="email"
              class="cp-modal__input"
              v-model="form.client_email"
              :disabled="submitting"
              @blur="markBlurred('client_email')"
            />
            <div
              v-if="blurred.client_email && fieldErrors.client_email"
              class="cp-modal__field-error"
            >{{ fieldErrors.client_email }}</div>
          </div>
          <div class="cp-modal__field">
            <label class="cp-modal__label" for="cp-cphone">Phone</label>
            <input
              id="cp-cphone"
              type="text"
              class="cp-modal__input"
              v-model="form.client_phone"
              :disabled="submitting"
            />
          </div>
          <div class="cp-modal__field">
            <label class="cp-modal__label" for="cp-caddr">Address</label>
            <textarea
              id="cp-caddr"
              class="cp-modal__input cp-modal__textarea"
              rows="2"
              v-model="form.client_address"
              :disabled="submitting"
            ></textarea>
          </div>
        </div>

        <div class="cp-modal__group">
          <span class="cp-modal__group-title">Members</span>
          <div v-if="form.members.length" class="cp-modal__chips">
            <span
              v-for="uid in form.members"
              :key="'chip-' + uid"
              class="cp-modal__chip"
            >
              {{ displayNameFor(uid) }}
              <button
                type="button"
                class="cp-modal__chip-x"
                :disabled="submitting"
                :aria-label="'Remove ' + displayNameFor(uid)"
                @click="removeMember(uid)"
              >×</button>
            </span>
          </div>
          <input
            type="search"
            class="cp-modal__input"
            v-model="memberSearch"
            placeholder="Search org members…"
            :disabled="submitting"
          />
          <ul
            v-if="availableMembers.length"
            class="cp-modal__member-results"
          >
            <li
              v-for="m in availableMembers"
              :key="'avail-' + m.userId"
              class="cp-modal__member-row"
            >
              <div class="cp-modal__member-info">
                <span class="cp-modal__member-name">{{ m.displayName || m.userId }}</span>
                <span class="cp-modal__member-meta">
                  <template v-if="m.email">{{ m.email }} · </template>uid: {{ m.userId }}
                </span>
              </div>
              <button
                type="button"
                class="cp-modal__member-add"
                :disabled="submitting"
                :aria-label="'Add ' + (m.displayName || m.userId)"
                @click="addMember(m.userId)"
              >+</button>
            </li>
          </ul>
          <div
            v-else-if="memberSearch.trim().length >= 2"
            class="cp-modal__member-empty"
          >No matches.</div>
          <div
            v-else-if="form.members.length === 0 && !orgMembers.length"
            class="cp-modal__member-empty"
          >No org members available.</div>
        </div>

        <div class="cp-modal__group">
          <span class="cp-modal__group-title">Timing</span>
          <div class="cp-modal__row">
            <div class="cp-modal__field cp-modal__field--grow">
              <label class="cp-modal__label" for="cp-weeks">Required prep weeks</label>
              <input
                id="cp-weeks"
                type="number"
                min="0"
                class="cp-modal__input"
                v-model.number="form.required_preparation_weeks"
                :disabled="submitting"
                @blur="markBlurred('required_preparation_weeks')"
              />
              <div
                v-if="blurred.required_preparation_weeks && fieldErrors.required_preparation_weeks"
                class="cp-modal__field-error"
              >{{ fieldErrors.required_preparation_weeks }}</div>
            </div>
            <div class="cp-modal__field cp-modal__field--grow">
              <label class="cp-modal__label" for="cp-execdate">Desired execution date</label>
              <input
                id="cp-execdate"
                type="date"
                class="cp-modal__input"
                v-model="form.desired_execution_date"
                :disabled="submitting"
              />
            </div>
          </div>
        </div>
      </section>

      <footer class="cp-modal__footer">
        <div v-if="error" class="cp-modal__error">{{ error }}</div>
        <div v-if="slowHint" class="cp-modal__hint">This may take a few seconds…</div>
        <div class="cp-modal__actions">
          <button
            type="button"
            class="cp-modal__btn cp-modal__btn--ghost"
            :disabled="submitting"
            @click="$emit('cancel')"
          >Cancel</button>
          <button
            v-if="step === 2"
            type="button"
            class="cp-modal__btn cp-modal__btn--ghost"
            :disabled="submitting"
            @click="step = 1"
          >← Back</button>
          <button
            v-if="step === 1"
            type="button"
            class="cp-modal__btn cp-modal__btn--primary"
            :disabled="!step1Valid"
            @click="goToStep2"
          >Next →</button>
          <button
            v-if="step === 2"
            type="button"
            class="cp-modal__btn cp-modal__btn--ghost"
            :disabled="submitting"
            @click="submit"
          >Skip &amp; Create</button>
          <button
            v-if="step === 2"
            type="button"
            class="cp-modal__btn cp-modal__btn--primary"
            :disabled="submitting || !formValid"
            @click="submit"
          >
            <span
              v-if="submitting"
              class="cp-modal__spinner"
              aria-hidden="true"
            ></span>
            Create project
          </button>
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

// Hard-coded; mirrors projectcreatoraio/src/macros/project-types.js
// Update here if that catalog changes — we don't import across apps.
const PROJECT_TYPES = [
  { id: 0, label: "Combi" },
  { id: 1, label: "Solo Elektra" },
  { id: 2, label: "Solo Water" },
  { id: 3, label: "Custom" },
];

const PCAIO_HEADERS = {
  "OCS-APIRequest": "true",
  Accept: "application/json",
  "Content-Type": "application/json",
};

export default {
  name: "CreateProjectModal",
  props: {
    orgId: {
      type: Number,
      required: true,
    },
    orgMembers: {
      type: Array,
      default: function () {
        return [];
      },
    },
    currentUid: {
      type: String,
      default: null,
    },
  },
  data: function () {
    return {
      step: 1,
      form: {
        name: "",
        number: "",
        type: 0,
        description: "",
        loc_street: "",
        loc_city: "",
        loc_zip: "",
        client_name: "",
        client_role: "",
        client_phone: "",
        client_email: "",
        client_address: "",
        members: [],
        required_preparation_weeks: null,
        desired_execution_date: "",
      },
      memberSearch: "",
      blurred: {},
      submitting: false,
      slowHint: false,
      error: null,
    };
  },
  created: function () {
    this._slowHintTimer = null;
    this._escHandler = (e) => {
      if (e.key === "Escape" && !this.submitting) {
        this.$emit("cancel");
      }
    };
    document.addEventListener("keydown", this._escHandler);
  },
  beforeDestroy: function () {
    if (this._slowHintTimer) clearTimeout(this._slowHintTimer);
    if (this._escHandler) document.removeEventListener("keydown", this._escHandler);
  },
  computed: {
    projectTypes: function () {
      return PROJECT_TYPES;
    },
    fieldErrors: function () {
      var errs = {};
      var f = this.form;
      var name = (f.name || "").trim();
      if (!name) {
        errs.name = "Required";
      } else if (name.length > 255) {
        errs.name = "Max 255 characters";
      }
      var number = (f.number || "").trim();
      if (!number) {
        errs.number = "Required";
      } else if (number.length > 64) {
        errs.number = "Max 64 characters";
      }
      var email = (f.client_email || "").trim();
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errs.client_email = "Invalid email address";
      }
      var weeks = f.required_preparation_weeks;
      if (weeks !== null && weeks !== "" && (typeof weeks !== "number" || isNaN(weeks) || weeks < 0)) {
        errs.required_preparation_weeks = "Must be 0 or more";
      }
      return errs;
    },
    step1Valid: function () {
      var e = this.fieldErrors;
      return !e.name && !e.number;
    },
    formValid: function () {
      return Object.keys(this.fieldErrors).length === 0;
    },
    selectedSet: function () {
      var set = {};
      for (var i = 0; i < this.form.members.length; i++) {
        set[this.form.members[i]] = true;
      }
      return set;
    },
    availableMembers: function () {
      var q = (this.memberSearch || "").trim().toLowerCase();
      var sel = this.selectedSet;
      var me = this.currentUid;
      var out = [];
      var src = this.orgMembers || [];
      for (var i = 0; i < src.length; i++) {
        var m = src[i];
        var uid = m.userId || m.id;
        if (!uid || sel[uid]) continue;
        // The admin creating the project is the owner — exclude from the picker.
        if (me && uid === me) continue;
        if (q.length >= 2) {
          var nm = (m.displayName || "").toLowerCase();
          var u = String(uid).toLowerCase();
          var em = (m.email || "").toLowerCase();
          if (nm.indexOf(q) === -1 && u.indexOf(q) === -1 && em.indexOf(q) === -1) continue;
        } else if (q.length > 0) {
          return [];
        }
        out.push({
          userId: uid,
          displayName: m.displayName || uid,
          email: m.email || "",
        });
        if (out.length >= 10) break;
      }
      return out;
    },
  },
  methods: {
    onOverlayClick: function () {
      if (!this.submitting) this.$emit("cancel");
    },
    markBlurred: function (field) {
      this.$set(this.blurred, field, true);
    },
    goToStep2: function () {
      this.$set(this.blurred, "name", true);
      this.$set(this.blurred, "number", true);
      if (this.step1Valid) this.step = 2;
    },
    addMember: function (uid) {
      if (!uid) return;
      if (this.form.members.indexOf(uid) !== -1) return;
      this.form.members.push(uid);
      this.memberSearch = "";
    },
    removeMember: function (uid) {
      var i = this.form.members.indexOf(uid);
      if (i !== -1) this.form.members.splice(i, 1);
    },
    displayNameFor: function (uid) {
      for (var i = 0; i < this.orgMembers.length; i++) {
        var m = this.orgMembers[i];
        if ((m.userId || m.id) === uid) return m.displayName || uid;
      }
      return uid;
    },
    buildPayload: function () {
      var f = this.form;
      var me = this.currentUid;
      // The creator is the project owner — never send them as a member.
      var members = me ? f.members.filter(function (u) { return u !== me; }) : f.members.slice();
      var payload = {
        name: f.name.trim(),
        number: f.number.trim(),
        type: Number(f.type),
        description: f.description || "",
        organizationId: this.orgId,
        members: members,
        loc_street: f.loc_street || "",
        loc_city: f.loc_city || "",
        loc_zip: f.loc_zip || "",
        client_name: (f.client_name || "").trim() || null,
        client_role: (f.client_role || "").trim() || null,
        client_phone: (f.client_phone || "").trim() || null,
        client_email: (f.client_email || "").trim() || null,
        client_address: (f.client_address || "").trim() || null,
        required_preparation_weeks:
          typeof f.required_preparation_weeks === "number" &&
          !isNaN(f.required_preparation_weeks)
            ? f.required_preparation_weeks
            : null,
        desired_execution_date: f.desired_execution_date || null,
      };
      return payload;
    },
    submit: async function () {
      this.$set(this.blurred, "name", true);
      this.$set(this.blurred, "number", true);
      if (!this.formValid) {
        if (this.fieldErrors.name || this.fieldErrors.number) this.step = 1;
        return;
      }
      if (this.submitting) return;
      this.submitting = true;
      this.error = null;
      this.slowHint = false;
      this._slowHintTimer = setTimeout(() => {
        this.slowHint = true;
      }, 3000);
      try {
        const res = await axios.post(
          generateUrl("/apps/projectcreatoraio/api/v1/projects"),
          this.buildPayload(),
          { headers: PCAIO_HEADERS }
        );
        const data = (res && res.data) || {};
        if (data.projectId == null) {
          this.error = "Server didn't return a project id.";
          return;
        }
        this.$emit("created", Number(data.projectId));
      } catch (e) {
        this.error = this.extractError(
          e,
          "Couldn't create the project. Please try again."
        );
      } finally {
        if (this._slowHintTimer) clearTimeout(this._slowHintTimer);
        this.submitting = false;
        this.slowHint = false;
      }
    },
    extractError: function (err, fallback) {
      if (!err) return fallback;
      if (!err.response) return "Couldn't reach the server. Try again.";
      var msg =
        err.response.data &&
        (err.response.data.message ||
          (err.response.data.ocs &&
            err.response.data.ocs.meta &&
            err.response.data.ocs.meta.message));
      if (msg) return String(msg);
      return fallback + " (HTTP " + err.response.status + ")";
    },
  },
};
</script>

<style scoped>
.cp-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 24px;
}
.cp-modal {
  background: #fff;
  width: min(560px, 100%);
  max-height: calc(100vh - 48px);
  border-radius: 14px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.cp-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}
.cp-modal__title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #1a1a2e;
  display: flex;
  align-items: center;
  gap: 8px;
}
.cp-modal__step {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  background: #f0f1f5;
  padding: 2px 8px;
  border-radius: 999px;
}
.cp-modal__close {
  background: none;
  border: none;
  font-size: 22px;
  color: #6b7280;
  cursor: pointer;
  line-height: 1;
}
.cp-modal__close:hover { color: #1a1a2e; }
.cp-modal__close:disabled { opacity: 0.5; cursor: not-allowed; }

.cp-modal__body {
  padding: 16px 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cp-modal__group {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.cp-modal__group-title {
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.cp-modal__row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.cp-modal__field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.cp-modal__field--grow { flex: 1; min-width: 140px; }

.cp-modal__label {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.cp-modal__required { color: #b91c1c; }

.cp-modal__input,
.cp-modal__textarea {
  font-size: 13px;
  color: #1a1a2e;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 10px;
  outline: none;
  box-sizing: border-box;
  width: 100%;
}
.cp-modal__input:focus,
.cp-modal__textarea:focus { border-color: #4a90d9; }
.cp-modal__input--zip { width: 110px; min-width: 110px; }
.cp-modal__textarea { resize: vertical; min-height: 56px; }

.cp-modal__field-error {
  font-size: 11px;
  color: #b91c1c;
}

.cp-modal__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.cp-modal__chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #e8f0fe;
  color: #1e4a8a;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 6px 3px 10px;
  border-radius: 999px;
}
.cp-modal__chip-x {
  background: none;
  border: none;
  color: #1e4a8a;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}
.cp-modal__chip-x:disabled { opacity: 0.5; cursor: not-allowed; }

.cp-modal__member-results {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 200px;
  overflow-y: auto;
}
.cp-modal__member-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 4px;
  border-bottom: 1px solid #f3f4f6;
}
.cp-modal__member-row:last-child { border-bottom: none; }
.cp-modal__member-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.cp-modal__member-name {
  font-size: 13px;
  font-weight: 600;
  color: #1a1a2e;
}
.cp-modal__member-meta {
  font-size: 11px;
  color: #9ca3af;
}
.cp-modal__member-add {
  flex: 0 0 auto;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: none;
  background: #4a90d9;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}
.cp-modal__member-add:hover:not(:disabled) { background: #357ec7; }
.cp-modal__member-add:disabled { opacity: 0.5; cursor: not-allowed; }
.cp-modal__member-empty {
  font-size: 12px;
  color: #6b7280;
  padding: 6px 0;
}

.cp-modal__footer {
  border-top: 1px solid #e5e7eb;
  padding: 12px 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.cp-modal__error {
  font-size: 12px;
  color: #b91c1c;
}
.cp-modal__hint {
  font-size: 11px;
  color: #92400e;
  font-style: italic;
}
.cp-modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}
.cp-modal__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid transparent;
  cursor: pointer;
}
.cp-modal__btn:disabled { opacity: 0.5; cursor: not-allowed; }
.cp-modal__btn--primary { background: #4a90d9; color: #fff; }
.cp-modal__btn--primary:hover:not(:disabled) { background: #357ec7; }
.cp-modal__btn--ghost { background: #fff; color: #6b7280; border-color: #e5e7eb; }
.cp-modal__btn--ghost:hover:not(:disabled) { background: #f0f1f5; }

.cp-modal__spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid #fff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: cp-modal-spin 0.7s linear infinite;
}
@keyframes cp-modal-spin {
  to { transform: rotate(360deg); }
}
</style>
