<script setup>
const props = defineProps({
  /** API pagination meta: { current_page, last_page, per_page, total, from, to } */
  meta: {
    type: Object,
    required: true,
  },
  maxVisible: {
    type: Number,
    default: 5,
  },
})

const emit = defineEmits(['page-change'])

function go(page) {
  if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) return
  emit('page-change', page)
}

/** Build visible page range with ellipsis markers */
function pages() {
  const { current_page: cur, last_page: last } = props.meta
  if (last <= props.maxVisible) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }

  const half = Math.floor(props.maxVisible / 2)
  let start = Math.max(2, cur - half)
  let end = Math.min(last - 1, cur + half)

  if (cur - half < 2) end = Math.min(last - 1, props.maxVisible - 1)
  if (cur + half > last - 1) start = Math.max(2, last - props.maxVisible + 2)

  const range = []
  range.push(1)
  if (start > 2) range.push('...')
  for (let i = start; i <= end; i++) range.push(i)
  if (end < last - 1) range.push('...')
  range.push(last)
  return range
}
</script>

<template>
  <div class="flex items-center justify-between gap-4 text-sm">
    <!-- Info -->
    <p class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
      <template v-if="meta.total">
        Menampilkan
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ meta.from }}</span>–<span class="font-medium text-gray-700 dark:text-gray-300">{{ meta.to }}</span>
        dari
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ meta.total }}</span>
      </template>
      <template v-else>Tidak ada data</template>
    </p>

    <!-- Page buttons -->
    <nav aria-label="Pagination" class="flex items-center gap-1">
      <!-- Prev -->
      <button
        type="button"
        :disabled="meta.current_page === 1"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        aria-label="Halaman sebelumnya"
        @click="go(meta.current_page - 1)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <template v-for="page in pages()" :key="page">
        <!-- Ellipsis -->
        <span
          v-if="page === '...'"
          class="inline-flex h-8 w-8 items-center justify-center text-gray-400"
        >…</span>

        <!-- Page number -->
        <button
          v-else
          type="button"
          :aria-current="page === meta.current_page ? 'page' : undefined"
          :class="[
            'inline-flex h-8 w-8 items-center justify-center rounded-md border text-sm font-medium transition-colors',
            page === meta.current_page
              ? 'border-teal-600 bg-teal-600 text-white cursor-default'
              : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
          ]"
          @click="go(page)"
        >
          {{ page }}
        </button>
      </template>

      <!-- Next -->
      <button
        type="button"
        :disabled="meta.current_page === meta.last_page"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        aria-label="Halaman berikutnya"
        @click="go(meta.current_page + 1)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </nav>
  </div>
</template>
