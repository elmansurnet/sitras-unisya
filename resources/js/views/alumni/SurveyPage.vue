<script setup>
/**
 * SurveyPage.vue — Daftar Survei Tersedia untuk Alumni
 * Route: /alumni/survey (name: alumni.survey)
 * Sesuai 06_UI_UX.md §2.2 & §8
 * API: GET /api/v1/alumni/surveys (05_API.md §7)
 */
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniSurveyStore } from '@/stores/alumniSurvey'
import Badge from '@/components/common/Badge.vue'

const router = useRouter()
const surveyStore = useAlumniSurveyStore()

const surveys = computed(() => surveyStore.list)
const loading = computed(() => surveyStore.loading)

const statusConfig = {
  belum_disurvei: { variant: 'muted', label: 'Belum Dimulai',    cta: 'Mulai Isi' },
  terkirim:       { variant: 'info',    label: 'Undangan Dikirim', cta: 'Mulai Isi' },
  sedang_mengisi: { variant: 'warning', label: 'Sedang Diisi',    cta: 'Lanjutkan' },
  selesai:        { variant: 'success', label: 'Selesai',         cta: null },
}

function goFill(survey) {
  router.push({ name: 'alumni.survey.fill', params: { id: survey.id } })
}

onMounted(() => surveyStore.fetchList())
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-[var(--color-text)]">Survei Tersedia</h1>
      <p class="text-sm text-[var(--color-text-muted)]">Survei yang dikirimkan kepada Anda oleh institusi.</p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div class="skeleton h-28 rounded-xl" />
      <div class="skeleton h-28 rounded-xl" />
    </div>

    <!-- Empty -->
    <div v-else-if="!surveys.length" class="text-center py-16 space-y-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-[var(--color-text-faint)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
      <p class="text-sm font-medium text-[var(--color-text-muted)]">Belum ada survei yang tersedia.</p>
      <p class="text-xs text-[var(--color-text-faint)]">Survei akan muncul di sini saat institusi mengirimkan undangan.</p>
    </div>

    <!-- Survey cards -->
    <div v-else class="space-y-3">
      <div
        v-for="survey in surveys"
        :key="survey.id"
        class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-5"
      >
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <h2 class="text-sm font-semibold text-[var(--color-text)] truncate">{{ survey.title }}</h2>
              <Badge :variant="statusConfig[survey.my_status]?.variant ?? 'muted'" dot>
                {{ statusConfig[survey.my_status]?.label ?? survey.my_status }}
              </Badge>
            </div>
            <p v-if="survey.description" class="text-xs text-[var(--color-text-muted)] line-clamp-2">{{ survey.description }}</p>
            <div class="flex items-center gap-4 mt-2 flex-wrap">
              <span v-if="survey.estimated_minutes" class="text-xs text-[var(--color-text-faint)]">
                ⏱ ±{{ survey.estimated_minutes }} menit
              </span>
              <span v-if="survey.end_date" class="text-xs text-[var(--color-text-faint)]">
                Batas: {{ new Date(survey.end_date).toLocaleDateString('id-ID') }}
              </span>
            </div>

            <!-- Progress bar untuk sedang_mengisi -->
            <div v-if="survey.my_status === 'sedang_mengisi' && survey.my_progress" class="mt-3">
              <div class="flex justify-between text-xs text-[var(--color-text-muted)] mb-1">
                <span>Progress</span>
                <span>{{ survey.my_progress }}%</span>
              </div>
              <div class="h-1.5 rounded-full bg-[var(--color-surface-offset)] overflow-hidden">
                <div
                  class="h-full bg-[var(--color-warning)] rounded-full transition-all duration-500"
                  :style="{ width: survey.my_progress + '%' }"
                />
              </div>
            </div>
          </div>

          <!-- CTA button -->
          <button
            v-if="statusConfig[survey.my_status]?.cta"
            class="flex-shrink-0 h-9 px-4 rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
            @click="goFill(survey)"
          >
            {{ statusConfig[survey.my_status].cta }}
          </button>
          <span v-else class="text-xs text-[var(--color-success)] font-medium flex-shrink-0">✓ Selesai</span>
        </div>
      </div>
    </div>
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
