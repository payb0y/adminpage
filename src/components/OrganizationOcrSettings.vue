<template>
  <div class="ocr-settings">
    <p class="ocr-settings__intro">
      Define the fields OCR should extract from each kind of document.
    </p>

    <p v-if="error && !editorOpen" class="ocr-settings__error" role="alert">{{ error }}</p>

    <div v-if="loading" class="iz-empty ocr-settings__loading">
      Loading OCR document types…
    </div>

    <template v-else>
      <div class="ocr-settings__toolbar">
        <span class="ocr-settings__count">
          {{ documentTypes.length }}
          {{ documentTypes.length === 1 ? "type" : "types" }} configured
        </span>
        <button
          type="button"
          class="iz-btn iz-btn--primary iz-btn--sm"
          :disabled="actionBusy"
          @click="startCreate"
        >
          <span aria-hidden="true">+</span>
          New document type
        </button>
      </div>

      <!-- A new type edits at the top: it has no row in the list to sit under. -->
      <OcrTypeEditor
        v-if="editorOpen && !form.id"
        class="ocr-settings__editor ocr-settings__editor--new"
        :form="form"
        :busy="actionBusy"
        :save-label="saveLabel"
        :error="error"
        @add-field="addField"
        @remove-field="removeField"
        @save="saveType"
        @reset="closeEditor"
        @request-delete="showDeleteConfirmation = true"
      />

      <p
        v-if="documentTypes.length === 0 && !editorOpen"
        class="iz-empty ocr-settings__empty"
      >
        No document types defined yet.
      </p>

      <div v-else-if="documentTypes.length" class="ocr-settings__type-list">
        <template v-for="documentType in documentTypes">
          <button
            :key="'type-' + documentType.id"
            type="button"
            class="ocr-settings__type"
            :class="{ 'ocr-settings__type--open': isOpen(documentType) }"
            :aria-expanded="isOpen(documentType) ? 'true' : 'false'"
            :disabled="actionBusy"
            @click="toggleType(documentType)"
          >
            <span class="ocr-settings__type-copy">
              <strong>{{ documentType.name }}</strong>
              <small>{{ fieldCount(documentType) }} fields</small>
            </span>
            <span
              class="ocr-settings__status"
              :class="{ 'ocr-settings__status--active': documentType.is_active }"
              :title="documentType.is_active ? 'Active' : 'Inactive'"
            ></span>
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
              class="ocr-settings__chevron"
              :class="{ 'ocr-settings__chevron--rotated': isOpen(documentType) }"
              aria-hidden="true"
            >
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </button>
          <OcrTypeEditor
            v-if="isOpen(documentType)"
            :key="'editor-' + documentType.id"
            class="ocr-settings__editor"
            :form="form"
            :busy="actionBusy"
            :save-label="saveLabel"
            :error="error"
            @add-field="addField"
            @remove-field="removeField"
            @save="saveType"
            @reset="resetForm"
            @request-delete="showDeleteConfirmation = true"
          />
        </template>
      </div>
    </template>

    <ConfirmDialog
      v-if="showDeleteConfirmation"
      title="Delete OCR document type?"
      :message="`The document type '${form.name}' will be permanently deleted.`"
      confirm-label="Delete type"
      busy-label="Deleting..."
      :danger="true"
      :busy="deleting"
      :error="deleteError"
      @confirm="deleteType"
      @cancel="closeDeleteConfirmation"
    />
  </div>
</template>

<script>
import ConfirmDialog from "./ConfirmDialog.vue";
import OcrTypeEditor from "./OcrTypeEditor.vue";
import {
  createOrganizationDocumentType,
  deleteOrganizationDocumentType,
  listOrganizationDocumentTypes,
  updateOrganizationDocumentType,
} from "../services/projectCreatorApi";

function emptyField() {
  return { name: "" };
}

function emptyForm() {
  return {
    id: null,
    name: "",
    is_active: true,
    fields: [emptyField()],
  };
}

/**
 * OCR document types, as a list that expands one editor at a time.
 *
 * This used to be a two-pane sidebar + editor, which worked at modal height.
 * It now renders inline in Organization Insights, where a persistent split
 * pane would dominate the panel — hence the accordion, and hence the editor
 * living in OcrTypeEditor so both an existing type and a new one use it.
 */
export default {
  name: "OrganizationOcrSettings",
  components: { ConfirmDialog, OcrTypeEditor },
  props: {
    organizationId: { type: Number, required: true },
  },
  data: function () {
    return {
      loading: true,
      saving: false,
      deleting: false,
      error: "",
      deleteError: "",
      showDeleteConfirmation: false,
      documentTypes: [],
      selectedTypeId: null,
      editorOpen: false,
      form: emptyForm(),
    };
  },
  computed: {
    actionBusy: function () {
      return this.saving || this.deleting;
    },
    locked: function () {
      return this.actionBusy || this.showDeleteConfirmation;
    },
    saveLabel: function () {
      if (this.saving) return "Saving...";
      return this.form.id ? "Update type" : "Create type";
    },
  },
  watch: {
    locked: {
      immediate: true,
      handler: function (locked) {
        this.$emit("lock-change", locked);
      },
    },
  },
  mounted: function () {
    this.loadDocumentTypes();
  },
  beforeDestroy: function () {
    this.$emit("lock-change", false);
  },
  methods: {
    async loadDocumentTypes() {
      this.loading = true;
      this.error = "";
      try {
        this.documentTypes = await listOrganizationDocumentTypes(this.organizationId);
        if (this.selectedTypeId !== null) {
          const selected = this.findType(this.selectedTypeId);
          if (selected) {
            this.applyType(selected);
            return;
          }
        }
        // Nothing selected, or what was selected is gone: fall back to a
        // closed list rather than the modal's old always-open create form.
        this.closeEditor();
      } catch (error) {
        console.error("Failed to load organization OCR document types", error);
        this.error = this.errorMessage(error, "Could not load OCR document types.");
      } finally {
        this.loading = false;
      }
    },
    findType(id) {
      return this.documentTypes.find(function (documentType) {
        return Number(documentType.id) === Number(id);
      });
    },
    fieldCount(documentType) {
      return Array.isArray(documentType.fields) ? documentType.fields.length : 0;
    },
    isOpen(documentType) {
      return (
        this.editorOpen && Number(this.selectedTypeId) === Number(documentType.id)
      );
    },
    closeEditor() {
      this.selectedTypeId = null;
      this.editorOpen = false;
      this.form = emptyForm();
      this.error = "";
    },
    startCreate() {
      this.selectedTypeId = null;
      this.form = emptyForm();
      this.error = "";
      this.editorOpen = true;
    },
    toggleType(documentType) {
      if (this.isOpen(documentType)) {
        this.closeEditor();
        return;
      }
      this.selectType(documentType);
    },
    selectType(documentType) {
      this.selectedTypeId = Number(documentType.id);
      this.applyType(documentType);
      this.error = "";
      this.editorOpen = true;
    },
    applyType(documentType) {
      const fields = Array.isArray(documentType.fields) && documentType.fields.length
        ? documentType.fields.map(function (field) {
          return { name: field.name || field.label || field.key || "" };
        })
        : [emptyField()];
      this.form = {
        id: Number(documentType.id) || null,
        name: documentType.name || documentType.label || documentType.key || "",
        is_active: Boolean(documentType.is_active),
        fields,
      };
    },
    resetForm() {
      const selected = this.findType(this.selectedTypeId);
      if (selected) {
        this.applyType(selected);
        this.error = "";
        return;
      }
      this.closeEditor();
    },
    addField() {
      this.form.fields.push(emptyField());
    },
    removeField(index) {
      if (this.form.fields.length > 1) this.form.fields.splice(index, 1);
    },
    buildPayload() {
      return {
        name: String(this.form.name || "").trim(),
        is_active: this.form.is_active ? 1 : 0,
        fields: this.form.fields.map(function (field) {
          return { name: String(field.name || "").trim() };
        }),
      };
    },
    async saveType() {
      const payload = this.buildPayload();
      if (!payload.name) {
        this.error = "Enter a document type name.";
        return;
      }
      if (payload.fields.some(function (field) {
        return !field.name;
      })) {
        this.error = "Enter a name for every extraction field.";
        return;
      }

      this.saving = true;
      this.error = "";
      try {
        const saved = this.form.id
          ? await updateOrganizationDocumentType(this.organizationId, this.form.id, payload)
          : await createOrganizationDocumentType(this.organizationId, payload);
        this.selectedTypeId = saved && saved.id ? Number(saved.id) : this.form.id;
        await this.loadDocumentTypes();
      } catch (error) {
        console.error("Failed to save organization OCR document type", error);
        this.error = this.errorMessage(error, "Could not save the OCR document type.");
      } finally {
        this.saving = false;
      }
    },
    async deleteType() {
      if (!this.form.id) return;
      this.deleting = true;
      this.deleteError = "";
      try {
        await deleteOrganizationDocumentType(this.organizationId, this.form.id);
        this.showDeleteConfirmation = false;
        this.selectedTypeId = null;
        this.editorOpen = false;
        await this.loadDocumentTypes();
      } catch (error) {
        console.error("Failed to delete organization OCR document type", error);
        this.deleteError = this.errorMessage(error, "Could not delete the OCR document type.");
      } finally {
        this.deleting = false;
      }
    },
    closeDeleteConfirmation() {
      if (!this.deleting) {
        this.showDeleteConfirmation = false;
        this.deleteError = "";
      }
    },
    errorMessage(error, fallback) {
      const data = error && error.response && error.response.data;
      return (data && (data.error || data.message)) || fallback;
    },
  },
};
</script>

<style scoped>
.ocr-settings {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md, 12px);
}

.ocr-settings__intro {
  margin: 0;
  font-size: var(--iz-fs-sm);
  color: var(--color-text-muted);
}

.ocr-settings__error {
  margin: 0;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border-radius: var(--radius-sm, 6px);
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
  font-size: var(--iz-fs-sm);
}

.ocr-settings__loading,
.ocr-settings__empty {
  padding: var(--spacing-lg, 16px);
}

.ocr-settings__toolbar {
  display: flex;
  align-items: center;
  gap: var(--spacing-md, 12px);
}

.ocr-settings__count {
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.ocr-settings__toolbar .iz-btn {
  margin-left: auto;
}

.ocr-settings__type-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs, 6px);
}

/* A row and its editor read as one object: the open row loses its bottom
   radius so the editor below continues it. */
.ocr-settings__type {
  display: flex;
  align-items: center;
  gap: var(--spacing-md, 11px);
  width: 100%;
  padding: var(--spacing-sm, 10px) var(--spacing-md, 13px);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm, 7px);
  background: var(--bg-card);
  color: var(--color-text-primary);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.ocr-settings__type:hover:not(:disabled) {
  border-color: var(--accent);
}

.ocr-settings__type--open {
  border-color: var(--accent);
  background: var(--accent-bg);
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}

.ocr-settings__type-copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.ocr-settings__type-copy strong,
.ocr-settings__type-copy small {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ocr-settings__type-copy strong {
  font-size: var(--iz-fs-sm);
}

.ocr-settings__type-copy small {
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
}

.ocr-settings__status {
  margin-left: auto;
  width: 8px;
  height: 8px;
  flex: 0 0 auto;
  border-radius: var(--radius-pill, 999px);
  background: var(--color-text-muted);
}

.ocr-settings__status--active {
  background: var(--color-success);
}

.ocr-settings__chevron {
  flex: 0 0 auto;
  color: var(--color-text-muted);
  transition: transform 0.15s;
}

.ocr-settings__chevron--rotated {
  transform: rotate(180deg);
}

/* The editor is a sibling of its row, so pull it flush against it. */
.ocr-settings__editor {
  margin-top: calc(-1 * var(--spacing-xs, 6px));
}

.ocr-settings__editor--new {
  margin-top: 0;
  border-top: 1px solid var(--accent);
  border-radius: var(--radius-sm, 7px);
}
</style>
