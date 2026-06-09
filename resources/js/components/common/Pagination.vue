<script setup>
/**
 * Pagination.vue
 * Task 2A.18 | Sesuai 06_UI_UX.md Design System
 */
import { computed } from 'vue'

const props = defineProps({
  currentPage:  { type: Number, required: true },
  lastPage:     { type: Number, required: true },
  perPage:      { type: Number, default: 15 },
  total:        { type: Number, default: 0 },
  from:         { type: Number, default: 0 },
  to:           { type: Number, default: 0 },
  loading:      { type: Boolean, default: false },
  maxVisible:   { type: Number, default: 5 },
})

const emit = defineEmits(['page-change'])

const pages = computed(() => {
  const { currentPage: cur, lastPage: last, maxVisible: max } = props
  if (last <= max) return Array.from({ length: last }, (_, i) => i + 1)

  const half   = Math.floor(max / 2)
  let start    = Math.max(1, cur - half)
  let end      = start + max - 1

  if (end > last) {
    end   = last
    start = Math.max(1, end - max + 1)
  }

  const list = []
  if (start > 1) { list.push(1); if (start > 2) list.push('...') }
  for (let i = start; i <= end; i++) list.push(i)
  if (end < last) { if (end < last - 1) list.push('...'); list.push(last) }
  return list
})

function go(page) {
  if (
    typeof page !== 'number' ||
    page < 1 ||
    page > props.lastPage ||
    page === props.currentPage ||
    props.loading
  ) return
  emit('page-change', page)
}
</script>

<template>
  <nav
    v-if="lastPage > 1"
    class="pagination"
    aria-label="Navigasi halaman"
  >
    <!-- Info -->
    <span class="pagination-info">
      {{ from }}–{{ to }} dari {{ total }}
    </span>

    <!-- Controls -->
    <div class="pagination-controls">
      <!-- Prev -->
      <button
        type="button"
        class="pg-btn"
        :disabled="currentPage === 1 || loading"
        aria-label="Halaman sebelumnya"
        @click="go(currentPage - 1)"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>

      <!-- Pages -->
      <template v-for="p in pages" :key="typeof p === 'string' ? `dots-${p}` : p">
        <span v-if="p === '...'" class="pg-dots" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          class="pg-btn"
          :class="{ 'pg-btn--active': p === currentPage }"
          :aria-label="`Halaman ${p}`"
          :aria-current="p === currentPage ? 'page' : undefined"
          :disabled="loading"
          @click="go(p)"
        >
          {{ p }}
        </button>
      </template>

      <!-- Next -->
      <button
        type="button"
        class="pg-btn"
        :disabled="currentPage === lastPage || loading"
        aria-label="Halaman berikutnya"
        @click="go(currentPage + 1)"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
          <path d="M9 18l6-6-6-6"/>
        </svg>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-3);
  padding-top: var(--space-4);
}
.pagination-info { font-size: var(--text-sm); color: var(--color-text-muted); }
.pagination-controls { display: flex; align-items: center; gap: var(--space-1); }

.pg-btn {
  min-width: 32px; height: 32px;
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0 var(--space-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  cursor: pointer;
  transition: background var(--transition-interactive), color var(--transition-interactive),
              border-color var(--transition-interactive);
}
.pg-btn:hover:not(:disabled) {
  background: var(--color-surface-offset);
  color: var(--color-text);
  border-color: var(--color-primary);
}
.pg-btn--active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
  font-weight: 600;
  cursor: default;
}
.pg-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.pg-dots { padding: 0 var(--space-1); color: var(--color-text-faint); font-size: var(--text-sm); }
</style>
