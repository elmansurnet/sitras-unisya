<script setup>
/**
 * frontend/src/pages/employer/DonePage.vue
 * Halaman konfirmasi survei employer berhasil dikirim.
 * Route  : employer.done — /employer/done
 * Layout : EmployerLayout (wraps via router)
 *
 * Menampilkan:
 * - Ikon sukses
 * - Judul survei
 * - Waktu submit
 * - Completion percentage
 * - Pesan terima kasih
 *
 * Tidak ada tombol navigasi ke halaman lain karena employer
 * hanya memiliki satu survei per token.
 *
 * Sesuai 04_ARCHITECTURE.md §2, 06_UI_UX.md §8
 */
import { computed, onMounted } from 'vue'
import { useSurveyStore } from '@/stores/survey'

const store = useSurveyStore()

const survey     = computed(() => store.questionnaire)
const response   = computed(() => store.response)
const completion = computed(() => store.completion)
const period     = computed(() => store.period)

onMounted(() => {
  // Bersihkan state form survei tapi pertahankan data untuk tampilan
  // Data di store sudah cukup untuk ditampilkan; tidak perlu reset
})

function formatDatetime(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-16">
    <div class="w-full max-w-md">

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
      <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Survei Berhasil Dikirim!</h1>
        <p class="mt-2 text-sm text-gray-500">
          Terima kasih atas partisipasi Anda dalam survei tracer study
          <strong class="text-gray-700">Universitas Islam Syarifuddin</strong>.
        </p>
      </div>

      <!-- Info card -->
      <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="space-y-4">

          <!-- Judul survei -->
          <div v-if="survey">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Survei</dt>
            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ survey.title }}</dd>
          </div>

          <!-- Periode -->
          <div v-if="period">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Periode</dt>
            <dd class="mt-1 text-sm text-gray-700">{{ period.name }}</dd>
          </div>

          <!-- Waktu submit -->
          <div v-if="response?.submitted_at">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Dikirim pada</dt>
            <dd class="mt-1 text-sm text-gray-700">{{ formatDatetime(response.submitted_at) }}</dd>
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

      <!-- Pesan tambahan -->
      <div class="mt-6 rounded-lg border border-teal-100 bg-teal-50 px-4 py-4 text-center">
        <p class="text-sm text-teal-700">
          Data yang Anda berikan akan digunakan untuk meningkatkan kualitas
          lulusan dan relevansi kurikulum pendidikan.
        </p>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-center text-xs text-gray-400">
        SITRAS UNISYA — Universitas Islam Syarifuddin
      </p>

    </div>
  </div>
</template>
