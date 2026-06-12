<script setup>
/**
 * SurveyPage.vue — Pengisian Survei Alumni (multi-step)
 * Route: /alumni/survey
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 4B
 */
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-800">Pengisian Survei</h1>
      <p class="text-sm text-gray-500 mt-1">Isi survei tracer study untuk membantu pengembangan kurikulum</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-base font-medium text-gray-800 mb-2">Belum ada survei aktif</h3>
      <p class="text-sm text-gray-500 max-w-sm mx-auto">
        Saat ini belum ada survei yang tersedia. Administrator akan mengirimkan undangan survei melalui WhatsApp atau Email.
      </p>
      <RouterLink
        :to="{ name: 'alumni.dashboard' }"
        class="inline-flex items-center gap-2 mt-6 text-sm text-emerald-600 hover:text-emerald-700 font-medium"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Kembali ke Beranda
      </RouterLink>
    </div>
  </div>
</template>
