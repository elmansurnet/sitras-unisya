<script setup>
/**
 * pages/alumni/SurveyPage.vue — Halaman Pengisian Survei Alumni
 *
 * Flow:
 *   1. Mount → fetchSurvey('alumni')
 *   2. Jika status === 'selesai'  → redirect ke alumni.survey.done
 *   3. Jika questionnaire null (belum ada undangan) → tampilkan empty state
 *   4. Render per seksi (is_paginated) dengan SurveyProgressBar + QuestionPreview
 *   5. Navigasi Sebelumnya / Selanjutnya / Simpan Draft / Kirim Survei
 *   6. Kirim → modal konfirmasi → submit() → redirect ke alumni.survey.done
 *
 * Route  : alumni.survey  (06_UI_UX.md §8)
 * Store  : useSurveyStore
 * Layout : AlumniLayout
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import SurveyProgressBar from '@/components/survey/SurveyProgressBar.vue'
import QuestionPreview from '@/components/survey/QuestionPreview.vue'

const router = useRouter()
const store  = useSurveyStore()

// ---------------------------------------------------------------------------
// Local UI state
// ---------------------------------------------------------------------------
const showConfirmModal  = ref(false)
const showErrorSummary  = ref(false)  // tampilkan error semua field jika submit gagal validasi
const savingDraft       = ref(false)  // indikator manual save draft
const draftSavedMsg     = ref(false)  // feedback "Draft tersimpan"

// ---------------------------------------------------------------------------
// Computed helpers
// ---------------------------------------------------------------------------
const isSectionPaginated = computed(() =>
  store.questionnaire?.is_paginated !== false
)

/** Daftar pertanyaan yang ditampilkan — semua jika tidak paginated, seksi aktif jika paginated */
const visibleQuestions = computed(() => {
  if (!store.questionnaire) return []
  if (!isSectionPaginated.value) {
    // Tampilkan semua pertanyaan dari semua seksi
    return store.sections.flatMap((s) =>
      [...(s.questions ?? [])].sort((a, b) => a.order_number - b.order_number)
    )
  }
  return store.currentSectionQuestions
})

/** Judul seksi aktif */
const currentSectionTitle = computed(() => store.currentSection?.title ?? '')

/** Judul semua seksi untuk step indicator */
const sectionTitles = computed(() =>
  store.sections.map((s) => s.title ?? '')
)

/** Persentase completion dari store */
const percentage = computed(() => Math.round(store.completion))

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  await loadSurvey()
})

onUnmounted(() => {
  // Flush pending debounce sebelum navigasi
  store.saveDraft('alumni').catch(() => {})
})

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------
async function loadSurvey() {
  try {
    await store.fetchSurvey('alumni')
    // Jika sudah selesai, redirect langsung
    if (store.isCompleted) {
      router.replace({ name: 'alumni.survey.done' })
    }
  } catch {
    // error ditangani di template via store.error
  }
}

function handleAnswerUpdate(questionId, value) {
  // value adalah objek { answer_value, answer_options, answer_text }
  const { answer_value, answer_options, answer_text } = value
  // Update satu per satu field yang berubah
  if (answer_options !== null) {
    store.setAnswer(questionId, 'answer_options', answer_options, 'alumni')
  } else if (answer_text !== undefined && answer_text !== null) {
    store.setAnswer(questionId, 'answer_text', answer_text, 'alumni')
  } else {
    store.setAnswer(questionId, 'answer_value', answer_value, 'alumni')
  }
}

async function handleManualSaveDraft() {
  savingDraft.value = true
  try {
    await store.saveDraft('alumni')
    draftSavedMsg.value = true
    setTimeout(() => { draftSavedMsg.value = false }, 3000)
  } finally {
    savingDraft.value = false
  }
}

function handleNext() {
  if (!isSectionPaginated.value) return
  showErrorSummary.value = false
  store.nextSection()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function handlePrev() {
  if (!isSectionPaginated.value) return
  showErrorSummary.value = false
  store.prevSection()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function openConfirmModal() {
  // Validasi semua required sebelum buka modal
  if (!store.canSubmit) {
    showErrorSummary.value = true
    // Jika paginated, arahkan ke seksi yang belum selesai
    if (isSectionPaginated.value) {
      scrollToFirstError()
    }
    return
  }
  showConfirmModal.value = true
}

function scrollToFirstError() {
  // Cari seksi pertama yang memiliki required question belum terisi
  for (let i = 0; i < store.sections.length; i++) {
    const section = store.sections[i]
    const hasEmpty = (section.questions ?? []).some(
      (q) => q.is_required && !isAnswered(q.id)
    )
    if (hasEmpty) {
      store.goToSection(i)
      setTimeout(() => {
        const firstErr = document.querySelector('.question-wrap--error')
        firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }, 100)
      break
    }
  }
}

function isAnswered(questionId) {
  const ans = store.answers[questionId]
  if (!ans) return false
  const { answer_value, answer_options, answer_text } = ans
  if (answer_options && answer_options.length > 0) return true
  if (answer_value !== null && answer_value !== undefined && answer_value !== '') return true
  if (answer_text  !== null && answer_text  !== undefined && answer_text  !== '') return true
  return false
}

async function confirmSubmit() {
  showConfirmModal.value = false
  try {
    await store.submit('alumni')
    router.push({ name: 'alumni.survey.done' })
  } catch {
    // error ditampilkan di bawah modal melalui store.error
  }
}
</script>

<template>
  <div class="survey-page">
    <!-- ================================================================ -->
    <!-- SKELETON LOADING -->
    <!-- ================================================================ -->
    <template v-if="store.loading">
      <div class="skeleton-wrap" aria-busy="true" aria-label="Memuat survei...">
        <div class="skeleton skeleton-heading"></div>
        <div class="skeleton skeleton-bar"></div>
        <div class="skeleton skeleton-card" v-for="n in 3" :key="n"></div>
      </div>
    </template>

    <!-- ================================================================ -->
    <!-- ERROR STATE -->
    <!-- ================================================================ -->
    <template v-else-if="store.error && !store.questionnaire">
      <div class="empty-state" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        <h2>Gagal Memuat Survei</h2>
        <p>{{ store.error?.message ?? 'Terjadi kesalahan. Coba lagi.' }}</p>
        <button class="btn btn-primary" @click="loadSurvey">Coba Lagi</button>
      </div>
    </template>

    <!-- ================================================================ -->
    <!-- EMPTY STATE — belum ada survei / undangan -->
    <!-- ================================================================ -->
    <template v-else-if="!store.questionnaire">
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <h2>Belum Ada Survei</h2>
        <p>Anda belum menerima undangan survei. Silakan hubungi admin jika ada pertanyaan.</p>
        <a href="/alumni/dashboard" class="btn btn-secondary">Kembali ke Beranda</a>
      </div>
    </template>

    <!-- ================================================================ -->
    <!-- KONTEN SURVEI -->
    <!-- ================================================================ -->
    <template v-else>
      <!-- Header survei -->
      <div class="survey-header">
        <h1 class="survey-title">{{ store.questionnaire.title }}</h1>
        <div class="survey-meta">
          <span v-if="store.questionnaire.estimated_minutes" class="meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/>
            </svg>
            {{ store.questionnaire.estimated_minutes }} menit
          </span>
          <span v-if="store.period" class="meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd"/>
            </svg>
            Batas: {{ new Date(store.period.end_date).toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) }}
          </span>
        </div>
      </div>

      <!-- Progress bar -->
      <SurveyProgressBar
        :current-section="store.currentSectionIndex"
        :total-sections="store.totalSections"
        :percentage="percentage"
        :section-titles="sectionTitles"
        class="survey-progress-bar"
      />

      <!-- Judul seksi aktif (jika paginated) -->
      <div v-if="isSectionPaginated && currentSectionTitle" class="section-heading">
        <h2>{{ currentSectionTitle }}</h2>
      </div>

      <!-- Error summary banner -->
      <div v-if="showErrorSummary" class="error-banner" role="alert" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
        </svg>
        <span>Harap lengkapi semua pertanyaan yang wajib diisi (ditandai <strong>*</strong>).</span>
      </div>

      <!-- Draft saved feedback -->
      <transition name="fade">
        <div v-if="draftSavedMsg" class="draft-saved-msg" aria-live="polite">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
          </svg>
          Draft tersimpan
        </div>
      </transition>

      <!-- Daftar pertanyaan -->
      <div class="questions-list">
        <QuestionPreview
          v-for="question in visibleQuestions"
          :key="question.id"
          :question="question"
          :model-value="store.answers[question.id] ?? { answer_value: null, answer_options: null, answer_text: null }"
          :show-error="showErrorSummary && question.is_required && !isAnswered(question.id)"
          @update:model-value="handleAnswerUpdate(question.id, $event)"
        />
      </div>

      <!-- Navigasi bawah -->
      <div class="survey-nav">
        <!-- Tombol Sebelumnya -->
        <button
          v-if="isSectionPaginated && !store.isFirstSection"
          type="button"
          class="btn btn-secondary"
          @click="handlePrev"
          :disabled="store.submitting"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd"/>
          </svg>
          Sebelumnya
        </button>
        <div v-else class="nav-spacer"></div>

        <!-- Tombol Simpan Draft -->
        <button
          type="button"
          class="btn btn-ghost"
          @click="handleManualSaveDraft"
          :disabled="savingDraft || store.submitting"
        >
          <svg v-if="!savingDraft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"/>
            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/>
          </svg>
          <svg v-else class="spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd"/>
          </svg>
          {{ savingDraft ? 'Menyimpan...' : 'Simpan Draft' }}
        </button>

        <!-- Tombol Selanjutnya / Kirim -->
        <template v-if="isSectionPaginated">
          <button
            v-if="!store.isLastSection"
            type="button"
            class="btn btn-primary"
            @click="handleNext"
            :disabled="store.submitting"
          >
            Selanjutnya
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/>
            </svg>
          </button>
          <button
            v-else
            type="button"
            class="btn btn-success"
            @click="openConfirmModal"
            :disabled="store.submitting"
          >
            <span v-if="!store.submitting">Kirim Survei</span>
            <span v-else class="btn-loading">Mengirim...</span>
          </button>
        </template>
        <template v-else>
          <!-- Mode non-paginated: tombol kirim langsung -->
          <button
            type="button"
            class="btn btn-success"
            @click="openConfirmModal"
            :disabled="store.submitting"
          >
            <span v-if="!store.submitting">Kirim Survei</span>
            <span v-else class="btn-loading">Mengirim...</span>
          </button>
        </template>
      </div>

      <!-- Error submit -->
      <p v-if="store.error && !store.loading" class="submit-error" role="alert">
        {{ store.error?.message ?? 'Gagal mengirim survei. Coba lagi.' }}
      </p>
    </template>

    <!-- ================================================================ -->
    <!-- MODAL KONFIRMASI SUBMIT -->
    <!-- ================================================================ -->
    <Teleport to="body">
      <div
        v-if="showConfirmModal"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        @click.self="showConfirmModal = false"
      >
        <div class="modal-box">
          <div class="modal-icon modal-icon--warning">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 id="modal-title" class="modal-title">Kirim Survei?</h2>
          <p class="modal-body">
            Jawaban yang telah dikirim <strong>tidak dapat diubah</strong>.
            Pastikan semua jawaban sudah benar sebelum mengirim.
          </p>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showConfirmModal = false">Periksa Lagi</button>
            <button type="button" class="btn btn-success" @click="confirmSubmit" :disabled="store.submitting">
              <span v-if="!store.submitting">Ya, Kirim Sekarang</span>
              <span v-else class="btn-loading">Mengirim...</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ===== Layout ===== */
.survey-page {
  max-width: 760px;
  margin: 0 auto;
  padding: 1.5rem 1rem 4rem;
}

/* ===== Skeleton ===== */
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.skeleton-wrap { display: flex; flex-direction: column; gap: 1rem; }

.skeleton {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 0.5rem;
}

.skeleton-heading { height: 2rem; width: 60%; }
.skeleton-bar { height: 0.5rem; width: 100%; }
.skeleton-card { height: 120px; width: 100%; }

/* ===== Empty / Error state ===== */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 4rem 2rem;
  color: #64748b;
  gap: 0.75rem;
}

.empty-state svg { width: 3rem; height: 3rem; color: #94a3b8; }
.empty-state h2 { font-size: 1.25rem; font-weight: 600; color: #0f172a; margin: 0; }
.empty-state p { margin: 0; max-width: 36ch; font-size: 0.9375rem; }

/* ===== Survey header ===== */
.survey-header {
  margin-bottom: 0.5rem;
}

.survey-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.375rem;
  line-height: 1.3;
}

.survey-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #64748b;
}

.meta-item svg { width: 1rem; height: 1rem; }

/* ===== Progress bar ===== */
.survey-progress-bar {
  margin: 0.75rem 0 1rem;
}

/* ===== Section heading ===== */
.section-heading {
  padding: 0.75rem 1rem;
  background: #f0fdf9;
  border-left: 4px solid #0d9488;
  border-radius: 0 0.5rem 0.5rem 0;
  margin-bottom: 1rem;
}

.section-heading h2 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #0f766e;
}

/* ===== Error / draft banners ===== */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem 1rem;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #be123c;
  margin-bottom: 1rem;
}

.error-banner svg { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }

.draft-saved-msg {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #15803d;
  margin-bottom: 0.75rem;
}

.draft-saved-msg svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }

/* ===== Questions list ===== */
.questions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

/* ===== Navigation ===== */
.survey-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

.nav-spacer { flex: 1; }

.submit-error {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: #ef4444;
  text-align: center;
}

/* ===== Buttons ===== */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: background-color 150ms ease, opacity 150ms ease, transform 100ms ease;
  white-space: nowrap;
}

.btn:active { transform: scale(0.97); }
.btn:disabled { opacity: 0.55; cursor: not-allowed; }
.btn svg { width: 1.125rem; height: 1.125rem; }

.btn-primary  { background: #0d9488; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #0f766e; }
.btn-secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
.btn-secondary:hover:not(:disabled) { background: #f8fafc; }
.btn-ghost { background: transparent; color: #475569; border: 1px solid #e2e8f0; }
.btn-ghost:hover:not(:disabled) { background: #f8fafc; }
.btn-success { background: #16a34a; color: #fff; }
.btn-success:hover:not(:disabled) { background: #15803d; }

.btn-loading { opacity: 0.8; }

/* ===== Modal ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  animation: fadeIn 150ms ease;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-box {
  background: #fff;
  border-radius: 1rem;
  padding: 2rem 1.75rem;
  max-width: 440px;
  width: 100%;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  animation: scaleIn 150ms cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.modal-icon svg { width: 3rem; height: 3rem; }
.modal-icon--warning svg { color: #f59e0b; }

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  text-align: center;
  color: #0f172a;
  margin: 0 0 0.5rem;
}

.modal-body {
  text-align: center;
  font-size: 0.9375rem;
  color: #475569;
  margin: 0 0 1.5rem;
  line-height: 1.6;
}

.modal-actions {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* ===== Spin animation (draft saving) ===== */
.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ===== Fade transition ===== */
.fade-enter-active, .fade-leave-active { transition: opacity 300ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ===== Responsive ===== */
@media (max-width: 480px) {
  .survey-nav { justify-content: center; }
  .btn { padding: 0.5rem 1rem; font-size: 0.875rem; }
  .survey-title { font-size: 1.25rem; }
}
</style>
