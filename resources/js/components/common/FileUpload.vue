<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  accept: { type: String, default: '*' },
  maxSizeMb: { type: Number, default: 5 },
  label: { type: String, default: 'Klik atau seret file ke sini' },
  hint: { type: String, default: '' },
  modelValue: { type: [File, null], default: null },
  previewUrl: { type: String, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'error'])

const isDragging = ref(false)
const internalError = ref(null)
const previewSrc = ref(props.previewUrl ?? null)

const acceptedExtensions = computed(() => {
  if (!props.accept || props.accept === '*') return 'Semua format'
  return props.accept.replace(/image\//g, '.').replace(/,/g, ', ')
})

function validate(file) {
  if (!file) return null
  const maxBytes = props.maxSizeMb * 1024 * 1024
  if (file.size > maxBytes) {
    return `Ukuran file melebihi ${props.maxSizeMb}MB.`
  }
  if (props.accept !== '*') {
    const allowed = props.accept.split(',').map((s) => s.trim())
    const ok = allowed.some((type) => {
      if (type.startsWith('.')) return file.name.endsWith(type)
      return file.type === type || file.type.startsWith(type.replace('/*', '/'))
    })
    if (!ok) return `Format file tidak didukung. Gunakan: ${acceptedExtensions.value}`
  }
  return null
}

function handleFile(file) {
  if (!file) return
  const err = validate(file)
  if (err) {
    internalError.value = err
    emit('error', err)
    return
  }
  internalError.value = null

  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = (e) => (previewSrc.value = e.target.result)
    reader.readAsDataURL(file)
  } else {
    previewSrc.value = null
  }

  emit('update:modelValue', file)
}

function onFileInput(e) {
  handleFile(e.target.files[0])
}

function onDrop(e) {
  isDragging.value = false
  handleFile(e.dataTransfer.files[0])
}

function onDragOver(e) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave() {
  isDragging.value = false
}

function clear() {
  previewSrc.value = null
  internalError.value = null
  emit('update:modelValue', null)
}

const displayError = computed(() => props.error || internalError.value)
</script>

<template>
  <div class="file-upload">
    <div
      :class="[
        'relative flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-6 text-center transition-colors',
        isDragging
          ? 'border-[var(--color-primary)] bg-[var(--color-primary-highlight)]'
          : 'border-[var(--color-border)] bg-[var(--color-surface)] hover:border-[var(--color-primary)] hover:bg-[var(--color-surface-offset)]',
        loading ? 'opacity-60 pointer-events-none' : '',
      ]"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop.prevent="onDrop"
    >
      <!-- Preview image -->
      <template v-if="previewSrc">
        <img
          :src="previewSrc"
          alt="Preview"
          class="max-h-40 rounded-md object-contain"
          loading="lazy"
        />
        <button
          type="button"
          class="text-xs text-[var(--color-error)] underline hover:no-underline"
          @click="clear"
        >
          Hapus
        </button>
      </template>

      <!-- File icon + label -->
      <template v-else>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-[var(--color-text-faint)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <div>
          <label class="cursor-pointer text-sm font-medium text-[var(--color-primary)] hover:underline">
            {{ label }}
            <input
              type="file"
              class="sr-only"
              :accept="accept"
              @change="onFileInput"
            />
          </label>
          <p class="text-xs text-[var(--color-text-faint)] mt-1">
            {{ hint || `Maks. ${maxSizeMb}MB — ${acceptedExtensions}` }}
          </p>
        </div>
      </template>

      <!-- Loading overlay -->
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-[var(--color-surface)]/70 rounded-lg">
        <svg class="animate-spin w-6 h-6 text-[var(--color-primary)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
      </div>
    </div>

    <!-- Error -->
    <p v-if="displayError" class="mt-1.5 text-xs text-[var(--color-error)]">
      {{ displayError }}
    </p>

    <!-- File name display when non-image -->
    <p v-else-if="modelValue && !previewSrc" class="mt-1.5 text-xs text-[var(--color-text-muted)]">
      {{ modelValue.name }} ({{ (modelValue.size / 1024).toFixed(1) }} KB)
    </p>
  </div>
</template>
