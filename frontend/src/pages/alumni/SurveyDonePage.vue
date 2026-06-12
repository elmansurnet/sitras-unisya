<script setup>
/**
 * frontend/src/pages/alumni/SurveyDonePage.vue
 * Halaman konfirmasi submit survei berhasil.
 * Route: alumni.survey.done — /alumni/survey/:id/done
 * Layout: AlumniLayout (wraps via router)
 *
 * Menampilkan:
 * - Ikon sukses animasi
 * - Judul survei & waktu submit
 * - Completion percentage
 * - Tombol kembali ke dashboard
 *
 * Sesuai 04_ARCHITECTURE.md §2, 06_UI_UX.md §8
 */
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'

const router = useRouter()
const store  = useSurveyStore()

const survey    = computed(() => store.lastSubmittedSurvey ?? store.currentSurvey)
const submittedAt = computed(() => store.lastSubmittedAt)
const completion  = computed(() => store.lastCompletionPct ?? 100)

onMounted(() => {
  // Bersihkan state pengisian survei agar data tidak tersisa
  store.clearCurrentSurvey()
})

function formatDatetime(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function goToDashboard() {
  router.push({ name: 'alumni.home' })
}

function goToSurveyList() {
  router.push({ name: 'alumni.survey' })
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-16">
    <div class="w-full max-w-md text-center">

      <!-- Success icon -->
      <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
        <svg
          class="h-10 w-10 text-green-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl font-bold text-gray-900">Survei Berhasil Dikirim!</h1>
      <p class="mt-2 text-sm text-gray-500">
        Terima kasih atas partisipasi Anda dalam survei tracer study UNISYA.
      </p>

      <!-- Survey info card -->
      <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-sm">
        <dl class="space-y-4">
          <!-- Judul survei -->
          <div v-if="survey">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Survei</dt>
            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ survey.title }}</dd>
          </div>

          <!-- Waktu submit -->
          <div v-if="submittedAt">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Dikirim pada</dt>
            <dd class="mt-1 text-sm text-gray-700">{{ formatDatetime(submittedAt) }}</dd>
          </div>

          <!-- Completion -->
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Kelengkapan jawaban</dt>
            <dd class="mt-2">
              <div class="flex items-center gap-3">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                  <div
                    class="h-full rounded-full bg-green-500 transition-all duration-700"
                    :style="{ width: `${completion}%` }"
                  />
                </div>
                <span class="shrink-0 text-sm font-semibold text-green-700">{{ completion }}%</span>
              </div>
            </dd>
          </div>
        </dl>
      </div>

      <!-- CTA buttons -->
      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <button
          class="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-teal-700 active:bg-teal-800"
          @click="goToDashboard"
        >
          Kembali ke Dashboard
        </button>
        <button
          class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="goToSurveyList"
        >
          Lihat Survei Lain
        </button>
      </div>

      <!-- Info tambahan -->
      <p class="mt-8 text-xs text-gray-400">
        Data Anda digunakan untuk meningkatkan kualitas pendidikan di Universitas Islam Syarifuddin.
      </p>
    </div>
  </div>
</template>
