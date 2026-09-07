<template>
  <div class="org-pdf">
    <p class="org-pdf__intro">
      Automatically added to the shared folder of every new project in your organization.
    </p>

    <p v-if="error" class="org-pdf__error" role="alert">{{ error }}</p>

    <div v-if="loading" class="iz-empty org-pdf__loading">Loading template settings…</div>

    <template v-else>
      <div class="org-pdf__status">
        <span class="org-pdf__document" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
          </svg>
        </span>
        <span class="org-pdf__status-copy">
          <span class="iz-label">Current template</span>
          <strong>{{ hasCustomPdf ? currentFileName || "Custom organization PDF" : "System default PDF" }}</strong>
          <small>{{ hasCustomPdf ? "Custom template active" : "Fallback used for new projects" }}</small>
        </span>
        <button
          v-if="hasCustomPdf"
          type="button"
          class="iz-btn iz-btn--danger iz-btn--sm org-pdf__reset"
          :disabled="busy"
          @click="showResetConfirmation = true"
        >Reset to default</button>
      </div>

      <div
        class="org-pdf__dropzone"
        :class="{ 'org-pdf__dropzone--selected': selectedFile }"
        role="button"
        tabindex="0"
        @click="chooseFile"
        @keydown.enter.prevent="chooseFile"
        @keydown.space.prevent="chooseFile"
        @dragover.prevent
        @drop.prevent="onDrop"
      >
        <input
          ref="fileInput"
          class="org-pdf__file-input"
          type="file"
          accept="application/pdf,.pdf"
          :disabled="busy"
          @change="onFileSelected"
        />
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
          <polyline points="17 8 12 3 7 8" />
          <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        <template v-if="selectedFile">
          <strong>{{ selectedFile.name }}</strong>
          <small>{{ formatFileSize(selectedFile.size) }}</small>
        </template>
        <template v-else>
          <strong>Choose or drop a PDF here</strong>
          <small>Only PDF documents are accepted</small>
        </template>
      </div>

      <!-- Only once a file is picked is there anything to name or save: an
           inline section has no modal footer to park a permanent Save in. -->
      <div v-if="selectedFile" class="org-pdf__pending">
        <label class="org-pdf__field">
          <span class="iz-label">Filename in new projects</span>
          <input
            v-model="fileName"
            class="iz-input"
            type="text"
            placeholder="e.g. Welcome guide.pdf"
            :disabled="busy"
          />
          <small>The .pdf extension is added if omitted.</small>
        </label>
        <div class="org-pdf__actions">
          <button
            type="button"
            class="iz-btn iz-btn--sm"
            :disabled="busy"
            @click="clearSelection"
          >Discard</button>
          <button
            type="button"
            class="iz-btn iz-btn--primary iz-btn--sm"
            :disabled="busy"
            @click="save"
          >{{ uploading ? "Uploading…" : "Save template" }}</button>
        </div>
      </div>
    </template>

    <ConfirmDialog
      v-if="showResetConfirmation"
      title="Reset project PDF?"
      message="The custom template will be removed and new projects will use the system default PDF."
      confirm-label="Reset to default"
      busy-label="Resetting…"
      :danger="true"
      :busy="resetting"
      :error="resetError"
      @confirm="resetToDefault"
      @cancel="closeResetConfirmation"
    />
  </div>
</template>

<script>
import ConfirmDialog from "./ConfirmDialog.vue";
import {
  deleteOrganizationPdf,
  getOrganizationPdfInfo,
  uploadOrganizationPdf,
} from "../services/projectCreatorApi";

/**
 * The default-project-PDF settings, lifted out of OrganizationSettingsModal so
 * they can sit inline in Organization Insights. Same endpoints and same rules;
 * what changed is that there is no modal footer, so Save appears only once a
 * file has been chosen and there is something to save.
 */
export default {
  name: "OrganizationPdfSettings",
  components: { ConfirmDialog },
  props: {
    organizationId: { type: Number, required: true },
  },
  data: function () {
    return {
      loading: true,
      uploading: false,
      resetting: false,
      hasCustomPdf: false,
      currentFileName: "",
      selectedFile: null,
      fileName: "",
      error: "",
      showResetConfirmation: false,
      resetError: "",
    };
  },
  computed: {
    busy: function () {
      return this.uploading || this.resetting;
    },
  },
  mounted: function () {
    this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      this.error = "";
      try {
        const info = await getOrganizationPdfInfo(this.organizationId);
        this.hasCustomPdf = Boolean(info && info.has_custom_pdf);
        this.currentFileName = (info && info.file_name) || "";
      } catch (error) {
        console.error("Failed to load organization PDF settings", error);
        this.error = this.errorMessage(error, "Could not load the PDF template settings.");
      } finally {
        this.loading = false;
      }
    },
    chooseFile() {
      if (!this.busy && this.$refs.fileInput) this.$refs.fileInput.click();
    },
    onFileSelected(event) {
      this.setFile(event.target.files && event.target.files[0]);
    },
    onDrop(event) {
      if (!this.busy) this.setFile(event.dataTransfer.files && event.dataTransfer.files[0]);
    },
    setFile(file) {
      if (!file) return;
      if (file.type !== "application/pdf" && !file.name.toLowerCase().endsWith(".pdf")) {
        this.error = "Please select a valid PDF document (.pdf).";
        this.selectedFile = null;
        return;
      }
      this.error = "";
      this.selectedFile = file;
      this.fileName = file.name;
    },
    clearSelection() {
      this.selectedFile = null;
      this.fileName = "";
      this.error = "";
      if (this.$refs.fileInput) this.$refs.fileInput.value = "";
    },
    normalizedFileName() {
      const name = String(this.fileName || "").trim();
      if (!name) return "";
      return /\.pdf$/i.test(name) ? name : `${name}.pdf`;
    },
    async save() {
      const fileName = this.normalizedFileName();
      if (!fileName) {
        this.error = "Enter a filename for the PDF template.";
        return;
      }
      this.uploading = true;
      this.error = "";
      try {
        await uploadOrganizationPdf(this.organizationId, this.selectedFile, fileName);
        this.clearSelection();
        await this.load();
      } catch (error) {
        console.error("Failed to upload organization PDF", error);
        this.error = this.errorMessage(error, "Could not upload the PDF template.");
      } finally {
        this.uploading = false;
      }
    },
    async resetToDefault() {
      this.resetting = true;
      this.resetError = "";
      try {
        await deleteOrganizationPdf(this.organizationId);
        this.hasCustomPdf = false;
        this.currentFileName = "";
        this.showResetConfirmation = false;
      } catch (error) {
        console.error("Failed to reset organization PDF", error);
        this.resetError = this.errorMessage(error, "Could not reset the PDF template.");
      } finally {
        this.resetting = false;
      }
    },
    closeResetConfirmation() {
      if (!this.resetting) {
        this.showResetConfirmation = false;
        this.resetError = "";
      }
    },
    formatFileSize(bytes) {
      if (!bytes && bytes !== 0) return "";
      if (bytes < 1024) return `${bytes} B`;
      if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
      return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    },
    errorMessage(error, fallback) {
      const data = error && error.response && error.response.data;
      if (data && typeof data.message === "string" && data.message) return data.message;
      return fallback;
    },
  },
};
</script>

<style scoped>
.org-pdf {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md, 12px);
}

.org-pdf__intro {
  margin: 0;
  font-size: var(--iz-fs-sm);
  color: var(--color-text-muted);
}

.org-pdf__error {
  margin: 0;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border-radius: var(--radius-sm, 6px);
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
  font-size: var(--iz-fs-sm);
}

.org-pdf__loading {
  padding: var(--spacing-lg, 16px);
}

.org-pdf__status {
  display: flex;
  align-items: center;
  gap: var(--spacing-md, 14px);
  padding: var(--spacing-md, 13px) var(--spacing-lg, 16px);
  border: 1px solid var(--bg-subtle);
  border-radius: var(--radius-sm, 8px);
  background: var(--bg-subtle);
}

.org-pdf__document {
  display: flex;
  color: var(--accent);
  flex: 0 0 auto;
}

.org-pdf__status-copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.org-pdf__status-copy strong {
  font-size: var(--iz-fs-sm);
}

.org-pdf__status-copy small {
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
}

.org-pdf__reset {
  margin-left: auto;
  flex: 0 0 auto;
}

.org-pdf__dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-xs, 5px);
  padding: var(--spacing-xl, 22px);
  border: 1.5px dashed var(--color-border);
  border-radius: var(--radius-sm, 8px);
  background: var(--bg-subtle);
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.org-pdf__dropzone:hover,
.org-pdf__dropzone--selected {
  border-color: var(--accent);
  background: var(--accent-bg);
}

.org-pdf__dropzone svg {
  color: var(--color-text-muted);
}

.org-pdf__dropzone strong {
  font-size: var(--iz-fs-sm);
}

.org-pdf__dropzone small {
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
}

.org-pdf__file-input {
  display: none;
}

.org-pdf__pending {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md, 12px);
}

.org-pdf__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs, 4px);
}

.org-pdf__field small {
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
}

.org-pdf__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm, 8px);
}
</style>
