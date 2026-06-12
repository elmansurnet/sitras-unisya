<script setup>
/**
 * frontend/src/pages/employer/SurveyPage.vue
 * Halaman pengisian survei untuk employer (mitra/perusahaan).
 * Route  : employer.survey — /employer/survey
 * Layout : EmployerLayout (wraps via router)
 * Auth   : Token-based (survey_token di URL via EmployerAccessPage)
 *          Token sudah diset di axios header oleh EmployerAccessPage sebelum redirect.
 *
 * Flow:
 *   1. Mount → fetchSurvey('employer')
 *   2. Jika status === 'selesai' → tampilkan ringkasan read-only
 *   3. Isi per section, auto-save debounced 2 detik via store.setAnswer
 *   4. Simpan Draft manual → store.saveDraft('employer')
 *   5. Klik Kirim → ConfirmModal → store.submit('employer')
 *      → router.push({ name: 'employer.done' })
 *
 * Sesuai 04_ARCHITECTURE.md §2, 05_API.md §employer-survey, 06_UI_UX.md §8
 */
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import SurveyProgressBar from '@/components/survey/SurveyProgressBar.vue'
import QuestionPreview   from '@/components/survey/QuestionPreview.vue'

const router = useRouter()
const store  = useSurveyStore()

// ── State lokal ───────────────────────────────────────────────────────────────
const savingDraft      = ref(false)
const draftSavedAt     = ref(null)
const showConfirmModal = ref(false)
const submitError      = ref(null)

// ── Computed dari store ───────────────────────────────────────────────────────
const loading       = computed(() => store.loading)
const submitting    = computed(() => store.submitting)
const error         = computed(() => store.error)
const survey        = computed(() => store.questionnaire)
const period        = computed(() => store.period)
const isCompleted   = computed(() => store.isCompleted)
const completion    = computed(() => store.completion)
const sections      = computed(() => store.sections)
const currentSection       = computed(() => store.currentSection)
const currentSectionIndex  = computed(() => store.currentSectionIndex)
const isLastSection        = computed(() => store.isLastSection)
const isFirstSection       = computed(() => store.isFirstSection)
const currentSectionValid  = computed(() => store.currentSectionValid)
const answers       = computed(() => store.answers)
const response      = computed(() => store.response)

const totalQuestions = computed(() =>
  sections.value.reduce((acc, s) => acc + (s.questions?.length ?? 0), 0)
)
const answeredCount = computed(() =>
  Object.values(answers.value).filter((a) => {
    if (a.answer_options?.length) return true
    if (a.answer_value !== null && a.answer_value !== undefined && a.answer_value !== '') return true
    if (a.answer_text  !== null && a.answer_text  !== undefined && a.answer_text  !== '') return true
    return false
  }).length
)

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    await store.fetchSurvey('employer')
  } catch {
    // error sudah ada di store.error
  }
})

// ── Draft ─────────────────────────────────────────────────────────────────────
async function handleSaveDraft() {
  if (savingDraft.value) return
  savingDraft.value = true
  try {
    await store.saveDraft('employer')
    draftSavedAt.value = new Date()
  } finally {
    savingDraft.value = false
  }
}

// ── Answer ────────────────────────────────────────────────────────────────────
function handleAnswer({ questionId, field, value }) {
  store.setAnswer(questionId, field ?? 'answer_value', value, 'employer')
}

// ── Section navigation ────────────────────────────────────────────────────────
function goNext()  { store.nextSection(); scrollTop() }
function goPrev()  { store.prevSection(); scrollTop() }
function goToIdx(i){ store.goToSection(i); scrollTop() }
function scrollTop(){ window.scrollTo({ top: 0, behavior: 'smooth' }) }

// ── Submit ────────────────────────────────────────────────────────────────────
function openConfirmModal() {
  submitError.value = null
  showConfirmModal.value = true
}

async function confirmSubmit() {
  submitError.value = null
  try {
    await store.submit('employer')
    showConfirmModal.value = false
    router.push({ name: 'employer.done' })
  } catch {
    submitError.value = store.error?.message ?? 'Gagal mengirim survei. Silakan coba lagi.'
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
function formatTime(d) {
  if (!d) return ''
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- ──────────── LOADING ─────────────────────────────────── -->
    <div v-if="loading && !survey" class="flex min-h-[60vh] items-center justify-center">
      <div class="text-center">
        <svg class="mx-auto mb-4 h-10 w-10 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
        <p class="text-sm text-gray-500">Memuat survei…</p>
      </div>
    </div>

    <!-- ──────────── ERROR (token tidak valid / expired) ────── -->
    <div v-else-if="error && !survey" class="flex min-h-[60vh] items-center justify-center px-4">
      <div class="w-full max-w-md rounded-xl border border-red-200 bg-red-50 p-8 text-center">
        <svg class="mx-auto mb-4 h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <h2 class="mb-2 text-base font-semibold text-red-800">Survei tidak tersedia</h2>
        <p class="text-sm text-red-600">
          {{ typeof error === 'object' ? error.message : error }}
        </p>
        <p class="mt-3 text-xs text-red-400">
          Hubungi admin SITRAS UNISYA jika merasa ada kesalahan.
        </p>
      </div>
    </div>

    <!-- ──────────── SUDAH SUBMIT (read-only) ────────────────── -->
    <div v-else-if="survey && isCompleted" class="mx-auto max-w-xl px-4 py-16 text-center">
      <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
        <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h1 class="text-xl font-bold text-gray-900">Survei Sudah Dikirim</h1>
      <p class="mt-2 text-sm text-gray-500">Terima kasih atas partisipasi Anda.</p>

      <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-sm">
        <dl class="space-y-4">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Survei</dt>
            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ survey.title }}</dd>
          </div>
          <div v-if="response?.submitted_at">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Dikirim pada</dt>
            <dd class="mt-1 text-sm text-gray-700">
              {{ new Date(response.submitted_at).toLocaleString('id-ID', {
                  day: 'numeric', month: 'long', year: 'numeric',
                  hour: '2-digit', minute: '2-digit'
              }) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Kelengkapan</dt>
            <dd class="mt-2">
              <div class="flex items-center gap-3">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full bg-green-500 transition-all" :style="{ width: `${completion}%` }" />
                </div>
                <span class="shrink-0 text-sm font-semibold text-green-700">{{ completion }}%</span>
              </div>
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- ──────────── FORM SURVEI ─────────────────────────────── -->
    <template v-else-if="survey">

      <!-- Sticky header -->
      <header class="sticky top-0 z-20 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-3xl px-4 py-3">
          <!-- Title row -->
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h1 class="truncate text-sm font-semibold text-gray-900 sm:text-base">{{ survey.title }}</h1>
              <p v-if="period" class="mt-0.5 text-xs text-gray-400">
                Periode: {{ period.name }}
                <span v-if="period.end_date"> · Batas: {{ formatDate(period.end_date) }}</span>
              </p>
            </div>
            <!-- Auto-save indicator -->
            <div class="shrink-0 text-right text-xs text-gray-400">
              <span v-if="savingDraft">Menyimpan…</span>
              <span v-else-if="draftSavedAt">Tersimpan {{ formatTime(draftSavedAt) }}</span>
            </div>
          </div>

          <!-- Section label -->
          <p v-if="currentSection" class="mt-1 text-xs text-gray-400">
            Bagian {{ currentSectionIndex + 1 }} dari {{ sections.length }}: {{ currentSection.title }}
          </p>

          <!-- Progress -->
          <div class="mt-3">
            <SurveyProgressBar
              :percentage="completion"
              :answered="answeredCount"
              :total="totalQuestions"
            />
          </div>
        </div>
      </header>

      <!-- Body -->
      <main class="mx-auto max-w-3xl px-4 py-8">

        <!-- Section description -->
        <div
          v-if="currentSection?.description"
          class="mb-6 rounded-lg bg-teal-50 px-4 py-3 text-sm text-teal-800"
        >
          {{ currentSection.description }}
        </div>

        <!-- Questions -->
        <div v-if="currentSection" class="space-y-6">
          <QuestionPreview
            v-for="(q, idx) in currentSection.questions"
            :key="q.id"
            :question="q"
            :index="idx + 1"
            :model-value="answers[q.id]"
            @update:model-value="handleAnswer({ questionId: q.id, field: $event?.field, value: $event?.value ?? $event })"
          />
        </div>

        <!-- Navigation -->
        <div class="mt-10 flex items-center justify-between gap-4">
          <!-- Kiri: Prev + Simpan Draft -->
          <div class="flex gap-2">
            <button
              v-if="!isFirstSection"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100"
              @click="goPrev"
            >
              ← Sebelumnya
            </button>
            <button
              class="rounded-lg border border-teal-200 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 active:bg-teal-100 disabled:opacity-40"
              :disabled="savingDraft"
              @click="handleSaveDraft"
            >
              {{ savingDraft ? 'Menyimpan…' : 'Simpan Draft' }}
            </button>
          </div>

          <!-- Kanan: Next / Kirim -->
          <div>
            <button
              v-if="!isLastSection"
              class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 active:bg-teal-800"
              @click="goNext"
            >
              Selanjutnya →
            </button>
            <button
              v-else
              class="rounded-lg bg-green-600 px-5 py-2 text-sm font-medium text-white hover:bg-green-700 active:bg-green-800 disabled:opacity-50"
              :disabled="submitting"
              @click="openConfirmModal"
            >
              {{ submitting ? 'Mengirim…' : 'Kirim Survei' }}
            </button>
          </div>
        </div>

        <!-- Section stepper dots -->
        <div v-if="sections.length > 1" class="mt-6 flex justify-center gap-2">
          <button
            v-for="(sec, i) in sections"
            :key="i"
            :class="[
              'h-2.5 rounded-full transition-all',
              i === currentSectionIndex ? 'w-6 bg-teal-600' : 'w-2.5 bg-gray-300 hover:bg-gray-400',
            ]"
            :aria-label="`Bagian ${i + 1}: ${sec.title}`"
            @click="goToIdx(i)"
          />
        </div>

        <!-- Peringatan required questions -->
        <div
          v-if="isLastSection && !currentSectionValid"
          class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
        >
          ⚠️ Masih ada pertanyaan wajib di bagian ini yang belum dijawab.
        </div>
      </main>
    </template>

    <!-- ──────────── CONFIRM MODAL ─────────────────────────── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showConfirmModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
          @click.self="showConfirmModal = false"
        >
          <div
            class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="employer-confirm-title"
          >
            <h2 id="employer-confirm-title" class="text-base font-semibold text-gray-900">Kirim Survei?</h2>
            <p class="mt-2 text-sm text-gray-500">
              Anda telah menjawab <strong>{{ answeredCount }}</strong> dari
              <strong>{{ totalQuestions }}</strong> pertanyaan ({{ completion }}%).
              Survei yang sudah dikirim tidak dapat diubah.
            </p>
            <p v-if="submitError" class="mt-3 text-sm font-medium text-red-600">{{ submitError }}</p>
            <div class="mt-5 flex justify-end gap-3">
              <button
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                :disabled="submitting"
                @click="showConfirmModal = false"
              >
                Batal
              </button>
              <button
                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                :disabled="submitting"
                @click="confirmSubmit"
              >
                {{ submitting ? 'Mengirim…' : 'Ya, Kirim' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
