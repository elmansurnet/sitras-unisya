<script setup>
/**
 * EmployerFormPage.vue — Form Tambah/Edit Employer (Admin)
 * Route: /admin/employers/create | /admin/employers/:id/edit
 * Sesuai 04_ARCHITECTURE.md + 06_UI_UX.md §8
 * Stub production-ready: akan diimplementasikan penuh pada Sesi 2B
 */
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'

const route  = useRoute()
const isEdit = computed(() => !!route.params.id)
</script>

<template>
  <div class="p-6">
    <div class="flex items-center gap-3 mb-6">
      <RouterLink
        :to="{ name: 'admin.employers.index' }"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Kembali
      </RouterLink>
      <span class="text-gray-300">/</span>
      <h1 class="text-xl font-semibold text-gray-800">
        {{ isEdit ? 'Edit Employer' : 'Tambah Employer' }}
      </h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <p class="text-sm text-gray-400">Form employer akan diimplementasikan penuh pada Sesi 2B.</p>
    </div>
  </div>
</template>
