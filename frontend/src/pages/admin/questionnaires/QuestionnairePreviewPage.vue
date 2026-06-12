<script setup>
/**
 * QuestionnairePreviewPage.vue — Preview Kuesioner (Admin)
 * Route: /admin/questionnaires/:id/preview
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 3B
 */
import { useRoute, RouterLink } from 'vue-router'
const route = useRoute()
</script>

<template>
  <div class="p-6">
    <div class="flex items-center gap-3 mb-6">
      <RouterLink :to="{ name: 'admin.questionnaires.index' }" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali
      </RouterLink>
      <span class="text-gray-300">/</span>
      <h1 class="text-xl font-semibold text-gray-800">Preview Kuesioner #{{ route.params.id }}</h1>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <p class="text-sm text-gray-400">Preview kuesioner akan diimplementasikan penuh pada Sesi 3B.</p>
    </div>
  </div>
</template>
