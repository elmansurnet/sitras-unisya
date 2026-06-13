<template>
  <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
      <h3 class="text-sm font-semibold text-gray-800">
        {{ isNew ? 'Tambah Pertanyaan' : 'Edit Pertanyaan' }}
      </h3>
      <button @click="$emit('cancel')" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="p-4 space-y-4">

      <!-- Tipe & Urutan -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Tipe Pertanyaan <span class="text-red-500">*</span></label>
          <select v-model="form.type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option v-for="t in QUESTION_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Urutan</label>
          <input v-model.number="form.order" type="number" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
      </div>

      <!-- Teks pertanyaan -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Teks Pertanyaan <span class="text-red-500">*</span></label>
        <textarea
          v-model="form.question_text"
          rows="2"
          placeholder="Tuliskan pertanyaan..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 resize-none"
        />
        <p v-if="errors.question_text" class="mt-1 text-xs text-red-500">{{ errors.question_text }}</p>
      </div>

      <!-- Teks bantuan -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Teks Bantuan <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input
          v-model="form.help_text"
          type="text"
          placeholder="Petunjuk pengisian..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        />
      </div>

      <!-- Options (Radio, Checkbox, Select) -->
      <div v-if="hasOptions" class="space-y-2">
        <label class="block text-xs font-medium text-gray-600">Pilihan Jawaban <span class="text-red-500">*</span></label>
        <div
          v-for="(opt, idx) in form.options"
          :key="idx"
          class="flex items-center gap-2"
        >
          <span class="text-xs text-gray-400 w-5 text-center tabular-nums">{{ idx + 1 }}.</span>
          <input
            v-model="form.options[idx]"
            type="text"
            :placeholder="`Pilihan ${idx + 1}`"
            class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          />
          <button
            @click="removeOption(idx)"
            :disabled="form.options.length <= 2"
            class="rounded p-1 text-gray-300 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-30"
            title="Hapus pilihan"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <button
          @click="addOption"
          class="inline-flex items-center gap-1 text-xs text-teal-600 hover:text-teal-800"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Tambah pilihan
        </button>
        <p v-if="errors.options" class="text-xs text-red-500">{{ errors.options }}</p>
      </div>

      <!-- Scale settings -->
      <div v-if="form.type === 'scale'" class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Nilai Minimum</label>
          <input v-model.number="form.scale_min" type="number" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Nilai Maksimum</label>
          <input v-model.number="form.scale_max" type="number" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Label Minimum</label>
          <input v-model="form.scale_min_label" type="text" placeholder="Sangat tidak puas" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Label Maksimum</label>
          <input v-model="form.scale_max_label" type="text" placeholder="Sangat puas" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
      </div>

      <!-- Placeholder (text/textarea/number/date) -->
      <div v-if="hasPlaceholder">
        <label class="block text-xs font-medium text-gray-600 mb-1">Placeholder</label>
        <input
          v-model="form.placeholder"
          type="text"
          placeholder="Teks placeholder input..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        />
      </div>

      <!-- Min/Max Length (text / textarea) -->
      <div v-if="hasLength" class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Min. Karakter</label>
          <input v-model.number="form.min_length" type="number" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Max. Karakter</label>
          <input v-model.number="form.max_length" type="number" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
      </div>

      <!-- Required toggle -->
      <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
        <button
          type="button"
          @click="form.is_required = !form.is_required"
          :class="form.is_required ? 'bg-teal-600' : 'bg-gray-200'"
          class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
          role="switch"
          :aria-checked="form.is_required"
        >
          <span
            :class="form.is_required ? 'translate-x-4' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
          />
        </button>
        <span class="text-sm text-gray-700">Wajib diisi</span>
      </div>

      <!-- Error umum -->
      <p v-if="errors._general" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ errors._general }}</p>

    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
      <button
        v-if="!isNew"
        @click="$emit('delete', props.question)"
        class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        Hapus pertanyaan
      </button>
      <div v-else />

      <div class="flex gap-2">
        <button
          @click="$emit('cancel')"
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
        >
          Batal
        </button>
        <button
          @click="handleSave"
          :disabled="saving"
          class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
        >
          <svg v-if="saving" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
          </svg>
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { QUESTION_TYPES } from '@/constants/questionTypes'

const OPTION_TYPES       = ['radio', 'checkbox', 'select']
const PLACEHOLDER_TYPES  = ['text', 'textarea', 'number', 'email', 'date']
const LENGTH_TYPES       = ['text', 'textarea']

// ─── Props & Emits ───────────────────────────────────────────────────

const props = defineProps({
  /** Question object untuk mode edit; null/undefined = mode tambah baru */
  question:     { type: Object,          default: null },
  /** ID seksi tempat pertanyaan ini berada (wajib saat isNew) */
  sectionId:    { type: [Number, String], required: true },
  /** Urutan default saat tambah baru */
  defaultOrder: { type: Number,          default: 1 },
  /** Flag loading dari store (dipakai di luar untuk disable tombol builder) */
  saving:       { type: Boolean,         default: false },
})

const emit = defineEmits(['save', 'cancel', 'delete'])

// ─── State form ───────────────────────────────────────────────────────────────────

function buildForm(q) {
  const settings = q?.settings ?? {}
  return {
    type:            q?.type            ?? 'text',
    question_text:   q?.question_text   ?? '',
    help_text:       q?.help_text       ?? '',
    is_required:     q?.is_required     ?? false,
    order:           q?.order           ?? props.defaultOrder,
    options:         Array.isArray(q?.options) && q.options.length ? [...q.options] : ['', ''],
    placeholder:     settings.placeholder      ?? '',
    min_length:      settings.min_length       ?? null,
    max_length:      settings.max_length       ?? null,
    scale_min:       settings.scale_min        ?? 1,
    scale_max:       settings.scale_max        ?? 5,
    scale_min_label: settings.scale_min_label  ?? '',
    scale_max_label: settings.scale_max_label  ?? '',
  }
}

const form   = reactive(buildForm(props.question))
const errors = reactive({})

const isNew          = computed(() => !props.question?.id)
const hasOptions     = computed(() => OPTION_TYPES.includes(form.type))
const hasPlaceholder = computed(() => PLACEHOLDER_TYPES.includes(form.type))
const hasLength      = computed(() => LENGTH_TYPES.includes(form.type))

// Reset options ke ['',''] ketika beralih tipe ke option-based
watch(() => form.type, (newType, oldType) => {
  if (OPTION_TYPES.includes(newType) && !OPTION_TYPES.includes(oldType)) {
    form.options = ['', '']
  }
})

// ─── Options helpers ─────────────────────────────────────────────────────────────

function addOption() {
  form.options.push('')
}

function removeOption(idx) {
  if (form.options.length > 2) form.options.splice(idx, 1)
}

// ─── Validation ──────────────────────────────────────────────────────────────────────

function validate() {
  Object.keys(errors).forEach((k) => delete errors[k])
  let valid = true

  if (!form.question_text.trim()) {
    errors.question_text = 'Teks pertanyaan wajib diisi.'
    valid = false
  }
  if (hasOptions.value) {
    const filled = form.options.filter((o) => o.trim())
    if (filled.length < 2) {
      errors.options = 'Minimal 2 pilihan jawaban harus diisi.'
      valid = false
    }
  }
  return valid
}

// ─── Save ───────────────────────────────────────────────────────────────────────────────

function handleSave() {
  if (!validate()) return

  const payload = {
    section_id:    props.sectionId,
    type:          form.type,
    question_text: form.question_text.trim(),
    help_text:     form.help_text.trim() || null,
    is_required:   form.is_required,
    order:         form.order,
    options:       hasOptions.value ? form.options.filter((o) => o.trim()) : null,
    settings: {
      ...(hasPlaceholder.value && form.placeholder ? { placeholder: form.placeholder } : {}),
      ...(hasLength.value && form.min_length       ? { min_length: form.min_length }   : {}),
      ...(hasLength.value && form.max_length       ? { max_length: form.max_length }   : {}),
      ...(form.type === 'scale' ? {
        scale_min:       form.scale_min,
        scale_max:       form.scale_max,
        scale_min_label: form.scale_min_label || null,
        scale_max_label: form.scale_max_label || null,
      } : {}),
    },
  }

  emit('save', payload)
}
</script>
