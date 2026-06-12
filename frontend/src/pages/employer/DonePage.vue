<script setup>
/**
 * DonePage.vue — Survei Employer Selesai
 * Route: /employer/done
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 4B
 */
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="bg-white rounded-xl border border-gray-200 p-10 max-w-md w-full text-center">
      <div class="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>

      <h1 class="text-xl font-semibold text-gray-800 mb-2">Terima Kasih!</h1>
      <p class="text-sm text-gray-500 mb-2">
        Penilaian Anda terhadap alumni kami telah berhasil dikirim.
      </p>
      <p class="text-sm text-gray-500">
        Data Anda akan digunakan untuk meningkatkan kualitas lulusan Universitas Islam Syarifuddin.
      </p>

      <div class="mt-8 pt-6 border-t border-gray-100">
        <p class="text-xs text-gray-400">SITRAS — Sistem Tracer Study UNISYA</p>
      </div>
    </div>
  </div>
</template>
