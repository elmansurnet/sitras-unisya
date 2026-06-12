<script setup>
/**
 * FileUpload.vue — Komponen upload file drag-drop
 *
 * Props:
 *   accept       : String  — MIME types, e.g. '.xlsx,.xls,.csv'
 *   maxSizeMb    : Number  — batas ukuran file dalam MB (default: 10)
 *   label        : String
 *   hint         : String  — teks hint di bawah label
 *   modelValue   : File|null
 *   loading      : Boolean
 *
 * Emits:
 *   update:modelValue(File|null)
 *   error(message: string)
 */
import { ref, computed } from 'vue'

const props = defineProps({
  accept:     { type: String,  default: '.xlsx,.xls,.csv' },
  maxSizeMb:  { type: Number,  default: 10 },
  label:      { type: String,  default: 'Pilih atau seret file ke sini' },
  hint:       { type: String,  default: '' },
  modelValue: { type: Object,  default: null },
  loading:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'error'])

const isDragging = ref(false)
const inputRef   = ref(null)

const fileName = computed(() => props.modelValue?.name ?? null)
const fileSize = computed(() => {
  if (!props.modelValue) return null
  const bytes = props.modelValue.size
  if (bytes < 1024)         return `${bytes} B`
  if (bytes < 1024 ** 2)    return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 ** 2).toFixed(2)} MB`
})

const acceptedTypes = computed(() =>
  props.accept
    .split(',')
    .map(t => t.trim())
)

function validate(file) {
  if (!file) return 'File tidak valid.'

  // Cek ekstensi / MIME
  const ext = '.' + file.name.split('.').pop().toLowerCase()
  const validExt = acceptedTypes.value.some(
    t => t.startsWith('.') ? t.toLowerCase() === ext : file.type === t
  )
  if (!validExt) {
    return `Format file tidak didukung. Gunakan: ${props.accept}`
  }

  // Cek ukuran
  if (file.size > props.maxSizeMb * 1024 * 1024) {
    return `Ukuran file terlalu besar (maks. ${props.maxSizeMb} MB).`
  }

  return null
}

function handleFiles(files) {
  const file = files[0]
  if (!file) return

  const err = validate(file)
  if (err) {
    emit('error', err)
    return
  }

  emit('update:modelValue', file)
}

function onInputChange(e) {
  handleFiles(e.target.files)
}

function onDrop(e) {
  isDragging.value = false
  handleFiles(e.dataTransfer.files)
}

function clearFile() {
  emit('update:modelValue', null)
  if (inputRef.value) inputRef.value.value = ''
}

function openPicker() {
  if (!props.loading) inputRef.value?.click()
}
</script>

<template>
  <div class="w-full">
    <!-- Drop zone -->
    <div
      :class="[
        'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center transition-colors',
        isDragging
          ? 'border-primary-400 bg-primary-50'
          : 'border-gray-300 bg-gray-50 hover:border-gray-400 hover:bg-gray-100',
        loading ? 'pointer-events-none opacity-60' : 'cursor-pointer',
      ]"
      @click="openPicker"
      @dragenter.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @dragover.prevent
      @drop.prevent="onDrop"
    >
      <!-- Hidden input -->
      <input
        ref="inputRef"
        type="file"
        :accept="accept"
        class="sr-only"
        @change="onInputChange"
      />

      <!-- File selected state -->
      <template v-if="fileName">
        <svg class="mb-3 h-10 w-10 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="mb-0.5 text-sm font-semibold text-gray-800">{{ fileName }}</p>
        <p class="text-xs text-gray-500">{{ fileSize }}</p>
        <button
          type="button"
          class="mt-3 text-xs text-red-500 hover:text-red-700 underline focus:outline-none"
          @click.stop="clearFile"
        >
          Hapus file
        </button>
      </template>

      <!-- Empty state -->
      <template v-else>
        <svg class="mb-3 h-10 w-10 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        <p class="text-sm font-medium text-gray-700">{{ label }}</p>
        <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
        <p v-else class="mt-1 text-xs text-gray-500">
          Format: {{ accept }} &bull; Maks. {{ maxSizeMb }} MB
        </p>
      </template>
    </div>
  </div>
</template>
