<script setup>
/**
 * SurveyProgressBar.vue — Komponen Progress Bar Multi-Seksi Survei
 *
 * Props:
 *   currentSection  : number  — indeks seksi aktif (0-based)
 *   totalSections   : number  — total seksi dalam kuesioner
 *   percentage      : number  — persentase penyelesaian 0-100
 *   sectionTitles   : string[] — opsional, label tiap seksi untuk step indicator
 *
 * Digunakan di:
 *   pages/alumni/SurveyPage.vue
 *   pages/employer/SurveyPage.vue
 *
 * Spec 06_UI_UX.md §4.8 & §3.6
 */
import { computed } from 'vue'

const props = defineProps({
  currentSection: {
    type    : Number,
    required: true,
  },
  totalSections: {
    type    : Number,
    required: true,
  },
  percentage: {
    type   : Number,
    default: 0,
  },
  sectionTitles: {
    type   : Array,
    default: () => [],
  },
})

/** Tampilkan label seksi dari prop atau fallback ke 'Bagian N' */
function getSectionLabel(index) {
  return props.sectionTitles[index] ?? `Bagian ${index + 1}`
}

/** Lebar progress bar (CSS width) */
const progressWidth = computed(() => `${Math.min(100, Math.max(0, props.percentage))}%`)

/** Nomor seksi saat ini (1-based untuk display) */
const currentDisplay = computed(() => props.currentSection + 1)

/** Status tiap step: 'done' | 'active' | 'upcoming' */
function stepStatus(index) {
  if (index < props.currentSection) return 'done'
  if (index === props.currentSection) return 'active'
  return 'upcoming'
}
</script>

<template>
  <div class="survey-progress" role="progressbar" :aria-valuenow="percentage" aria-valuemin="0" aria-valuemax="100" :aria-label="`Progres survei: ${percentage}%`">
    <!-- Baris info: seksi & persentase -->
    <div class="progress-meta">
      <span class="progress-label">
        Bagian <strong>{{ currentDisplay }}</strong> dari <strong>{{ totalSections }}</strong>
      </span>
      <span class="progress-pct">{{ percentage }}% selesai</span>
    </div>

    <!-- Bar visual -->
    <div class="progress-track" aria-hidden="true">
      <div class="progress-fill" :style="{ width: progressWidth }"></div>
    </div>

    <!-- Step dots — tampilkan jika totalSections > 1 dan <= 10 -->
    <div v-if="totalSections > 1 && totalSections <= 10" class="progress-steps" aria-hidden="true">
      <button
        v-for="(_, index) in totalSections"
        :key="index"
        type="button"
        class="step-dot"
        :class="stepStatus(index)"
        :title="getSectionLabel(index)"
        tabindex="-1"
      >
        <!-- Checkmark untuk seksi selesai -->
        <svg v-if="stepStatus(index) === 'done'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="none" aria-hidden="true">
          <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <!-- Nomor untuk seksi aktif / upcoming -->
        <span v-else class="step-number">{{ index + 1 }}</span>
      </button>
    </div>

    <!-- Label seksi aktif (mobile — tampil di bawah steps) -->
    <p v-if="sectionTitles.length > 0" class="section-title-label">
      {{ getSectionLabel(currentSection) }}
    </p>
  </div>
</template>

<style scoped>
.survey-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  padding: 1rem 0;
}

/* Meta baris: seksi X dari Y + persentase */
.progress-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem; /* text-sm */
  color: #475569; /* text-gray-600 */
}

.progress-label strong {
  color: #0f172a; /* text-gray-900 */
  font-weight: 600;
}

.progress-pct {
  font-weight: 600;
  color: #0d9488; /* primary-600 */
}

/* Track */
.progress-track {
  width: 100%;
  height: 8px;
  background-color: #e2e8f0; /* gray-200 */
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #0d9488, #14b8a6); /* primary-600 → primary-500 */
  border-radius: 9999px;
  transition: width 400ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Step dots */
.progress-steps {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.25rem;
}

.step-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 9999px;
  border: none;
  cursor: default;
  transition: background-color 200ms ease, color 200ms ease;
  font-size: 0.75rem;
  font-weight: 600;
  flex-shrink: 0;
}

.step-dot svg {
  width: 12px;
  height: 12px;
}

/* Status: selesai */
.step-dot.done {
  background-color: #0d9488; /* primary-600 */
  color: #ffffff;
}

/* Status: aktif */
.step-dot.active {
  background-color: #0f172a; /* gray-900 */
  color: #ffffff;
  box-shadow: 0 0 0 3px #ccfbef; /* primary-100 ring */
}

/* Status: belum */
.step-dot.upcoming {
  background-color: #e2e8f0; /* gray-200 */
  color: #64748b; /* gray-500 */
}

.step-number {
  line-height: 1;
}

/* Label nama seksi aktif */
.section-title-label {
  margin: 0;
  font-size: 0.875rem;
  color: #475569; /* gray-600 */
  font-style: italic;
}

/* Responsif: kurangi ukuran dot di mobile kecil */
@media (max-width: 400px) {
  .step-dot {
    width: 24px;
    height: 24px;
    font-size: 0.65rem;
  }
}
</style>
