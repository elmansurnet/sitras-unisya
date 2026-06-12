<script setup>
/**
 * Pagination.vue — Navigasi halaman
 * Props:
 *   meta : { current_page, last_page, total, from, to, per_page }
 * Emits:
 *   change(page: number)
 */
import { computed } from 'vue'

const props = defineProps({
  meta: {
    type: Object,
    required: true,
    // { current_page, last_page, total, from, to }
  },
})

const emit = defineEmits(['change'])

const pages = computed(() => {
  const { current_page: cur, last_page: last } = props.meta
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)

  const items = []
  items.push(1)

  if (cur > 3) items.push('...')

  for (let i = Math.max(2, cur - 1); i <= Math.min(last - 1, cur + 1); i++) {
    items.push(i)
  }

  if (cur < last - 2) items.push('...')

  items.push(last)
  return items
})

function go(page) {
  if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) return
  emit('change', page)
}
</script>

<template>
  <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <!-- Info -->
    <p class="text-sm text-gray-500">
      <template v-if="meta.total > 0">
        Menampilkan
        <span class="font-medium text-gray-700">{{ meta.from }}</span>–<span class="font-medium text-gray-700">{{ meta.to }}</span>
        dari
        <span class="font-medium text-gray-700">{{ meta.total }}</span>
        data
      </template>
      <template v-else>Tidak ada data</template>
    </p>

    <!-- Page buttons -->
    <div class="inline-flex items-center gap-1">
      <!-- Prev -->
      <button
        type="button"
        :disabled="meta.current_page === 1"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        @click="go(meta.current_page - 1)"
        aria-label="Halaman sebelumnya"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>

      <!-- Page numbers -->
      <template v-for="(p, i) in pages" :key="i">
        <span
          v-if="p === '...'"
          class="inline-flex h-8 w-8 items-center justify-center text-sm text-gray-400"
        >…</span>
        <button
          v-else
          type="button"
          :class="[
            'inline-flex h-8 w-8 items-center justify-center rounded-md border text-sm font-medium transition-colors',
            p === meta.current_page
              ? 'border-primary-600 bg-primary-600 text-white'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
          ]"
          @click="go(p)"
          :aria-current="p === meta.current_page ? 'page' : undefined"
        >
          {{ p }}
        </button>
      </template>

      <!-- Next -->
      <button
        type="button"
        :disabled="meta.current_page === meta.last_page"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        @click="go(meta.current_page + 1)"
        aria-label="Halaman berikutnya"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>
  </div>
</template>
