<script setup>
/**
 * frontend/src/pages/alumni/SurveyPage.vue
 * Halaman survei alumni: list survei + mode isi survei inline (SurveyFillPanel).
 * Route: alumni.survey — /alumni/survey
 * Layout: AlumniLayout (wraps via router)
 *
 * Flow:
 *   1. Mount → fetchAvailableSurveys()
 *   2. Klik 'Isi/Lanjutkan' → mode fill (fetchSurvey(id))
 *   3. Isi per section, auto-save 30 detik, Simpan Draft manual
 *   4. Klik Kirim → ConfirmModal → submitSurvey() → router push SurveyDonePage
 *   5. Klik Batal/Kembali → kembali ke mode list
 *
 * Sesuai 04_ARCHITECTURE.md §2, 06_UI_UX.md §8, 05_API.md alumni survey
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import SurveyProgressBar from '@/components/survey/SurveyProgressBar.vue'
import QuestionPreview   from '@/components/survey/QuestionPreview.vue'

const router = useRouter()
const route  = useRoute()
const store  = useSurveyStore()

// ── Mode: 'list' | 'fill' ────────────────────────────────────────────────────
const mode           = ref('list')
const activeSurveyId = ref(null)

// ── List state ───────────────────────────────────────────────────────────────
const surveys = computed(() => store.availableSurveys)
const listLoading = computed(() => store.loading && mode.value === 'list')
const listError   = computed(() => mode.value === 'list' ? store.error : null)

// ── Fill state ───────────────────────────────────────────────────────────────
const survey         = computed(() => store.currentSurvey)
const answers        = computed(() => store.answers)
const fillLoading    = computed(() => store.loading && mode.value === 'fill')
const fillError      = computed(() => mode.value === 'fill' ? store.error : null)
const submitting     = computed(() => store.submitting)

const currentSectionIndex = ref(0)
const savingDraft         = ref(false)
const draftSavedAt        = ref(null)
const showConfirmModal    = ref(false)
const submitError         = ref(null)
let   autoSaveTimer       = null

const sections = computed(() => survey.value?.sections ?? [])
const currentSection = computed(() => sections.value[currentSectionIndex.value] ?? null)
const isLastSection  = computed(() => currentSectionIndex.value === sections.value.length - 1)

const totalQuestions = computed(() =>
  sections.value.reduce((acc, s) => acc + (s.questions?.length ?? 0), 0)
)
const answeredCount = computed(() => Object.keys(answers.value).length)
const completionPct = computed(() =>
  totalQuestions.value > 0
    ? Math.round((answeredCount.value / totalQuestions.value) * 100)
    : 0
)

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  store.fetchAvailableSurveys()
  // Cek query param ?submitted=1 dari redirect pasca submit
  if (route.query.submitted === '1') {
    // tampilkan notifikasi sukses bisa dilakukan via useToast bila tersedia
  }
})

onBeforeUnmount(() => clearAutoSave())

// ── List actions ──────────────────────────────────────────────────────────────
async function startFill(id) {
  activeSurveyId.value = id
  currentSectionIndex.value = 0
  draftSavedAt.value = null
  submitError.value  = null
  mode.value = 'fill'
  await store.fetchSurvey(id)
  startAutoSave()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function backToList() {
  clearAutoSave()
  mode.value = 'list'
  store.clearCurrentSurvey()
  activeSurveyId.value = null
}

// ── Auto-save ─────────────────────────────────────────────────────────────────
function startAutoSave() {
  autoSaveTimer = setInterval(async () => {
    if (Object.keys(answers.value).length > 0) await triggerSaveDraft()
  }, 30_000)
}

function clearAutoSave() {
  if (autoSaveTimer) { clearInterval(autoSaveTimer); autoSaveTimer = null }
}

async function triggerSaveDraft() {
  if (savingDraft.value) return
  savingDraft.value = true
  try {
    await store.saveDraft(activeSurveyId.value)
    draftSavedAt.value = new Date()
  } finally {
    savingDraft.value = false
  }
}

// ── Section navigation ────────────────────────────────────────────────────────
function prevSection() {
  if (currentSectionIndex.value > 0) { currentSectionIndex.value--; scrollTop() }
}
function nextSection() {
  if (!isLastSection.value) { currentSectionIndex.value++; scrollTop() }
}
function scrollTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) }

// ── Answer ────────────────────────────────────────────────────────────────────
function handleAnswer({ questionId, value }) {
  store.setAnswer(questionId, value)
}

// ── Submit ────────────────────────────────────────────────────────────────────
function openConfirmModal() {
  submitError.value = null
  showConfirmModal.value = true
}

async function confirmSubmit() {
  submitError.value = null
  const ok = await store.submitSurvey(activeSurveyId.value)
  if (ok) {
    showConfirmModal.value = false
    clearAutoSave()
    router.push({
      name: 'alumni.survey.done',
      params: { id: activeSurveyId.value },
    })
  } else {
    submitError.value = store.error ?? 'Gagal mengirim survei. Silakan coba lagi.'
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusLabel(status) {
  return { not_started: 'Belum Dikerjakan', in_progress: 'Sedang Diisi', submitted: 'Sudah Dikirim' }[status] ?? status
}
function statusClass(status) {
  return {
    not_started: 'bg-amber-100 text-amber-700',
    in_progress:  'bg-blue-100 text-blue-700',
    submitted:    'bg-green-100 text-green-700',
  }[status] ?? 'bg-gray-100 text-gray-600'
}
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

    <!-- ──────────── MODE: LIST ───────────────────────────────── -->
    <div v-if="mode === 'list'" class="mx-auto max-w-3xl px-4 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Survei Tersedia</h1>
        <p class="mt-1 text-sm text-gray-500">Daftar survei tracer study yang perlu Anda isi.</p>
      </div>

      <!-- Loading skeleton -->
      <div v-if="listLoading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="animate-pulse rounded-xl border border-gray-200 bg-white p-6">
          <div class="mb-3 h-4 w-1/3 rounded bg-gray-200" />
          <div class="mb-2 h-3 w-2/3 rounded bg-gray-100" />
          <div class="h-3 w-1/2 rounded bg-gray-100" />
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="listError" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center">
        <p class="text-sm font-medium text-red-700">{{ listError }}</p>
        <button class="mt-3 text-sm text-red-600 underline" @click="store.fetchAvailableSurveys()">Coba lagi</button>
      </div>

      <!-- Empty -->
      <div v-else-if="surveys.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
        <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
        </svg>
        <p class="text-sm font-medium text-gray-500">Tidak ada survei aktif saat ini.</p>
        <p class="mt-1 text-xs text-gray-400">Silakan kembali lagi nanti.</p>
      </div>

      <!-- Survey cards -->
      <div v-else class="space-y-4">
        <div
          v-for="sv in surveys"
          :key="sv.id"
          class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 class="truncate text-base font-semibold text-gray-900">{{ sv.title }}</h2>
              <p v-if="sv.description" class="mt-1 text-sm text-gray-500 line-clamp-2">{{ sv.description }}</p>
            </div>
            <span :class="statusClass(sv.alumni_status)" class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium">
              {{ statusLabel(sv.alumni_status) }}
            </span>
          </div>

          <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
            <span v-if="sv.end_date">⏰ Batas: {{ formatDate(sv.end_date) }}</span>
            <span v-if="sv.total_questions">📋 {{ sv.total_questions }} pertanyaan</span>
            <span v-if="sv.completion_percentage != null">✅ {{ sv.completion_percentage }}% selesai</span>
          </div>

          <div class="mt-5 flex justify-end">
            <button
              v-if="sv.alumni_status !== 'submitted'"
              class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700 active:bg-teal-800"
              @click="startFill(sv.id)"
            >
              {{ sv.alumni_status === 'in_progress' ? 'Lanjutkan Isi' : 'Isi Survei' }}
            </button>
            <span v-else class="rounded-lg bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
              Survei telah dikirim
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ──────────── MODE: FILL ───────────────────────────────── -->
    <template v-else-if="mode === 'fill'">

      <!-- Loading -->
      <div v-if="fillLoading" class="flex min-h-[60vh] items-center justify-center">
        <div class="text-center">
          <svg class="mx-auto mb-4 h-10 w-10 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          <p class="text-sm text-gray-500">Memuat survei…</p>
        </div>
      </div>

      <!-- Fetch error -->
      <div v-else-if="fillError && !survey" class="mx-auto max-w-xl px-4 py-16 text-center">
        <p class="text-sm font-medium text-red-600">{{ fillError }}</p>
        <button class="mt-4 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="backToList">
          ← Kembali ke daftar
        </button>
      </div>

      <!-- Survey form -->
      <template v-else-if="survey">
        <!-- Sticky header -->
        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white shadow-sm">
          <div class="mx-auto max-w-3xl px-4 py-3">
            <div class="flex items-center gap-3">
              <!-- Back button -->
              <button
                class="shrink-0 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                aria-label="Kembali ke daftar survei"
                @click="backToList"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold text-gray-900 sm:text-base">{{ survey.title }}</h1>
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
            <!-- Progress -->
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
              v-for="(q, idx) in currentSection.questions"
              :key="q.id"
              :question="q"
              :index="idx + 1"
              :model-value="answers[q.id]"
              @update:model-value="handleAnswer({ questionId: q.id, value: $event })"
            />
          </div>

          <!-- Navigation -->
          <div class="mt-10 flex items-center justify-between gap-4">
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
                i === currentSectionIndex ? 'w-6 bg-teal-600' : 'w-2.5 bg-gray-300 hover:bg-gray-400',
              ]"
              :aria-label="`Bagian ${i + 1}: ${sec.title}`"
              @click="currentSectionIndex = i; scrollTop()"
            />
          </div>
        </main>
      </template>
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
            aria-labelledby="confirm-modal-title"
          >
            <h2 id="confirm-modal-title" class="text-base font-semibold text-gray-900">Kirim Survei?</h2>
            <p class="mt-2 text-sm text-gray-500">
              Anda telah menjawab <strong>{{ answeredCount }}</strong> dari
              <strong>{{ totalQuestions }}</strong> pertanyaan ({{ completionPct }}%).
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
