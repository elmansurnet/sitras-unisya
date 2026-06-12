<script setup>
/**
 * DashboardPage.vue — Admin Dashboard
 * Route: /admin/dashboard
 * Sesuai 06_UI_UX.md §8 + 04_ARCHITECTURE.md
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 5B
 */
import { onMounted } from 'vue'

onMounted(() => {
  document.title = 'Dashboard — SITRAS UNISYA'
})
</script>

<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
      <p class="text-sm text-gray-500 mt-1">Ringkasan data Tracer Study Universitas Islam Syarifuddin</p>
    </div>

    <!-- Placeholder KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg border border-gray-200 p-4 animate-pulse">
        <div class="h-3 bg-gray-200 rounded w-1/2 mb-3" />
        <div class="h-6 bg-gray-200 rounded w-1/3" />
      </div>
    </div>

    <!-- Placeholder Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="h-3 bg-gray-200 rounded w-1/3 mb-4 animate-pulse" />
        <div class="h-48 bg-gray-100 rounded animate-pulse" />
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="h-3 bg-gray-200 rounded w-1/3 mb-4 animate-pulse" />
        <div class="h-48 bg-gray-100 rounded animate-pulse" />
      </div>
    </div>

    <p class="text-xs text-gray-400 text-center mt-8">Dashboard akan diimplementasikan penuh pada Sesi 5B</p>
  </div>
</template>
