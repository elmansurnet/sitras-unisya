<script setup>
/**
 * NotificationLogPage.vue — Log Pengiriman Notifikasi (Admin)
 * Route: /admin/notifications/logs
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 4B
 */
</script>

<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-800">Log Notifikasi</h1>
      <p class="text-sm text-gray-500 mt-1">Riwayat pengiriman notifikasi WhatsApp dan Email</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200">
      <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
          </svg>
        </div>
        <h3 class="text-sm font-medium text-gray-700 mb-1">Belum ada log notifikasi</h3>
        <p class="text-xs text-gray-400 max-w-xs">Log notifikasi akan diimplementasikan penuh pada Sesi 4B.</p>
      </div>
    </div>
  </div>
</template>
