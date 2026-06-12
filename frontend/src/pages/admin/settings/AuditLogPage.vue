<script setup>
/**
 * AuditLogPage.vue — Audit Log Aktivitas Sistem (Superadmin)
 * Route: /admin/audit-logs
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 2C
 */
</script>

<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-gray-800">Audit Log</h1>
      <p class="text-sm text-gray-500 mt-1">Riwayat seluruh aktivitas perubahan data di sistem</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200">
      <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <h3 class="text-sm font-medium text-gray-700 mb-1">Belum ada log aktivitas</h3>
        <p class="text-xs text-gray-400 max-w-xs">Audit log akan diimplementasikan penuh pada Sesi 2C.</p>
      </div>
    </div>
  </div>
</template>
