<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  /** Accepted MIME types or extensions, e.g. '.xlsx,.csv' */
  accept: {
    type: String,
    default: '.xlsx,.csv',
  },
  /** Max size in KB */
  maxSizeKb: {
    type: Number,
    default: 10240, // 10 MB
  },
  modelValue: {
    type: [File, null],
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  /** Label shown in the dropzone */
  label: {
    type: String,
    default: 'Seret file ke sini atau',
  },
  hint: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'error'])

const isDragging = ref(false)
const fileInputRef = ref(null)
const internalError = ref('')

const file = computed(() => props.modelValue)

const fileSizeLabel = computed(() => {
  if (!file.value) return ''
  const kb = file.value.size / 1024
  return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb.toFixed(0)} KB`
})

function formatAccept() {
  return props.accept
    .split(',')
    .map((a) => a.trim().replace('.', '').toUpperCase())
    .join(', ')
}

function validate(f) {
  internalError.value = ''
  if (!f) return false

  // Size check
  if (f.size / 1024 > props.maxSizeKb) {
    const maxMb = (props.maxSizeKb / 1024).toFixed(0)
    internalError.value = `Ukuran file melebihi batas maksimum ${maxMb} MB.`
    emit('error', internalError.value)
    return false
  }

  // Extension check
  const allowedExts = props.accept
    .split(',')
    .map((a) => a.trim().toLowerCase())
  const ext = '.' + f.name.split('.').pop().toLowerCase()
  if (!allowedExts.includes(ext)) {
    internalError.value = `Format file tidak diizinkan. Gunakan: ${formatAccept()}`
    emit('error', internalError.value)
    return false
  }

  return true
}

function handleFile(f) {
  if (!f) return
  if (validate(f)) {
    emit('update:modelValue', f)
  } else {
    emit('update:modelValue', null)
  }
}

function onDrop(e) {
  isDragging.value = false
  if (props.disabled) return
  const f = e.dataTransfer?.files?.[0]
  handleFile(f)
}

function onInputChange(e) {
  handleFile(e.target.files?.[0])
}

function openPicker() {
  if (!props.disabled) fileInputRef.value?.click()
}

function clear() {
  emit('update:modelValue', null)
  internalError.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <!-- Drop zone -->
    <div
      :class="[
        'relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-6 py-8 text-center transition-colors',
        isDragging
          ? 'border-teal-400 bg-teal-50 dark:bg-teal-900/20'
          : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50',
        props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/10',
        internalError ? 'border-red-400 bg-red-50 dark:bg-red-900/10' : '',
      ]"
      @dragover.prevent="isDragging = !props.disabled"
      @dragleave="isDragging = false"
      @drop.prevent="onDrop"
      @click="openPicker"
    >
      <input
        ref="fileInputRef"
        type="file"
        class="sr-only"
        :accept="accept"
        :disabled="disabled"
        @change="onInputChange"
      />

      <!-- No file selected -->
      <template v-if="!file">
        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <div>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ label }}
            <span class="text-teal-600 dark:text-teal-400 font-medium">klik untuk pilih</span>
          </p>
          <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
            {{ formatAccept() }} &bull; Maks. {{ (maxSizeKb / 1024).toFixed(0) }} MB
          </p>
          <p v-if="hint" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ hint }}</p>
        </div>
      </template>

      <!-- File selected -->
      <template v-else>
        <div class="flex items-center gap-3 w-full max-w-xs">
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/40">
            <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="flex-1 min-w-0 text-left">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ fileSizeLabel }}</p>
          </div>
          <button
            type="button"
            class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 transition-colors"
            aria-label="Hapus file"
            @click.stop="clear"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </template>
    </div>

    <!-- Error message -->
    <p v-if="internalError" class="text-xs text-red-600 dark:text-red-400">{{ internalError }}</p>
  </div>
</template>
