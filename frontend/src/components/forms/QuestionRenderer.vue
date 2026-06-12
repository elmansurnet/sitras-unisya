<template>
  <div
    :class="[
      'group relative rounded-xl border transition-all duration-150',
      isBuilderMode
        ? (isActive ? 'border-teal-400 shadow-md shadow-teal-100' : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm')
        : 'border-gray-200 bg-white',
    ]"
  >

    <!-- ═══ BUILDER TOOLBAR (hanya mode builder) ══════════════════════════════════ -->
    <div
      v-if="isBuilderMode"
      :class="[
        'flex items-center justify-between border-b px-3 py-2 transition-colors',
        isActive ? 'border-teal-200 bg-teal-50' : 'border-gray-100 bg-gray-50',
      ]"
    >
      <!-- Kiri: nomor + badge tipe -->
      <div class="flex items-center gap-2">
        <!-- Drag handle -->
        <span
          class="cursor-grab text-gray-300 hover:text-gray-500 active:cursor-grabbing"
          title="Seret untuk mengurutkan"
        >
          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
          </svg>
        </span>

        <span class="text-xs font-semibold text-gray-500 tabular-nums">{{ question.order }}.</span>

        <span :class="typeBadgeClass" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
          {{ typeLabel }}
        </span>

        <span v-if="question.is_required" class="text-xs text-red-500" title="Wajib diisi">*</span>

        <!-- Badge: ada kondisi -->
        <span
          v-if="hasConditions"
          class="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700"
          title="Pertanyaan ini punya logika kondisional"
        >
          <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Z" />
          </svg>
          Kondisi
        </span>
      </div>

      <!-- Kanan: action buttons -->
      <div class="flex items-center gap-0.5">
        <!-- Edit -->
        <button
          @click.stop="$emit('edit', question)"
          :class="isActive ? 'text-teal-600 hover:bg-teal-100' : 'text-gray-400 hover:bg-gray-200 hover:text-gray-600'"
          class="rounded p-1.5"
          title="Edit pertanyaan"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
          </svg>
        </button>

        <!-- Conditional Logic -->
        <button
          @click.stop="$emit('toggle-logic', question)"
          :class="hasConditions ? 'text-amber-600 hover:bg-amber-100' : 'text-gray-400 hover:bg-gray-200 hover:text-gray-600'"
          class="rounded p-1.5"
          title="Logika kondisional"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
          </svg>
        </button>

        <!-- Move Up -->
        <button
          @click.stop="$emit('move-up', question)"
          :disabled="isFirst"
          class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-30"
          title="Pindah ke atas"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
          </svg>
        </button>

        <!-- Move Down -->
        <button
          @click.stop="$emit('move-down', question)"
          :disabled="isLast"
          class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-30"
          title="Pindah ke bawah"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
          </svg>
        </button>

        <!-- Delete -->
        <button
          @click.stop="$emit('delete', question)"
          class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500"
          title="Hapus pertanyaan"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
          </svg>
        </button>
      </div>
    </div>

    <!-- ═══ QUESTION BODY ═══════════════════════════════════════════════════════════ -->
    <div class="p-4 space-y-3">

      <!-- Label + wajib -->
      <div>
        <p class="text-sm font-medium text-gray-900 leading-snug">
          <span v-if="!isBuilderMode && question.order" class="text-gray-400 tabular-nums">{{ question.order }}. </span>
          {{ question.question_text }}
          <span v-if="question.is_required" class="ml-0.5 text-red-500">*</span>
        </p>
        <p v-if="question.help_text" class="mt-0.5 text-xs text-gray-500">{{ question.help_text }}</p>
      </div>

      <!-- ── Tipe: text ──────────────────────────────────────────────────────────── -->
      <template v-if="question.type === 'text'">
        <input
          v-model="localValue"
          type="text"
          :placeholder="question.settings?.placeholder ?? 'Jawaban Anda...' "
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50"
        />
      </template>

      <!-- ── Tipe: textarea ──────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'textarea'">
        <textarea
          v-model="localValue"
          rows="3"
          :placeholder="question.settings?.placeholder ?? 'Jawaban Anda...' "
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50 resize-none"
        />
      </template>

      <!-- ── Tipe: radio ─────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'radio'">
        <div class="space-y-2">
          <label
            v-for="opt in question.options"
            :key="opt"
            class="flex items-center gap-2.5 cursor-pointer"
          >
            <input
              type="radio"
              :name="`q-${question.id}`"
              :value="opt"
              v-model="localValue"
              :disabled="isBuilderMode"
              class="h-4 w-4 accent-teal-600"
            />
            <span class="text-sm text-gray-700">{{ opt }}</span>
          </label>
        </div>
      </template>

      <!-- ── Tipe: checkbox ──────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'checkbox'">
        <div class="space-y-2">
          <label
            v-for="opt in question.options"
            :key="opt"
            class="flex items-center gap-2.5 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="opt"
              v-model="localValue"
              :disabled="isBuilderMode"
              class="h-4 w-4 rounded accent-teal-600"
            />
            <span class="text-sm text-gray-700">{{ opt }}</span>
          </label>
        </div>
      </template>

      <!-- ── Tipe: select ────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'select'">
        <select
          v-model="localValue"
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50"
        >
          <option value="">-- Pilih jawaban --</option>
          <option v-for="opt in question.options" :key="opt" :value="opt">{{ opt }}</option>
        </select>
      </template>

      <!-- ── Tipe: scale ─────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'scale'">
        <div class="space-y-2">
          <div class="flex gap-2 flex-wrap">
            <button
              v-for="n in scaleRange"
              :key="n"
              type="button"
              @click="!isBuilderMode && (localValue = n)"
              :class="[
                'h-10 w-10 rounded-lg border-2 text-sm font-semibold transition-all',
                localValue === n
                  ? 'border-teal-500 bg-teal-500 text-white'
                  : 'border-gray-200 bg-white text-gray-700 hover:border-teal-300',
                isBuilderMode ? 'cursor-default' : 'cursor-pointer',
              ]"
            >
              {{ n }}
            </button>
          </div>
          <div class="flex justify-between text-xs text-gray-400">
            <span>{{ question.settings?.scale_min_label ?? '' }}</span>
            <span>{{ question.settings?.scale_max_label ?? '' }}</span>
          </div>
        </div>
      </template>

      <!-- ── Tipe: date ──────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'date'">
        <input
          v-model="localValue"
          type="date"
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50"
        />
      </template>

      <!-- ── Tipe: number ────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'number'">
        <input
          v-model.number="localValue"
          type="number"
          :placeholder="question.settings?.placeholder ?? 'Masukkan angka...' "
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50"
        />
      </template>

      <!-- ── Tipe: email ─────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'email'">
        <input
          v-model="localValue"
          type="email"
          :placeholder="question.settings?.placeholder ?? 'nama@email.com'"
          :disabled="isBuilderMode"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-default disabled:bg-gray-50"
        />
      </template>

      <!-- ── Tipe: file ──────────────────────────────────────────────────────────── -->
      <template v-else-if="question.type === 'file'">
        <div
          :class="[
            'flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 text-center',
            isBuilderMode ? 'border-gray-200 bg-gray-50 cursor-default' : 'border-teal-200 bg-teal-50 cursor-pointer hover:bg-teal-100',
          ]"
          @click="!isBuilderMode && $refs.fileInput?.click()"
        >
          <svg class="h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
          </svg>
          <p class="text-sm text-gray-500">
            <span v-if="fileName" class="font-medium text-teal-700">{{ fileName }}</span>
            <span v-else>Klik untuk pilih file</span>
          </p>
          <p class="text-xs text-gray-400 mt-1">PDF, DOCX, JPG, PNG (maks. 5 MB)</p>
          <input
            v-if="!isBuilderMode"
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            @change="onFileChange"
          />
        </div>
      </template>

      <!-- ── Tipe tidak dikenal ──────────────────────────────────────────────────── -->
      <template v-else>
        <div class="rounded-lg bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
          Tipe pertanyaan tidak dikenal: <code>{{ question.type }}</code>
        </div>
      </template>

      <!-- Error validasi (mode pengisian) -->
      <p v-if="!isBuilderMode && validationError" class="text-xs text-red-500">{{ validationError }}</p>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// ─── Props & Emits ───────────────────────────────────────────────────────────

const props = defineProps({
  /** Objek pertanyaan lengkap dari store (question_text, type, options, settings, ...) */
  question: { type: Object, required: true },

  /**
   * Mode tampilan:
   * - 'builder'  → tampilkan toolbar, input disabled (hanya struktur)
   * - 'preview'  → input aktif tapi tidak ada submit/binding ke store
   * - 'fill'     → input aktif, v-model ke answer store (dipakai SurveyPage)
   */
  mode: {
    type: String,
    default: 'fill',
    validator: (v) => ['builder', 'preview', 'fill'].includes(v),
  },

  /** Nilai jawaban saat ini (dipakai mode fill & preview). Dua-arah via v-model. */
  modelValue: { type: [String, Number, Array, null], default: null },

  /** Apakah pertanyaan ini aktif/terpilih di builder */
  isActive: { type: Boolean, default: false },

  /** Posisi dalam list seksi — untuk disable move-up/down */
  isFirst: { type: Boolean, default: false },
  isLast:  { type: Boolean, default: false },

  /** Error validasi dari luar (SurveyPage inject) */
  error: { type: String, default: '' },
})

const emit = defineEmits([
  'update:modelValue',
  'edit',
  'delete',
  'move-up',
  'move-down',
  'toggle-logic',
])

// ─── Computed helpers ────────────────────────────────────────────────────────

const isBuilderMode = computed(() => props.mode === 'builder')

const hasConditions = computed(() => {
  const cl = props.question.conditional_logic
  return Array.isArray(cl) ? cl.length > 0 : (cl?.conditions?.length > 0)
})

const BADGE_CLASSES = {
  text:      'bg-sky-100 text-sky-700',
  textarea:  'bg-sky-100 text-sky-700',
  radio:     'bg-violet-100 text-violet-700',
  checkbox:  'bg-violet-100 text-violet-700',
  select:    'bg-violet-100 text-violet-700',
  scale:     'bg-orange-100 text-orange-700',
  date:      'bg-rose-100 text-rose-700',
  number:    'bg-teal-100 text-teal-700',
  email:     'bg-teal-100 text-teal-700',
  file:      'bg-gray-100 text-gray-600',
}

const TYPE_LABELS = {
  text:      'Teks',
  textarea:  'Teks Panjang',
  radio:     'Radio',
  checkbox:  'Checkbox',
  select:    'Dropdown',
  scale:     'Skala',
  date:      'Tanggal',
  number:    'Angka',
  email:     'Email',
  file:      'File',
}

const typeBadgeClass = computed(() => BADGE_CLASSES[props.question.type] ?? 'bg-gray-100 text-gray-600')
const typeLabel      = computed(() => TYPE_LABELS[props.question.type]   ?? props.question.type)

const scaleRange = computed(() => {
  const min = props.question.settings?.scale_min ?? 1
  const max = props.question.settings?.scale_max ?? 5
  const result = []
  for (let i = min; i <= max; i++) result.push(i)
  return result
})

// ─── v-model bridge ──────────────────────────────────────────────────────────
// Kita tidak mutasi prop langsung. localValue adalah mirror dua arah.

const localValue = ref(
  props.question.type === 'checkbox'
    ? (Array.isArray(props.modelValue) ? [...props.modelValue] : [])
    : (props.modelValue ?? null)
)

watch(() => props.modelValue, (v) => {
  if (props.question.type === 'checkbox') {
    localValue.value = Array.isArray(v) ? [...v] : []
  } else {
    localValue.value = v ?? null
  }
})

watch(localValue, (v) => {
  if (!isBuilderMode.value) emit('update:modelValue', v)
}, { deep: true })

// ─── File upload ─────────────────────────────────────────────────────────────

const fileName = ref('')

function onFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  fileName.value = file.name
  emit('update:modelValue', file)
}

// ─── Validation error (dari prop 'error') ────────────────────────────────────

const validationError = computed(() => props.error || '')
</script>
