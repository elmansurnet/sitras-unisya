<script setup>
/**
 * SurveyFillPage.vue — Pengisian Survei oleh Alumni
 * Route: /alumni/survey/:id/fill (name: alumni.survey.fill)
 * Sesuai 06_UI_UX.md §3.6 & §8
 * API: GET /api/v1/alumni/surveys/:id, POST /api/v1/alumni/surveys/:id/submit (05_API.md §7)
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniSurveyStore } from '@/stores/alumniSurvey'
import { useToast } from '@/composables/useToast'
import SurveyProgressBar from '@/components/common/SurveyProgressBar.vue'
import QuestionRenderer from '@/components/survey/QuestionRenderer.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const surveyStore = useAlumniSurveyStore()
const toast = useToast()

const surveyId = computed(() => route.params.id)
const questionnaire = computed(() => surveyStore.current)
const loading = computed(() => surveyStore.loadingDetail)

/** Navigasi seksi --*/
const currentSectionIndex = ref(0)
const sections = computed(() => questionnaire.value?.sections ?? [])
const currentSection = computed(() => sections.value[currentSectionIndex.value] ?? null)
const isLastSection = computed(() => currentSectionIndex.value === sections.value.length - 1)
const progressPercent = computed(() => {
  if (!sections.value.length) return 0
  return Math.round(((currentSectionIndex.value + 1) / sections.value.length) * 100)
})

/** Jawaban alumni: { [question_id]: value } */
const answers = ref({})
const answerErrors = ref({})

/** Modal konfirmasi submit */
const showConfirm = ref(false)
const submitting = ref(false)
const savingDraft = ref(false)

function prevSection() {
  if (currentSectionIndex.value > 0) currentSectionIndex.value--
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function nextSection() {
  if (validateCurrentSection()) {
    currentSectionIndex.value++
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

/** Validasi pertanyaan wajib di seksi aktif */
function validateCurrentSection() {
  answerErrors.value = {}
  let valid = true
  if (!currentSection.value) return true
  currentSection.value.questions.forEach((q) => {
    if (q.is_required && (answers.value[q.id] === undefined || answers.value[q.id] === '' || answers.value[q.id] === null)) {
      answerErrors.value[q.id] = 'Pertanyaan ini wajib dijawab.'
      valid = false
    }
  })
  return valid
}

async function saveDraft() {
  savingDraft.value = true
  try {
    await surveyStore.saveDraft(surveyId.value, answers.value)
    toast.success('Draft tersimpan.')
  } catch {
    toast.error('Gagal menyimpan draft.')
  } finally {
    savingDraft.value = false
  }
}

async function submitSurvey() {
  submitting.value = true
  try {
    await surveyStore.submit(surveyId.value, answers.value)
    router.push({ name: 'alumni.home' })
    toast.success('Survei berhasil dikirim. Terima kasih!')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengirim survei.')
  } finally {
    submitting.value = false
    showConfirm.value = false
  }
}

onMounted(() => {
  surveyStore.fetchDetail(surveyId.value).then(() => {
    // Populate saved answers jika ada draft
    if (surveyStore.currentDraftAnswers) {
      answers.value = { ...surveyStore.currentDraftAnswers }
    }
  })
})
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-5">
    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div class="skeleton h-10 rounded-xl" />
      <div class="skeleton h-64 rounded-xl" />
    </div>

    <template v-else-if="questionnaire">
      <!-- Header -->
      <div>
        <h1 class="text-lg font-semibold text-[var(--color-text)]">{{ questionnaire.title }}</h1>
        <p v-if="questionnaire.description" class="text-sm text-[var(--color-text-muted)] mt-0.5">{{ questionnaire.description }}</p>
      </div>

      <!-- Progress -->
      <SurveyProgressBar
        :current-section="currentSectionIndex + 1"
        :total-sections="sections.length"
        :percentage="progressPercent"
      />

      <!-- Seksi aktif -->
      <div
        v-if="currentSection"
        class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6 space-y-6"
      >
        <div v-if="currentSection.title">
          <h2 class="text-base font-semibold text-[var(--color-text)]">{{ currentSection.title }}</h2>
          <p v-if="currentSection.description" class="text-sm text-[var(--color-text-muted)] mt-0.5">{{ currentSection.description }}</p>
        </div>

        <QuestionRenderer
          v-for="question in currentSection.questions"
          :key="question.id"
          :question="question"
          :error="answerErrors[question.id]"
          v-model="answers[question.id]"
        />
      </div>

      <!-- Navigasi -->
      <div class="flex items-center justify-between gap-3">
        <button
          v-if="currentSectionIndex > 0"
          class="h-9 px-4 inline-flex items-center gap-2 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
          @click="prevSection"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
          Sebelumnya
        </button>
        <div v-else />

        <div class="flex items-center gap-2">
          <button
            class="h-9 px-4 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
            :disabled="savingDraft"
            @click="saveDraft"
          >
            <span v-if="savingDraft">Menyimpan...</span>
            <span v-else>Simpan Draft</span>
          </button>

          <button
            v-if="!isLastSection"
            class="h-9 px-4 inline-flex items-center gap-2 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
            @click="nextSection"
          >
            Selanjutnya
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
          </button>

          <button
            v-else
            class="h-9 px-5 rounded-md bg-[var(--color-success)] text-white text-sm font-medium hover:opacity-90 transition-opacity"
            @click="showConfirm = true"
          >
            Kirim Survei
          </button>
        </div>
      </div>
    </template>

    <!-- Confirm Submit Modal -->
    <ConfirmModal
      :show="showConfirm"
      title="Kirim Survei?"
      message="Pastikan semua jawaban sudah benar. Survei yang sudah dikirim tidak dapat diubah kembali."
      confirm-label="Ya, Kirim Sekarang"
      variant="success"
      :loading="submitting"
      @confirm="submitSurvey"
      @cancel="showConfirm = false"
    />
  </div>
</template>

<style scoped>
@keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
.skeleton {
  background: linear-gradient(90deg, var(--color-surface-offset) 25%, var(--color-surface-dynamic) 50%, var(--color-surface-offset) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
</style>
