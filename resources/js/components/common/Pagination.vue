<script setup>
const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 15 },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['change'])

function pages() {
  const range = []
  const delta = 2
  const left = Math.max(1, props.currentPage - delta)
  const right = Math.min(props.lastPage, props.currentPage + delta)

  for (let i = left; i <= right; i++) range.push(i)

  if (left > 2) range.unshift('...')
  if (left > 1) range.unshift(1)
  if (right < props.lastPage - 1) range.push('...')
  if (right < props.lastPage) range.push(props.lastPage)

  return range
}

const from = () => (props.currentPage - 1) * props.perPage + 1
const to = () => Math.min(props.currentPage * props.perPage, props.total)
</script>

<template>
  <div v-if="lastPage > 0" class="flex items-center justify-between flex-wrap gap-3 py-3 px-1 text-sm text-[var(--color-text-muted)]">
    <span>
      Menampilkan <strong class="text-[var(--color-text)]">{{ from() }}–{{ to() }}</strong> dari
      <strong class="text-[var(--color-text)]">{{ total }}</strong> data
    </span>

    <nav class="flex items-center gap-1" aria-label="Pagination">
      <button
        class="w-8 h-8 flex items-center justify-center rounded-md border border-[var(--color-border)] hover:bg-[var(--color-surface-offset)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="currentPage <= 1 || loading"
        @click="emit('change', currentPage - 1)"
        aria-label="Halaman sebelumnya"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <template v-for="p in pages()" :key="p">
        <span v-if="p === '...'" class="w-8 h-8 flex items-center justify-center text-[var(--color-text-faint)]">…</span>
        <button
          v-else
          class="w-8 h-8 flex items-center justify-center rounded-md border text-sm font-medium transition-colors"
          :class="
            p === currentPage
              ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]'
              : 'border-[var(--color-border)] hover:bg-[var(--color-surface-offset)]'
          "
          :disabled="loading"
          @click="emit('change', p)"
          :aria-current="p === currentPage ? 'page' : undefined"
        >
          {{ p }}
        </button>
      </template>

      <button
        class="w-8 h-8 flex items-center justify-center rounded-md border border-[var(--color-border)] hover:bg-[var(--color-surface-offset)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        :disabled="currentPage >= lastPage || loading"
        @click="emit('change', currentPage + 1)"
        aria-label="Halaman berikutnya"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </nav>
  </div>
</template>
