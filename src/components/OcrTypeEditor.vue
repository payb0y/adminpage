<template>
  <div class="ocr-editor">
    <p v-if="error" class="ocr-editor__error" role="alert">{{ error }}</p>

    <div class="ocr-editor__head">
      <label class="ocr-editor__field ocr-editor__field--grow">
        <span class="iz-label">Document type name</span>
        <input
          v-model="form.name"
          class="iz-input"
          type="text"
          placeholder="e.g. Bodemrapport"
          :disabled="busy"
        />
      </label>
      <label class="ocr-editor__active">
        <input v-model="form.is_active" type="checkbox" :disabled="busy" />
        Active
      </label>
    </div>

    <div class="ocr-editor__fields-block">
      <span class="iz-label">Extraction fields</span>
      <p class="ocr-editor__hint">
        Every data point OCR must pull out of this kind of document.
      </p>
      <div class="ocr-editor__fields">
        <div
          v-for="(field, index) in form.fields"
          :key="'field-' + index"
          class="ocr-editor__field-row"
        >
          <input
            v-model="field.name"
            class="iz-input"
            type="text"
            placeholder="e.g. Totaalbedrag"
            :disabled="busy"
          />
          <button
            type="button"
            class="iz-btn iz-btn--ghost iz-btn--sm"
            :disabled="busy || form.fields.length === 1"
            @click="$emit('remove-field', index)"
          >Remove</button>
        </div>
      </div>
      <button
        type="button"
        class="iz-btn iz-btn--sm ocr-editor__add"
        :disabled="busy"
        @click="$emit('add-field')"
      >
        <span aria-hidden="true">+</span>
        Add another field
      </button>
    </div>

    <div class="ocr-editor__actions">
      <button
        v-if="form.id"
        type="button"
        class="iz-btn iz-btn--danger iz-btn--sm"
        :disabled="busy"
        @click="$emit('request-delete')"
      >Delete type</button>
      <span class="ocr-editor__actions-right">
        <button
          type="button"
          class="iz-btn iz-btn--sm"
          :disabled="busy"
          @click="$emit('reset')"
        >Reset</button>
        <button
          type="button"
          class="iz-btn iz-btn--primary iz-btn--sm"
          :disabled="busy"
          @click="$emit('save')"
        >{{ saveLabel }}</button>
      </span>
    </div>
  </div>
</template>

<script>
/**
 * The document-type form, split out of OrganizationOcrSettings so the same
 * markup serves both an existing type and a new one. Presentational: the
 * parent owns `form` and every action, this only renders and emits.
 *
 * `form` is mutated in place through v-model. Vue 2 warns on reassigning a
 * prop, not on writing to a property of an object prop, and the parent is the
 * single owner of that object.
 */
export default {
  name: "OcrTypeEditor",
  props: {
    form: {
      type: Object,
      required: true,
    },
    busy: {
      type: Boolean,
      default: false,
    },
    saveLabel: {
      type: String,
      default: "Save",
    },
    error: {
      type: String,
      default: "",
    },
  },
};
</script>

<style scoped>
.ocr-editor {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg, 16px);
  padding: var(--spacing-lg, 16px);
  border: 1px solid var(--accent);
  border-top: none;
  border-radius: 0 0 var(--radius-sm, 6px) var(--radius-sm, 6px);
  background: var(--bg-card);
}

.ocr-editor__error {
  margin: 0;
  padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
  border-radius: var(--radius-sm, 6px);
  background: var(--color-badge-danger-bg);
  color: var(--color-badge-danger-text);
  font-size: var(--iz-fs-sm);
}

.ocr-editor__head {
  display: flex;
  align-items: flex-end;
  gap: var(--spacing-md, 12px);
  flex-wrap: wrap;
}

.ocr-editor__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs, 4px);
  min-width: 0;
}

.ocr-editor__field--grow {
  flex: 1 1 220px;
}

.ocr-editor__active {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs, 6px);
  font-size: var(--iz-fs-sm);
  color: var(--color-text-secondary);
  padding-bottom: 6px;
  white-space: nowrap;
}

.ocr-editor__fields-block {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs, 6px);
}

.ocr-editor__hint {
  margin: 0 0 var(--spacing-xs, 6px);
  font-size: var(--iz-fs-xs);
  color: var(--color-text-muted);
}

.ocr-editor__fields {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs, 6px);
}

.ocr-editor__field-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
}

.ocr-editor__field-row .iz-input {
  flex: 1;
  min-width: 0;
}

.ocr-editor__add {
  align-self: flex-start;
  margin-top: var(--spacing-xs, 6px);
}

.ocr-editor__actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 8px);
  padding-top: var(--spacing-md, 12px);
  border-top: 1px solid var(--bg-subtle);
}

.ocr-editor__actions-right {
  margin-left: auto;
  display: flex;
  gap: var(--spacing-sm, 8px);
}
</style>
