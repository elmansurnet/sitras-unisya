<template>
  <div class="rounded-xl border border-amber-200 bg-amber-50">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-amber-200 px-4 py-3">
      <div class="flex items-center gap-2">
        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
        </svg>
        <h4 class="text-sm font-semibold text-amber-800">Logika Kondisional</h4>
        <span v-if="localConditions.length" class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">{{ localConditions.length }}</span>
      </div>
      <button
        @click="addCondition"
        class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
      >
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Kondisi
      </button>
    </div>

    <div class="p-4">

      <!-- Empty state -->
      <p v-if="!localConditions.length" class="text-sm text-amber-700 text-center py-2">
        Belum ada kondisi. Pertanyaan ini selalu ditampilkan.
      </p>

      <!-- Kondisi list -->
      <div class="space-y-3">
        <div
          v-for="(cond, idx) in localConditions"
          :key="idx"
          class="rounded-lg border border-amber-200 bg-white p-3 space-y-3"
        >
          <!-- Row header -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Kondisi {{ idx + 1 }}</span>
            <button
              @click="removeCondition(idx)"
              class="rounded p-0.5 text-amber-400 hover:bg-amber-50 hover:text-red-500"
              title="Hapus kondisi"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- WHEN: pilih pertanyaan sumber -->
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="sm:col-span-1">
              <label class="block text-xs font-medium text-gray-600 mb-1">Ketika pertanyaan</label>
              <select
                v-model="cond.when_question_id"
                @change="onSourceChange(idx)"
                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
              >
                <option value="">Pilih pertanyaan...</option>
                <option
                  v-for="q in availableQuestions"
                  :key="q.id"
                  :value="q.id"
                >
                  #{{ q.order }} {{ q.question_text | truncate }}
                </option>
              </select>
            </div>

            <!-- Operator -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Operator</label>
              <select
                v-model="cond.operator"
                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
              >
                <option v-for="op in operatorsFor(cond.when_question_id)" :key="op.value" :value="op.value">{{ op.label }}</option>
              </select>
            </div>

            <!-- Value -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Nilai</label>
              <!-- Dropdown opsi jika sumber = radio/select -->
              <select
                v-if="sourceIsOption(cond.when_question_id)"
                v-model="cond.value"
                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
              >
                <option value="">Pilih nilai...</option>
                <option v-for="opt in optionsOf(cond.when_question_id)" :key="opt" :value="opt">{{ opt }}</option>
              </select>
              <!-- Input bebas untuk tipe lain -->
              <input
                v-else
                v-model="cond.value"
                type="text"
                placeholder="Nilai..."
                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
              />
            </div>
          </div>

          <!-- THEN: aksi -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Maka pertanyaan ini</label>
            <div class="flex gap-2">
              <label v-for="act in ACTIONS" :key="act.value" class="flex items-center gap-1.5 cursor-pointer">
                <input
                  type="radio"
                  :name="`action-${idx}`"
                  :value="act.value"
                  v-model="cond.action"
                  class="accent-amber-600"
                />
                <span class="text-xs text-gray-700">{{ act.label }}</span>
              </label>
            </div>
          </div>

          <!-- Preview kondisi (ringkasan teks) -->
          <p class="text-xs text-amber-700 bg-amber-50 rounded px-2 py-1 leading-relaxed">
            {{ summaryOf(cond) }}
          </p>
        </div>
      </div>

      <!-- Logika AND/OR jika ada >1 kondisi -->
      <div v-if="localConditions.length > 1" class="mt-3 flex items-center gap-3">
        <span class="text-xs text-gray-600">Gabungkan kondisi dengan:</span>
        <label class="flex items-center gap-1 cursor-pointer">
          <input type="radio" value="AND" v-model="localLogic" class="accent-amber-600" />
          <span class="text-xs font-medium text-gray-700">AND (semua harus terpenuhi)</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
          <input type="radio" value="OR" v-model="localLogic" class="accent-amber-600" />
          <span class="text-xs font-medium text-gray-700">OR (salah satu cukup)</span>
        </label>
      </div>

      <!-- Save bar -->
      <div v-if="localConditions.length" class="mt-4 flex justify-end gap-2">
        <button
          @click="cancel"
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50"
        >
          Batal
        </button>
        <button
          @click="save"
          class="rounded-lg bg-amber-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
        >
          Simpan Logika
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// ─── Constants ──────────────────────────────────────────────────────────────────

const ACTIONS = [
  { value: 'show',    label: 'Tampilkan' },
  { value: 'hide',    label: 'Sembunyikan' },
  { value: 'require', label: 'Wajibkan' },
]

const BASE_OPERATORS = [
  { value: 'equals',          label: 'sama dengan' },
  { value: 'not_equals',      label: 'tidak sama dengan' },
  { value: 'contains',        label: 'mengandung' },
  { value: 'not_contains',    label: 'tidak mengandung' },
]

const NUMERIC_OPERATORS = [
  ...BASE_OPERATORS,
  { value: 'greater_than',    label: 'lebih besar dari' },
  { value: 'less_than',       label: 'lebih kecil dari' },
]

const OPTION_OPERATORS = [
  { value: 'equals',          label: 'sama dengan' },
  { value: 'not_equals',      label: 'tidak sama dengan' },
  { value: 'contains',        label: 'mengandung' },
]

// ─── Props & Emits ───────────────────────────────────────────────────────────

const props = defineProps({
  /** Array kondisi saat ini dari DB (conditional_logic JSON) */
  conditions: { type: Array, default: () => [] },
  /** Gabungan AND|OR (default AND) */
  logic: { type: String, default: 'AND' },
  /** Semua pertanyaan di kuesioner (kecuali diri sendiri) untuk dipilih sebagai sumber */
  availableQuestions: { type: Array, default: () => [] },
})

const emit = defineEmits(['save', 'cancel'])

// ─── Local state (clone supaya tidak mutasi prop langsung) ───────────────────────

const localConditions = ref(deepClone(props.conditions))
const localLogic      = ref(props.logic)

watch(() => props.conditions, (v) => { localConditions.value = deepClone(v) }, { deep: true })
watch(() => props.logic,      (v) => { localLogic.value = v })

function deepClone(v) {
  return JSON.parse(JSON.stringify(v))
}

// ─── Helpers untuk pertanyaan sumber ─────────────────────────────────────────

function findQuestion(id) {
  return props.availableQuestions.find(q => String(q.id) === String(id))
}

function sourceIsOption(questionId) {
  const q = findQuestion(questionId)
  return q && ['radio', 'select'].includes(q.type)
}

function optionsOf(questionId) {
  const q = findQuestion(questionId)
  return Array.isArray(q?.options) ? q.options.filter(Boolean) : []
}

function operatorsFor(questionId) {
  const q = findQuestion(questionId)
  if (!q) return BASE_OPERATORS
  if (['radio', 'select'].includes(q.type)) return OPTION_OPERATORS
  if (['number', 'scale'].includes(q.type)) return NUMERIC_OPERATORS
  return BASE_OPERATORS
}

function onSourceChange(idx) {
  localConditions.value[idx].operator = 'equals'
  localConditions.value[idx].value    = ''
}

// ─── CRUD kondisi ───────────────────────────────────────────────────────────────

function addCondition() {
  localConditions.value.push({
    when_question_id: '',
    operator:         'equals',
    value:            '',
    action:           'show',
  })
}

function removeCondition(idx) {
  localConditions.value.splice(idx, 1)
}

// ─── Summary text ─────────────────────────────────────────────────────────────────

function summaryOf(cond) {
  if (!cond.when_question_id) return '— Pilih pertanyaan sumber terlebih dahulu.'
  const q = findQuestion(cond.when_question_id)
  const qtxt = q ? `"${q.question_text.substring(0, 40)}${q.question_text.length > 40 ? '...' : ''}"` : `pertanyaan #${cond.when_question_id}`
  const opLabel = operatorsFor(cond.when_question_id).find(o => o.value === cond.operator)?.label ?? cond.operator
  const actLabel = ACTIONS.find(a => a.value === cond.action)?.label ?? cond.action
  const valTxt = cond.value ? `"${cond.value}"` : '(belum diisi)'
  return `Jika ${qtxt} ${opLabel} ${valTxt} → ${actLabel} pertanyaan ini.`
}

// ─── Save / cancel ────────────────────────────────────────────────────────────────

function save() {
  emit('save', {
    conditions: deepClone(localConditions.value),
    logic:      localLogic.value,
  })
}

function cancel() {
  localConditions.value = deepClone(props.conditions)
  localLogic.value      = props.logic
  emit('cancel')
}
</script>
