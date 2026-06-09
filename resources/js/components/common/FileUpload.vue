<script setup>
/**
 * FileUpload.vue — Drag-drop file upload dengan preview & validasi client-side
 * Task 2A.22 | Sesuai 06_UI_UX.md & 07_SECURITY.md §6.1
 */
import { ref, computed } from 'vue'

const props = defineProps({
  accept:      { type: String,  default: '*' },
  maxSizeMb:   { type: Number,  default: 5 },
  multiple:    { type: Boolean, default: false },
  preview:     { type: Boolean, default: false }, // show image preview
  hint:        { type: String,  default: null },
  disabled:    { type: Boolean, default: false },
  modelValue:  { type: [File, Array], default: null },
})

const emit = defineEmits(['update:modelValue', 'error'])

const dragging  = ref(false)
const fileInput = ref(null)
const previews  = ref([]) // [{ name, url }]
const error     = ref(null)

const acceptLabel = computed(() => {
  if (!props.accept || props.accept === '*') return 'Semua format'
  return props.accept
    .split(',')
    .map((s) => s.trim().replace('image/', '').replace('application/', '').toUpperCase())
    .join(', ')
})

function validate(files) {
  error.value = null
  const maxBytes = props.maxSizeMb * 1024 * 1024
  for (const f of files) {
    if (f.size > maxBytes) {
      error.value = `Ukuran file "${f.name}" melebihi ${props.maxSizeMb} MB.`
      emit('error', error.value)
      return false
    }
    // Validasi ekstensi minimal
    if (props.accept && props.accept !== '*') {
      const accepted = props.accept.split(',').map((a) => a.trim())
      const match = accepted.some((a) => {
        if (a.startsWith('.')) return f.name.toLowerCase().endsWith(a.toLowerCase())
        return f.type === a || f.type.startsWith(a.replace('/*', ''))
      })
      if (!match) {
        error.value = `Format file "${f.name}" tidak diizinkan.`
        emit('error', error.value)
        return false
      }
    }
  }
  return true
}

function processFiles(files) {
  if (!files || !files.length) return
  const arr = [...files]
  if (!validate(arr)) return

  previews.value = []
  if (props.preview) {
    arr.forEach((f) => {
      if (f.type.startsWith('image/')) {
        const url = URL.createObjectURL(f)
        previews.value.push({ name: f.name, url })
      }
    })
  }

  const result = props.multiple ? arr : arr[0]
  emit('update:modelValue', result)
}

function onDragOver(e) {
  e.preventDefault()
  if (!props.disabled) dragging.value = true
}
function onDragLeave() { dragging.value = false }
function onDrop(e) {
  e.preventDefault()
  dragging.value = false
  if (!props.disabled) processFiles(e.dataTransfer?.files)
}
function onPick() {
  if (!props.disabled) fileInput.value?.click()
}
function onChange(e) {
  processFiles(e.target.files)
  e.target.value = '' // reset agar file yang sama bisa dipilih ulang
}

function clearPreview(idx) {
  if (previews.value[idx]) {
    URL.revokeObjectURL(previews.value[idx].url)
    previews.value.splice(idx, 1)
  }
  emit('update:modelValue', null)
}
</script>

<template>
  <div class="fu-wrap">
    <!-- Drop zone -->
    <div
      :class="['fu-zone', { 'fu-zone--dragging': dragging, 'fu-zone--disabled': disabled }]"
      role="button"
      tabindex="0"
      :aria-disabled="disabled"
      aria-label="Area upload file. Klik atau seret file ke sini."
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="onPick"
      @keydown.enter.prevent="onPick"
      @keydown.space.prevent="onPick"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="fu-input"
        tabindex="-1"
        @change="onChange"
      />

      <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5" class="fu-icon" aria-hidden="true">
        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
        <polyline points="17 8 12 3 7 8"/>
        <line x1="12" y1="3" x2="12" y2="15"/>
      </svg>

      <div class="fu-text">
        <span class="fu-main">
          <span v-if="dragging">Lepaskan file di sini</span>
          <span v-else>Klik atau seret file ke sini</span>
        </span>
        <span class="fu-hint">
          {{ hint ?? `Format: ${acceptLabel} · Maks. ${maxSizeMb} MB` }}
        </span>
      </div>
    </div>

    <!-- Error -->
    <p v-if="error" class="fu-error" role="alert">{{ error }}</p>

    <!-- Previews -->
    <div v-if="previews.length" class="fu-previews">
      <div
        v-for="(p, idx) in previews"
        :key="p.url"
        class="fu-preview-item"
      >
        <img
          :src="p.url"
          :alt="p.name"
          width="80" height="80"
          loading="lazy"
          class="fu-preview-img"
        />
        <button
          type="button"
          class="fu-preview-remove"
          :aria-label="`Hapus ${p.name}`"
          @click.stop="clearPreview(idx)"
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="3" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
        <span class="fu-preview-name">{{ p.name }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fu-wrap { display: flex; flex-direction: column; gap: var(--space-3); }

.fu-zone {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-8) var(--space-6);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: var(--space-3);
  cursor: pointer;
  background: var(--color-surface);
  text-align: center;
  transition: border-color var(--transition-interactive), background var(--transition-interactive);
  min-height: 140px;
}
.fu-zone:hover:not(.fu-zone--disabled),
.fu-zone:focus-visible {
  border-color: var(--color-primary);
  background: var(--color-primary-highlight);
  outline: none;
}
.fu-zone--dragging {
  border-color: var(--color-primary);
  background: var(--color-primary-highlight);
}
.fu-zone--disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

.fu-input { display: none; }
.fu-icon  { color: var(--color-text-faint); flex-shrink: 0; }
.fu-zone--dragging .fu-icon { color: var(--color-primary); }

.fu-text  { display: flex; flex-direction: column; gap: var(--space-1); }
.fu-main  { font-size: var(--text-sm); font-weight: 500; color: var(--color-text); }
.fu-hint  { font-size: var(--text-xs); color: var(--color-text-muted); }

.fu-error { font-size: var(--text-xs); color: var(--color-error); margin: 0; }

.fu-previews {
  display: flex; flex-wrap: wrap; gap: var(--space-3);
}
.fu-preview-item {
  position: relative;
  display: flex; flex-direction: column; align-items: center; gap: var(--space-1);
  width: 80px;
}
.fu-preview-img {
  width: 80px; height: 80px;
  object-fit: cover;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.fu-preview-remove {
  position: absolute; top: -6px; right: -6px;
  width: 20px; height: 20px;
  background: var(--color-error);
  color: #fff;
  border: none; border-radius: var(--radius-full);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
.fu-preview-name {
  font-size: 0.65rem;
  color: var(--color-text-muted);
  word-break: break-all;
  text-align: center;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
