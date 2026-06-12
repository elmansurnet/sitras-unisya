<script setup>
/**
 * EmployerIndexPage.vue — Daftar Employer (Admin)
 * Route: /admin/employers
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 2B
 */
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-semibold text-gray-800">Daftar Employer</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola data perusahaan/instansi yang mengisi survei</p>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
      <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
          </svg>
        </div>
        <h3 class="text-sm font-medium text-gray-700 mb-1">Belum ada data employer</h3>
        <p class="text-xs text-gray-400 max-w-xs">Modul Employer akan diimplementasikan penuh pada Sesi 2B.</p>
      </div>
    </div>
  </div>
</template>
