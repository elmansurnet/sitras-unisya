<script setup>
/**
 * FilterBar.vue — Bar filter generik
 * Props:
 *   loading : Boolean — disable input saat loading
 * Emits:
 *   reset   — klik tombol reset
 *   search  — klik tombol cari / enter di input
 * Slots:
 *   default — letakkan input/select filter di sini
 *   actions — tombol tambahan di kanan (misal: Import, Export)
 */
const props = defineProps({
  loading: { type: Boolean, default: false },
  hasFilters: { type: Boolean, default: false },
})

const emit = defineEmits(['reset', 'search'])
</script>

<template>
  <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end sm:flex-wrap">
    <!-- Filter slots -->
    <div class="flex flex-1 flex-wrap items-end gap-3">
      <slot />
    </div>

    <!-- Controls -->
    <div class="flex shrink-0 items-center gap-2">
      <!-- Reset button — muncul jika ada filter aktif -->
      <button
        v-if="hasFilters"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
        :disabled="loading"
        @click="emit('reset')"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Reset
      </button>

      <!-- Search button -->
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 disabled:opacity-60"
        :disabled="loading"
        @click="emit('search')"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"/>
          <path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        Cari
      </button>

      <!-- Additional action buttons -->
      <slot name="actions" />
    </div>
  </div>
</template>
