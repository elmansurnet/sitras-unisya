<script setup>
/**
 * views/alumni/SurveyFillPage.vue
 * Halaman pengisian survei alumni — multi-section, auto-save draft, submit.
 * Route: alumni.survey.fill — /alumni/survey/:id/fill
 * Layout: AlumniLayout (sudah wrap di router)
 * Sesuai 06_UI_UX.md §8, 05_API.md alumni survey endpoints
 *
 * Flow:
 *  1. onMounted → fetchSurvey(id)
 *  2. Alumni isi jawaban per section
 *  3. Auto-save draft tiap 30 detik (answers berubah)
 *  4. Tombol Simpan Draft → saveDraft manual
 *  5. Tombol Kirim → modal konfirmasi → submitSurvey
 *  6. Submit sukses → router.push 'alumni.survey' + banner sukses
 */
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import SurveyProgressBar from '@/components/survey/SurveyProgressBar.vue'
import QuestionPreview   from '@/components/survey/QuestionPreview.vue'

const route  = useRoute()
const router = useRouter()
const store  = useSurveyStore()

// ---------------------------------------------------------------------------
// State lokal
// ---------------------------------------------------------------------------
const currentSectionIndex = ref(0)
const showConfirmModal    = ref(false)
const savingDraft         = ref(false)
const submitError         = ref(null)
const draftSavedAt        = ref(null)
let   autoSaveTimer       = null

// ---------------------------------------------------------------------------
// Computed dari store
// ---------------------------------------------------------------------------
const survey    = computed(() => store.currentSurvey)
const answers   = computed(() => store.answers)
const loading   = computed(() => store.loading)
const submitting = computed(() => store.submitting)
const fetchError = computed(() => store.error)

const sections = computed(() => survey.value?.sections ?? [])

const currentSection = computed(() => sections.value[currentSectionIndex.value] ?? null)

const totalQuestions = computed(() =>
  sections.value.reduce((sum, s) => sum + (s.questions?.length ?? 0), 0)
)

const answeredCount = computed(() => Object.keys(answers.value).length)

const completionPct = computed(() =>
  totalQuestions.value > 0
    ? Math.round((answeredCount.value / totalQuestions.value) * 100)
    : 0
)

const isLastSection = computed(() => currentSectionIndex.value === sections.value.length - 1)

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  await store.fetchSurvey(route.params.id)
  startAutoSave()
})

onBeforeUnmount(() => {
  clearAutoSaveTimer()
})

// ---------------------------------------------------------------------------
// Auto-save
// ---------------------------------------------------------------------------
function startAutoSave() {
  autoSaveTimer = setInterval(async () => {
    if (Object.keys(answers.value).length > 0) {
      await triggerSaveDraft()
    }
  }, 30_000)
}

function clearAutoSaveTimer() {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
    autoSaveTimer = null
  }
}

async function triggerSaveDraft() {
  if (savingDraft.value) return
  savingDraft.value = true
  try {
    await store.saveDraft(route.params.id)
    draftSavedAt.value = new Date()
  } finally {
    savingDraft.value = false
  }
}

// ---------------------------------------------------------------------------
// Navigasi section
// ---------------------------------------------------------------------------
function prevSection() {
  if (currentSectionIndex.value > 0) currentSectionIndex.value--
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function nextSection() {
  if (!isLastSection.value) {
    currentSectionIndex.value++
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// ---------------------------------------------------------------------------
// Answer handler (dipanggil dari QuestionPreview)
// ---------------------------------------------------------------------------
function handleAnswer({ questionId, value }) {
  store.setAnswer(questionId, value)
}

// ---------------------------------------------------------------------------
// Submit
// ---------------------------------------------------------------------------
function openConfirmModal() {
  submitError.value = null
  showConfirmModal.value = true
}

async function confirmSubmit() {
  submitError.value = null
  const ok = await store.submitSurvey(route.params.id)
  if (ok) {
    showConfirmModal.value = false
    clearAutoSaveTimer()
    router.push({ name: 'alumni.survey', query: { submitted: '1' } })
  } else {
    submitError.value = store.error ?? 'Gagal mengirim survei. Coba lagi.'
  }
}

// Format waktu simpan draft
function formatTime(date) {
  if (!date) return ''
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading state -->
    <div v-if="loading" class="flex min-h-[60vh] items-center justify-center">
      <div class="text-center">
        <svg
          class="mx-auto mb-4 h-10 w-10 animate-spin text-teal-600"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
        <p class="text-sm text-gray-500">Memuat survei…</p>
      </div>
    </div>

    <!-- Error state -->
    <div
      v-else-if="fetchError && !survey"
      class="mx-auto max-w-xl px-4 py-16 text-center"
    >
      <p class="text-sm font-medium text-red-600">{{ fetchError }}</p>
      <button
        class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
        @click="store.fetchSurvey(route.params.id)"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Main content -->
    <template v-else-if="survey">
      <!-- Sticky top bar -->
      <header class="sticky top-0 z-20 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-3xl px-4 py-3">
          <!-- Judul survei -->
          <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
              <h1 class="truncate text-sm font-semibold text-gray-900 sm:text-base">
                {{ survey.title }}
              </h1>
              <p v-if="currentSection" class="mt-0.5 text-xs text-gray-400">
                Bagian {{ currentSectionIndex + 1 }} dari {{ sections.length }}: {{ currentSection.title }}
              </p>
            </div>
            <!-- Auto-save indicator -->
            <div class="shrink-0 text-right text-xs text-gray-400">
              <span v-if="savingDraft">Menyimpan…</span>
              <span v-else-if="draftSavedAt">Tersimpan {{ formatTime(draftSavedAt) }}</span>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="mt-3">
            <SurveyProgressBar
              :percentage="completionPct"
              :answered="answeredCount"
              :total="totalQuestions"
            />
          </div>
        </div>
      </header>

      <!-- Body -->
      <main class="mx-auto max-w-3xl px-4 py-8">
        <!-- Section description -->
        <div v-if="currentSection?.description" class="mb-6 rounded-lg bg-teal-50 px-4 py-3 text-sm text-teal-800">
          {{ currentSection.description }}
        </div>

        <!-- Questions -->
        <div v-if="currentSection" class="space-y-6">
          <QuestionPreview
            v-for="(question, idx) in currentSection.questions"
            :key="question.id"
            :question="question"
            :index="idx + 1"
            :model-value="answers[question.id]"
            @update:model-value="handleAnswer({ questionId: question.id, value: $event })"
          />
        </div>

        <!-- Navigation buttons -->
        <div class="mt-10 flex items-center justify-between gap-4">
          <!-- Kiri: Back + Save Draft -->
          <div class="flex gap-2">
            <button
              v-if="currentSectionIndex > 0"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100"
              @click="prevSection"
            >
              ← Sebelumnya
            </button>
            <button
              class="rounded-lg border border-teal-200 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 active:bg-teal-100 disabled:opacity-40"
              :disabled="savingDraft"
              @click="triggerSaveDraft"
            >
              {{ savingDraft ? 'Menyimpan…' : 'Simpan Draft' }}
            </button>
          </div>

          <!-- Kanan: Next atau Submit -->
          <div>
            <button
              v-if="!isLastSection"
              class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 active:bg-teal-800"
              @click="nextSection"
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
              i === currentSectionIndex
                ? 'w-6 bg-teal-600'
                : 'w-2.5 bg-gray-300 hover:bg-gray-400',
            ]"
            :aria-label="`Bagian ${i + 1}: ${sec.title}`"
            @click="currentSectionIndex = i; window.scrollTo({ top: 0, behavior: 'smooth' })"
          />
        </div>
      </main>
    </template>

    <!-- ── Confirm Submit Modal ─────────────────────────────────────────── -->
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
            aria-labelledby="modal-title"
          >
            <h2 id="modal-title" class="text-base font-semibold text-gray-900">
              Kirim Survei?
            </h2>
            <p class="mt-2 text-sm text-gray-500">
              Anda telah menjawab <strong>{{ answeredCount }}</strong> dari
              <strong>{{ totalQuestions }}</strong> pertanyaan ({{ completionPct }}%).
              Survei yang sudah dikirim tidak dapat diubah.
            </p>

            <!-- Error submit -->
            <p v-if="submitError" class="mt-3 text-sm font-medium text-red-600">
              {{ submitError }}
            </p>

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
