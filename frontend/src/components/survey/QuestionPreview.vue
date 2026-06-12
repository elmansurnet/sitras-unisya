<script setup>
/**
 * QuestionPreview.vue — Renderer Dinamis Tipe Pertanyaan Survei
 *
 * Props:
 *   question    : object   — objek Question dari API (id, question_text,
 *                            question_type, is_required, options[], helper_text,
 *                            min_value, max_value, max_length)
 *   modelValue  : object   — { answer_value, answer_options, answer_text }
 *   showError   : boolean  — tampilkan pesan wajib diisi jika true
 *
 * Emits:
 *   update:modelValue — { answer_value, answer_options, answer_text }
 *
 * Tipe yang didukung (06_UI_UX.md §3.6):
 *   text | textarea | radio | checkbox | select |
 *   likert | rating | date | file | number
 *
 * Digunakan di:
 *   pages/alumni/SurveyPage.vue
 *   pages/employer/SurveyPage.vue
 */
import { computed, ref } from 'vue'

const props = defineProps({
  question: {
    type    : Object,
    required: true,
  },
  modelValue: {
    type   : Object,
    default: () => ({ answer_value: null, answer_options: null, answer_text: null }),
  },
  showError: {
    type   : Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

// ---------------------------------------------------------------------------
// Helpers emit
// ---------------------------------------------------------------------------
function emitValue(field, value) {
  emit('update:modelValue', {
    answer_value  : props.modelValue?.answer_value   ?? null,
    answer_options: props.modelValue?.answer_options ?? null,
    answer_text   : props.modelValue?.answer_text    ?? null,
    [field]       : value,
  })
}

// ---------------------------------------------------------------------------
// Computed bindings per tipe
// ---------------------------------------------------------------------------
const textValue = computed({
  get: () => props.modelValue?.answer_value ?? '',
  set: (v) => emitValue('answer_value', v),
})

const textareaValue = computed({
  get: () => props.modelValue?.answer_text ?? '',
  set: (v) => emitValue('answer_text', v),
})

const radioValue = computed({
  get: () => props.modelValue?.answer_value ?? null,
  set: (v) => emitValue('answer_value', v),
})

const selectValue = computed({
  get: () => props.modelValue?.answer_value ?? '',
  set: (v) => emitValue('answer_value', v),
})

const numberValue = computed({
  get: () => props.modelValue?.answer_value ?? '',
  set: (v) => emitValue('answer_value', v === '' ? null : Number(v)),
})

const dateValue = computed({
  get: () => props.modelValue?.answer_value ?? '',
  set: (v) => emitValue('answer_value', v || null),
})

// Checkbox — answer_options: number[] (array option id)
const checkboxValue = computed(() => props.modelValue?.answer_options ?? [])

function toggleCheckbox(optionId) {
  const current = checkboxValue.value
  const updated = current.includes(optionId)
    ? current.filter((id) => id !== optionId)
    : [...current, optionId]
  emitValue('answer_options', updated)
}

// Likert — simpan ke answer_value sebagai string skala '1'–'5'
const likertValue = computed({
  get: () => props.modelValue?.answer_value ?? null,
  set: (v) => emitValue('answer_value', v),
})

const likertScale = [1, 2, 3, 4, 5]
const likertLabels = {
  1: 'Sangat Tidak Setuju',
  3: 'Netral',
  5: 'Sangat Setuju',
}

// Rating bintang — simpan ke answer_value
const ratingValue = computed({
  get: () => Number(props.modelValue?.answer_value ?? 0),
  set: (v) => emitValue('answer_value', String(v)),
})
const hoverRating = ref(0)

function setRating(val) {
  ratingValue.value = val
}

// File upload — simpan nama file ke answer_text
const uploadedFileName = ref(
  props.modelValue?.answer_text ?? null
)

function handleFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  uploadedFileName.value = file.name
  emitValue('answer_text', file.name)
}

// ---------------------------------------------------------------------------
// Validasi display
// ---------------------------------------------------------------------------
const isRequired  = computed(() => !!props.question.is_required)
const hasError    = computed(() => props.showError && isRequired.value && !isAnswered.value)

const isAnswered = computed(() => {
  const mv = props.modelValue
  if (!mv) return false
  const { answer_value, answer_options, answer_text } = mv
  if (answer_options && answer_options.length > 0) return true
  if (answer_value !== null && answer_value !== undefined && answer_value !== '') return true
  if (answer_text  !== null && answer_text  !== undefined && answer_text  !== '') return true
  return false
})

// Counter teks
const textLength     = computed(() => (textValue.value    ?? '').length)
const textareaLength = computed(() => (textareaValue.value ?? '').length)
</script>

<template>
  <div
    class="question-wrap"
    :class="{ 'question-wrap--error': hasError }"
    :id="`question-${question.id}`"
  >
    <!-- Label pertanyaan -->
    <div class="question-label">
      <span class="question-text">{{ question.question_text }}</span>
      <span v-if="isRequired" class="required-mark" aria-hidden="true">*</span>
    </div>

    <!-- Helper text -->
    <p v-if="question.helper_text" class="helper-text">{{ question.helper_text }}</p>

    <!-- ================================================================== -->
    <!-- TYPE: text -->
    <!-- ================================================================== -->
    <template v-if="question.question_type === 'text'">
      <div class="input-wrap">
        <input
          v-model="textValue"
          type="text"
          class="form-input"
          :class="{ 'form-input--error': hasError }"
          :maxlength="question.max_length ?? undefined"
          :aria-required="isRequired"
          :aria-describedby="hasError ? `err-${question.id}` : undefined"
          :placeholder="question.helper_text ? '' : 'Ketik jawaban Anda'"
        />
        <span v-if="question.max_length" class="char-counter">
          {{ textLength }}/{{ question.max_length }}
        </span>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: textarea -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'textarea'">
      <div class="input-wrap">
        <textarea
          v-model="textareaValue"
          class="form-input form-textarea"
          :class="{ 'form-input--error': hasError }"
          rows="4"
          :maxlength="question.max_length ?? undefined"
          :aria-required="isRequired"
          :aria-describedby="hasError ? `err-${question.id}` : undefined"
          placeholder="Ketik jawaban Anda di sini"
        />
        <span v-if="question.max_length" class="char-counter">
          {{ textareaLength }}/{{ question.max_length }}
        </span>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: radio -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'radio'">
      <div class="options-list" role="radiogroup" :aria-required="isRequired">
        <label
          v-for="option in question.options"
          :key="option.id"
          class="option-item"
          :class="{ 'option-item--selected': radioValue == option.id }"
        >
          <input
            v-model="radioValue"
            type="radio"
            :value="option.id"
            class="option-input"
            :name="`q-${question.id}`"
          />
          <span class="option-label">{{ option.option_text }}</span>
        </label>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: checkbox -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'checkbox'">
      <div class="options-list" role="group" :aria-required="isRequired">
        <label
          v-for="option in question.options"
          :key="option.id"
          class="option-item"
          :class="{ 'option-item--selected': checkboxValue.includes(option.id) }"
        >
          <input
            type="checkbox"
            :value="option.id"
            :checked="checkboxValue.includes(option.id)"
            class="option-input"
            @change="toggleCheckbox(option.id)"
          />
          <span class="option-label">{{ option.option_text }}</span>
        </label>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: select (dropdown) -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'select'">
      <select
        v-model="selectValue"
        class="form-input form-select"
        :class="{ 'form-input--error': hasError }"
        :aria-required="isRequired"
      >
        <option value="" disabled>-- Pilih salah satu --</option>
        <option
          v-for="option in question.options"
          :key="option.id"
          :value="option.id"
        >
          {{ option.option_text }}
        </option>
      </select>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: likert (skala 1–5 dengan label ekstrem) -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'likert'">
      <div class="likert-wrap">
        <div class="likert-scale" role="radiogroup" :aria-required="isRequired">
          <button
            v-for="val in likertScale"
            :key="val"
            type="button"
            class="likert-btn"
            :class="{ 'likert-btn--selected': String(likertValue) === String(val) }"
            @click="likertValue = String(val)"
            :aria-label="`Skala ${val}: ${likertLabels[val] ?? val}`"
            :aria-pressed="String(likertValue) === String(val)"
          >
            {{ val }}
          </button>
        </div>
        <div class="likert-labels">
          <span>Sangat Tidak Setuju</span>
          <span>Sangat Setuju</span>
        </div>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: rating (bintang 1–5) -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'rating'">
      <div class="rating-wrap" role="radiogroup" :aria-required="isRequired">
        <button
          v-for="star in 5"
          :key="star"
          type="button"
          class="rating-star"
          :class="{ 'rating-star--filled': star <= (hoverRating || ratingValue) }"
          @click="setRating(star)"
          @mouseenter="hoverRating = star"
          @mouseleave="hoverRating = 0"
          :aria-label="`${star} bintang`"
          :aria-pressed="ratingValue === star"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
          </svg>
        </button>
        <span v-if="ratingValue > 0" class="rating-label">
          {{ ratingValue }} / 5
        </span>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: date -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'date'">
      <input
        v-model="dateValue"
        type="date"
        class="form-input form-input--date"
        :class="{ 'form-input--error': hasError }"
        :aria-required="isRequired"
      />
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: number -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'number'">
      <input
        v-model="numberValue"
        type="number"
        class="form-input"
        :class="{ 'form-input--error': hasError }"
        :min="question.min_value ?? undefined"
        :max="question.max_value ?? undefined"
        :aria-required="isRequired"
        placeholder="0"
      />
      <p v-if="question.min_value != null || question.max_value != null" class="helper-text">
        <template v-if="question.min_value != null && question.max_value != null">
          Rentang: {{ question.min_value }} – {{ question.max_value }}
        </template>
        <template v-else-if="question.min_value != null">Minimum: {{ question.min_value }}</template>
        <template v-else>Maksimum: {{ question.max_value }}</template>
      </p>
    </template>

    <!-- ================================================================== -->
    <!-- TYPE: file -->
    <!-- ================================================================== -->
    <template v-else-if="question.question_type === 'file'">
      <label class="file-drop" :class="{ 'file-drop--error': hasError }">
        <input
          type="file"
          class="file-input-hidden"
          @change="handleFileChange"
          :aria-required="isRequired"
        />
        <div v-if="!uploadedFileName" class="file-placeholder">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V8m0 0-3 3m3-3 3 3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
          </svg>
          <span>Klik atau seret file ke sini</span>
          <span class="file-hint">Format: JPG, PNG, PDF — maks. 10 MB</span>
        </div>
        <div v-else class="file-uploaded">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="file-name">{{ uploadedFileName }}</span>
          <span class="file-change">Ganti file</span>
        </div>
      </label>
    </template>

    <!-- ================================================================== -->
    <!-- Fallback tipe tidak dikenali -->
    <!-- ================================================================== -->
    <template v-else>
      <p class="helper-text" style="color: #ef4444;">Tipe pertanyaan '{{ question.question_type }}' belum didukung.</p>
    </template>

    <!-- Error message -->
    <p v-if="hasError" :id="`err-${question.id}`" class="error-msg" role="alert" aria-live="polite">
      Pertanyaan ini wajib diisi.
    </p>
  </div>
</template>

<style scoped>
/* ===== Wrapper ===== */
.question-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1.25rem 1.5rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  transition: border-color 150ms ease;
}

.question-wrap--error {
  border-color: #ef4444;
  background-color: #fff5f5;
}

/* ===== Label ===== */
.question-label {
  display: flex;
  align-items: flex-start;
  gap: 0.25rem;
}

.question-text {
  font-size: 1rem;
  font-weight: 500;
  color: #0f172a;
  line-height: 1.5;
}

.required-mark {
  color: #ef4444;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 2px;
}

/* ===== Helper / error text ===== */
.helper-text {
  margin: 0;
  font-size: 0.8125rem;
  color: #64748b;
  line-height: 1.4;
}

.error-msg {
  margin: 0;
  font-size: 0.8125rem;
  color: #ef4444;
  font-weight: 500;
}

/* ===== Input base ===== */
.input-wrap {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.9375rem;
  color: #0f172a;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.form-input:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
  background: #ffffff;
}

.form-input--error {
  border-color: #ef4444;
  background-color: #fff5f5;
}

.form-input--error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%2364748b' stroke-width='1.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 18px;
  padding-right: 2.5rem;
  cursor: pointer;
}

.form-input--date {
  cursor: pointer;
}

.char-counter {
  position: absolute;
  bottom: 0.5rem;
  right: 0.75rem;
  font-size: 0.75rem;
  color: #94a3b8;
  pointer-events: none;
}

/* ===== Radio / Checkbox options ===== */
.options-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.option-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: border-color 150ms ease, background-color 150ms ease;
  user-select: none;
}

.option-item:hover {
  border-color: #0d9488;
  background-color: #f0fdf9;
}

.option-item--selected {
  border-color: #0d9488;
  background-color: #f0fdf9;
}

.option-input {
  width: 1rem;
  height: 1rem;
  accent-color: #0d9488;
  flex-shrink: 0;
  cursor: pointer;
}

.option-label {
  font-size: 0.9375rem;
  color: #1e293b;
  line-height: 1.4;
}

/* ===== Likert ===== */
.likert-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.likert-scale {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.likert-btn {
  min-width: 2.5rem;
  height: 2.5rem;
  padding: 0 0.5rem;
  border: 2px solid #cbd5e1;
  border-radius: 9999px;
  background: #f8fafc;
  color: #475569;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 150ms ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.likert-btn:hover {
  border-color: #0d9488;
  color: #0d9488;
  background: #f0fdf9;
}

.likert-btn--selected {
  border-color: #0d9488;
  background: #0d9488;
  color: #ffffff;
}

.likert-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #64748b;
  padding: 0 0.25rem;
}

/* ===== Rating bintang ===== */
.rating-wrap {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.rating-star {
  width: 2.5rem;
  height: 2.5rem;
  padding: 0.25rem;
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  transition: color 120ms ease, transform 100ms ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rating-star svg {
  width: 1.75rem;
  height: 1.75rem;
}

.rating-star:hover,
.rating-star--filled {
  color: #f59e0b; /* secondary-500 / gold */
}

.rating-star:active {
  transform: scale(0.88);
}

.rating-label {
  margin-left: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

/* ===== File upload ===== */
.file-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.5rem;
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
  background: #f8fafc;
  cursor: pointer;
  transition: border-color 150ms ease, background-color 150ms ease;
  text-align: center;
}

.file-drop:hover {
  border-color: #0d9488;
  background-color: #f0fdf9;
}

.file-drop--error {
  border-color: #ef4444;
}

.file-input-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
}

.file-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.375rem;
  color: #64748b;
}

.file-placeholder svg {
  width: 2.5rem;
  height: 2.5rem;
  color: #94a3b8;
}

.file-hint {
  font-size: 0.75rem;
  color: #94a3b8;
}

.file-uploaded {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  color: #0d9488;
}

.file-uploaded svg {
  width: 2rem;
  height: 2rem;
}

.file-name {
  font-weight: 500;
  font-size: 0.875rem;
  word-break: break-all;
}

.file-change {
  font-size: 0.75rem;
  color: #64748b;
  text-decoration: underline;
  cursor: pointer;
}
</style>
