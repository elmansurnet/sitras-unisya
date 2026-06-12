<script setup>
/**
 * views/alumni/SurveyPage.vue
 * Halaman daftar survei tersedia untuk alumni yang sudah login.
 * Route: alumni.survey — /alumni/survey
 * Layout: AlumniLayout (sudah wrap di router)
 * Sesuai 06_UI_UX.md §8 & 05_API.md GET /alumni/survey
 */
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'

const router = useRouter()
const surveyStore = useSurveyStore()

onMounted(() => {
  surveyStore.fetchAvailableSurveys()
})

const surveys = computed(() => surveyStore.availableSurveys)
const loading = computed(() => surveyStore.loading)
const error   = computed(() => surveyStore.error)

function statusLabel(status) {
  const map = {
    not_started: 'Belum Dikerjakan',
    in_progress:  'Sedang Diisi',
    submitted:    'Sudah Dikirim',
  }
  return map[status] ?? status
}

function statusClass(status) {
  const map = {
    not_started: 'bg-amber-100 text-amber-800',
    in_progress:  'bg-blue-100 text-blue-800',
    submitted:    'bg-green-100 text-green-800',
  }
  return map[status] ?? 'bg-gray-100 text-gray-700'
}

function goFill(id) {
  router.push({ name: 'alumni.survey.fill', params: { id } })
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Survei Tersedia</h1>
      <p class="mt-1 text-sm text-gray-500">
        Daftar survei tracer study yang perlu Anda isi.
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <div
        v-for="i in 3"
        :key="i"
        class="animate-pulse rounded-xl border border-gray-200 bg-white p-6"
      >
        <div class="h-4 w-1/3 rounded bg-gray-200 mb-3" />
        <div class="h-3 w-2/3 rounded bg-gray-100 mb-2" />
        <div class="h-3 w-1/2 rounded bg-gray-100" />
      </div>
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      class="rounded-xl border border-red-200 bg-red-50 p-6 text-center"
    >
      <p class="text-sm font-medium text-red-700">{{ error }}</p>
      <button
        class="mt-3 text-sm text-red-600 underline"
        @click="surveyStore.fetchAvailableSurveys()"
      >
        Coba lagi
      </button>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="surveys.length === 0"
      class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.5"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12h6m-3-3v6M3 12a9 9 0 1118 0 9 9 0 01-18 0z"
        />
      </svg>
      <p class="text-sm font-medium text-gray-500">Tidak ada survei aktif saat ini.</p>
      <p class="mt-1 text-xs text-gray-400">Silakan kembali lagi nanti.</p>
    </div>

    <!-- Survey list -->
    <div v-else class="space-y-4">
      <div
        v-for="survey in surveys"
        :key="survey.id"
        class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
      >
        <!-- Top: judul + status badge -->
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 class="truncate text-base font-semibold text-gray-900">
              {{ survey.title }}
            </h2>
            <p v-if="survey.description" class="mt-1 text-sm text-gray-500 line-clamp-2">
              {{ survey.description }}
            </p>
          </div>
          <span
            :class="statusClass(survey.alumni_status)"
            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
          >
            {{ statusLabel(survey.alumni_status) }}
          </span>
        </div>

        <!-- Meta info -->
        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
          <span v-if="survey.end_date">
            ⏰ Batas: {{ new Date(survey.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
          </span>
          <span v-if="survey.total_questions">
            📋 {{ survey.total_questions }} pertanyaan
          </span>
          <span v-if="survey.completion_percentage != null">
            ✅ {{ survey.completion_percentage }}% selesai
          </span>
        </div>

        <!-- Action button -->
        <div class="mt-5 flex justify-end">
          <button
            v-if="survey.alumni_status !== 'submitted'"
            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700 active:bg-teal-800 disabled:opacity-50"
            @click="goFill(survey.id)"
          >
            {{ survey.alumni_status === 'in_progress' ? 'Lanjutkan Isi' : 'Isi Survei' }}
          </button>
          <span
            v-else
            class="rounded-lg bg-green-50 px-4 py-2 text-sm font-medium text-green-700"
          >
            Survei telah dikirim
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
