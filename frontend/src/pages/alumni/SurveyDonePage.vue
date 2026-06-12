<script setup>
/**
 * SurveyDonePage.vue — Survei Alumni Selesai
 * Route: /alumni/survey/done
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 4B
 */
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-[60vh] flex items-center justify-center p-6">
    <div class="bg-white rounded-xl border border-gray-200 p-10 max-w-md w-full text-center">
      <!-- Success icon -->
      <div class="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>

      <h1 class="text-xl font-semibold text-gray-800 mb-2">Survei Berhasil Dikirim!</h1>
      <p class="text-sm text-gray-500 mb-6">
        Terima kasih telah mengisi survei tracer study. Jawaban Anda sangat membantu pengembangan kurikulum dan layanan universitas.
      </p>

      <RouterLink
        :to="{ name: 'alumni.dashboard' }"
        class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
      >
        Kembali ke Beranda
      </RouterLink>
    </div>
  </div>
</template>
