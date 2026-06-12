<template>
  <div class="min-h-screen bg-gray-50">

    <!-- ═══ TOP BAR ════════════════════════════════════════════════════════════ -->
    <header
      class="sticky top-0 z-20 flex items-center justify-between gap-2 border-b border-gray-200 bg-white px-4 py-3 shadow-sm print:hidden"
    >
      <!-- Kiri: back + judul -->
      <div class="flex min-w-0 items-center gap-2">
        <button
          @click="goBack"
          class="flex shrink-0 items-center gap-1 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
          title="Kembali ke builder"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </button>

        <div class="hidden min-w-0 sm:block">
          <p class="truncate text-xs text-gray-400">Mode Preview — tidak ada data yang dikirim</p>
          <h1 class="truncate text-sm font-semibold text-gray-800">
            {{ store.current?.title ?? 'Preview Kuesioner' }}
          </h1>
        </div>
      </div>

      <!-- Tengah: dot navigator (multi-seksi, hanya step mode desktop) -->
      <nav
        v-if="visibleSections.length > 1 && viewMode === 'step'"
        aria-label="Navigasi seksi"
        class="hidden items-center gap-1 md:flex"
      >
        <button
          v-for="(sec, i) in visibleSections"
          :key="sec.id"
          @click="goToSection(i)"
          :class="[
            'h-2 rounded-full transition-all',
            currentStep === i
              ? 'w-6 bg-teal-600'
              : 'w-2 bg-gray-300 hover:bg-gray-400',
          ]"
          :aria-label="`Seksi ${i + 1}: ${sec.title}`"
          :aria-current="currentStep === i ? 'step' : undefined"
        />
      </nav>

      <!-- Kanan: kontrol -->
      <div class="flex shrink-0 items-center gap-2">
        <!-- Badge preview -->
        <span class="hidden rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 sm:inline-flex">
          Preview Admin
        </span>

        <!-- Toggle view mode -->
        <div
          v-if="visibleSections.length > 1"
          class="hidden items-center rounded-lg border border-gray-200 bg-gray-50 p-0.5 sm:flex"
          title="Mode tampilan"
          role="group"
          aria-label="Pilih mode tampilan"
        >
          <button
            @click="setViewMode('all')"
            :class="[
              'rounded px-2.5 py-1 text-xs font-medium transition-all',
              viewMode === 'all'
                ? 'bg-white text-gray-800 shadow-sm'
                : 'text-gray-500 hover:text-gray-700',
            ]"
          >Semua</button>
          <button
            @click="setViewMode('step')"
            :class="[
              'rounded px-2.5 py-1 text-xs font-medium transition-all',
              viewMode === 'step'
                ? 'bg-white text-gray-800 shadow-sm'
                : 'text-gray-500 hover:text-gray-700',
            ]"
          >Per Seksi</button>
        </div>

        <!-- Reset jawaban -->
        <button
          @click="resetAnswers"
          class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-50"
          title="Reset semua jawaban"
        >
          Reset
        </button>

        <!-- Print / PDF -->
        <button
          @click="printPage"
          class="hidden items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-50 sm:inline-flex"
          title="Cetak / simpan PDF"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
          </svg>
          Cetak
        </button>

        <!-- Buka builder -->
        <RouterLink
          :to="{ name: 'admin.questionnaires.builder', params: { id: qId } }"
          class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
          </svg>
          Edit
        </RouterLink>
      </div>
    </header>

    <!-- ═══ LOADING ══════════════════════════════════════════════════════════════ -->
    <div v-if="store.loadingDetail" class="flex min-h-[60vh] items-center justify-center">
      <svg class="h-8 w-8 animate-spin text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
      </svg>
    </div>

    <!-- ═══ ERROR ═════════════════════════════════════════════════════════════════ -->
    <div v-else-if="store.error && !store.current" class="flex min-h-[60vh] flex-col items-center justify-center gap-3 p-8">
      <p class="text-sm text-red-600">{{ store.error }}</p>
      <button @click="loadData" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">Coba lagi</button>
    </div>

    <!-- ═══ KONTEN PREVIEW ════════════════════════════════════════════════════════ -->
    <main
      v-else-if="store.current"
      class="mx-auto max-w-2xl px-4 py-8 print:px-0 print:py-4"
      id="preview-main"
    >

      <!-- Header kuesioner -->
      <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm print:rounded-none print:border-x-0 print:border-t-0 print:shadow-none">
        <div class="mb-2 flex flex-wrap items-center gap-2">
          <span
            :class="{
              'bg-amber-100 text-amber-700': store.isDraft,
              'bg-teal-100  text-teal-700':  store.isPublished,
              'bg-gray-100  text-gray-500':  store.isArchived,
            }"
            class="rounded-full px-2 py-0.5 text-xs font-medium print:hidden"
          >{{ statusLabel }}</span>
          <span class="text-xs text-gray-400 print:hidden">
            {{ store.current.type === 'alumni' ? 'Kuesioner Alumni' : 'Kuesioner Employer' }}
          </span>
        </div>
        <h2 class="text-xl font-bold text-gray-900">{{ store.current.title }}</h2>
        <p v-if="store.current.description" class="mt-2 text-sm text-gray-600">
          {{ store.current.description }}
        </p>

        <!-- Stats bar: answer counter + progress -->
        <div class="mt-4 space-y-3">
          <!-- Answer counter -->
          <div class="flex items-center justify-between text-xs text-gray-500 print:hidden">
            <span class="flex items-center gap-1.5">
              <span
                :class="[
                  'inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold',
                  answeredCount === totalQuestionCount
                    ? 'bg-teal-100 text-teal-700'
                    : 'bg-gray-100 text-gray-600',
                ]"
              >{{ answeredCount }}</span>
              dari {{ totalQuestionCount }} pertanyaan dijawab
            </span>
            <span
              v-if="requiredUnansweredCount > 0"
              class="text-amber-600"
            >{{ requiredUnansweredCount }} wajib belum diisi</span>
            <span v-else-if="answeredCount === totalQuestionCount && totalQuestionCount > 0" class="text-teal-600 font-medium">
              Semua terjawab ✓
            </span>
          </div>

          <!-- Progress bar (multi-seksi) -->
          <div v-if="visibleSections.length > 1" class="print:hidden">
            <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500">
              <span v-if="viewMode === 'step'">
                Seksi {{ currentStep + 1 }} dari {{ visibleSections.length }}
              </span>
              <span v-else>{{ visibleSections.length }} seksi</span>
              <span>{{ Math.round(completionPercent) }}% selesai</span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
              <div
                class="h-full rounded-full bg-teal-500 transition-all duration-300"
                :style="{ width: completionPercent + '%' }"
                role="progressbar"
                :aria-valuenow="Math.round(completionPercent)"
                aria-valuemin="0"
                aria-valuemax="100"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ── MODE: SEMUA SEKSI (all) ──────────────────────────────────────────── -->
      <template v-if="viewMode === 'all'">
        <div
          v-for="(sec, si) in visibleSections"
          :key="sec.id"
          class="mb-6"
        >
          <!-- Header seksi -->
          <div class="mb-3 border-b border-gray-200 pb-2 print:border-gray-400">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-600 print:text-gray-600">
              Seksi {{ si + 1 }}
            </p>
            <h3 class="text-base font-semibold text-gray-800">{{ sec.title }}</h3>
            <p v-if="sec.description" class="mt-0.5 text-sm text-gray-500">{{ sec.description }}</p>
          </div>

          <!-- Pertanyaan -->
          <div class="space-y-4">
            <template v-for="q in visibleQuestions(sec)" :key="q.id">
              <QuestionRenderer
                :question="q"
                mode="preview"
                :model-value="answers[q.id]"
                @update:model-value="answers[q.id] = $event"
              />
            </template>
            <div
              v-if="!visibleQuestions(sec).length"
              class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400"
            >
              Seksi ini belum memiliki pertanyaan
            </div>
          </div>
        </div>
      </template>

      <!-- ── MODE: PER SEKSI (step) ──────────────────────────────────────────── -->
      <template v-else>
        <div
          v-if="activeSection"
          :key="activeSection.id"
          aria-live="polite"
          aria-atomic="true"
        >
          <!-- Header seksi aktif -->
          <div class="mb-3 border-b border-gray-200 pb-2">
            <div class="flex items-center justify-between">
              <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">
                Seksi {{ currentStep + 1 }} dari {{ visibleSections.length }}
              </p>
              <!-- Mini dot navigator mobile -->
              <div class="flex items-center gap-1 md:hidden">
                <span
                  v-for="(_, i) in visibleSections"
                  :key="i"
                  :class="[
                    'h-1.5 rounded-full transition-all',
                    i === currentStep ? 'w-4 bg-teal-600' : 'w-1.5 bg-gray-300',
                  ]"
                />
              </div>
            </div>
            <h3 class="text-base font-semibold text-gray-800">{{ activeSection.title }}</h3>
            <p v-if="activeSection.description" class="mt-0.5 text-sm text-gray-500">
              {{ activeSection.description }}
            </p>
          </div>

          <!-- Pertanyaan seksi aktif -->
          <div class="space-y-4">
            <template v-for="q in visibleQuestions(activeSection)" :key="q.id">
              <QuestionRenderer
                :question="q"
                mode="preview"
                :model-value="answers[q.id]"
                @update:model-value="answers[q.id] = $event"
              />
            </template>
            <div
              v-if="!visibleQuestions(activeSection).length"
              class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400"
            >
              Seksi ini belum memiliki pertanyaan
            </div>
          </div>

          <!-- Navigasi prev / next -->
          <div class="mt-6 flex items-center justify-between gap-3">
            <button
              @click="prevSection"
              :disabled="currentStep === 0"
              class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 transition-all"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
              </svg>
              Sebelumnya
            </button>

            <!-- Step counter -->
            <span class="text-xs text-gray-400 tabular-nums">
              {{ currentStep + 1 }} / {{ visibleSections.length }}
            </span>

            <!-- Tombol Lanjut / Selesai (disabled — mode preview) -->
            <button
              v-if="currentStep < visibleSections.length - 1"
              @click="nextSection"
              class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-all"
            >
              Lanjut
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </button>
            <button
              v-else
              disabled
              class="flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-teal-300 px-4 py-2 text-sm font-medium text-white"
              title="Mode preview — jawaban tidak dikirim"
            >
              Selesai (Preview)
            </button>
          </div>
        </div>
      </template>

      <!-- ── Dummy submit area (mode all) ──────────────────────────────────── -->
      <div
        v-if="viewMode === 'all'"
        class="mt-8 rounded-xl border border-gray-200 bg-white p-5 text-center print:hidden"
      >
        <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-teal-100">
          <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          </svg>
        </div>
        <p class="text-sm font-medium text-gray-700">Ini adalah tampilan preview</p>
        <p class="mt-1 text-xs text-gray-400">Jawaban tidak disimpan. Tombol kirim tidak aktif di mode preview.</p>
        <button
          disabled
          class="mt-4 inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-teal-300 px-6 py-2 text-sm font-medium text-white"
          title="Mode preview — tidak ada pengiriman data"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
          </svg>
          Kirim Jawaban
        </button>
      </div>

      <!-- Print footer -->
      <div class="mt-8 hidden text-center text-xs text-gray-400 print:block">
        <p>Dicetak dari SITRAS UNISYA — Preview {{ store.current.title }}</p>
      </div>

    </main>

    <!-- ═══ EMPTY: tidak ada seksi ═══════════════════════════════════════════════ -->
    <div
      v-else-if="!store.loadingDetail"
      class="flex min-h-[60vh] flex-col items-center justify-center gap-4 p-8 text-center"
    >
      <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
      </svg>
      <div>
        <p class="text-sm font-medium text-gray-700">Kuesioner belum memiliki konten</p>
        <p class="mt-1 text-xs text-gray-400">Tambah seksi dan pertanyaan terlebih dahulu di builder</p>
      </div>
      <RouterLink
        :to="{ name: 'admin.questionnaires.builder', params: { id: qId } }"
        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
      >
        Buka Builder
      </RouterLink>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import { useQuestionnaireStore } from '@/stores/questionnaire'
import QuestionRenderer from '@/components/forms/QuestionRenderer.vue'

// ─── Route & Store ────────────────────────────────────────────────────────────
const route  = useRoute()
const router = useRouter()
const store  = useQuestionnaireStore()
const qId    = computed(() => Number(route.params.id))

// ─── View mode ────────────────────────────────────────────────────────────────
// 'all'  → tampilkan semua seksi sekaligus (default admin preview)
// 'step' → tampilkan satu seksi per langkah (simulasi alur responden)
const viewMode    = ref('all')
const currentStep = ref(0)

function setViewMode(mode) {
  viewMode.value    = mode
  currentStep.value = 0
}

// ─── Answers (preview-only, tidak dikirim ke API) ─────────────────────────────
const answers = reactive({})

function resetAnswers() {
  Object.keys(answers).forEach(k => delete answers[k])
}

// ─── Status label ─────────────────────────────────────────────────────────────
const statusLabel = computed(() => {
  const map = { draft: 'Draft', aktif: 'Aktif', arsip: 'Diarsipkan' }
  return map[store.current?.status] ?? store.current?.status ?? ''
})

// ─── Conditional logic: evaluasi visibilitas pertanyaan ──────────────────────
function evaluateCondition(condition) {
  const answer = answers[condition.question_id]
  const val    = condition.value

  switch (condition.operator) {
    case 'equals':       return String(answer) === String(val)
    case 'not_equals':   return String(answer) !== String(val)
    case 'contains':
      return Array.isArray(answer)
        ? answer.includes(val)
        : String(answer ?? '').includes(String(val))
    case 'not_contains':
      return Array.isArray(answer)
        ? !answer.includes(val)
        : !String(answer ?? '').includes(String(val))
    case 'greater_than': return Number(answer) > Number(val)
    case 'less_than':    return Number(answer) < Number(val)
    case 'is_empty':     return !answer || (Array.isArray(answer) && answer.length === 0)
    case 'is_not_empty': return !!answer && !(Array.isArray(answer) && answer.length === 0)
    default:             return true
  }
}

function isQuestionVisible(q) {
  const cl = q.conditional_logic
  if (!cl) return true

  const conditions = Array.isArray(cl) ? cl : (cl.conditions ?? [])
  if (!conditions.length) return true

  const logicOp = Array.isArray(cl) ? 'and' : (cl.operator ?? 'and')
  return logicOp === 'or'
    ? conditions.some(evaluateCondition)
    : conditions.every(evaluateCondition)
}

// ─── Sections & questions ─────────────────────────────────────────────────────
const visibleSections = computed(() =>
  (store.sections ?? []).filter(sec => (sec.questions ?? []).length > 0)
)

function visibleQuestions(sec) {
  return (sec.questions ?? [])
    .slice()
    .sort((a, b) => (a.order_number ?? a.order ?? 0) - (b.order_number ?? b.order ?? 0))
    .filter(isQuestionVisible)
}

// Seksi yang aktif di step mode
const activeSection = computed(() => visibleSections.value[currentStep.value] ?? null)

// ─── Answer counter ──────────────────────────────────────────────────────────
const allVisibleQuestions = computed(() =>
  visibleSections.value.flatMap(sec => visibleQuestions(sec))
)

const totalQuestionCount = computed(() => allVisibleQuestions.value.length)

const answeredCount = computed(() =>
  allVisibleQuestions.value.filter(q => {
    const a = answers[q.id]
    if (a === null || a === undefined || a === '') return false
    if (Array.isArray(a)) return a.length > 0
    return true
  }).length
)

const requiredUnansweredCount = computed(() =>
  allVisibleQuestions.value.filter(q => {
    if (!q.is_required) return false
    const a = answers[q.id]
    if (a === null || a === undefined || a === '') return true
    if (Array.isArray(a)) return a.length === 0
    return false
  }).length
)

// ─── Progress ─────────────────────────────────────────────────────────────────
const completionPercent = computed(() => {
  if (!totalQuestionCount.value) return 0
  return (answeredCount.value / totalQuestionCount.value) * 100
})

// ─── Step navigation ──────────────────────────────────────────────────────────
function goToSection(idx) {
  currentStep.value = Math.max(0, Math.min(idx, visibleSections.value.length - 1))
  scrollToMain()
}

function nextSection() {
  if (currentStep.value < visibleSections.value.length - 1) {
    currentStep.value++
    scrollToMain()
  }
}

function prevSection() {
  if (currentStep.value > 0) {
    currentStep.value--
    scrollToMain()
  }
}

function scrollToMain() {
  const el = document.getElementById('preview-main')
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

// ─── Navigasi kembali ─────────────────────────────────────────────────────────
function goBack() {
  if (window.history.length > 2) {
    router.back()
  } else {
    router.push({ name: 'admin.questionnaires.builder', params: { id: qId.value } })
  }
}

// ─── Print ────────────────────────────────────────────────────────────────────
function printPage() {
  window.print()
}

// ─── Load data ────────────────────────────────────────────────────────────────
async function loadData() {
  if (store.current?.id === qId.value && store.sections.length) return
  try {
    await store.fetchById(qId.value)
  } catch {
    // store.error sudah terisi
  }
}

onMounted(loadData)
</script>
