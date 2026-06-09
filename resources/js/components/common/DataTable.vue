<script setup>
import { computed } from 'vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    // [{ key, label, sortable?, class? }]
  },
  rows: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  selected: {
    type: Array,
    default: () => [],
  },
  sortKey: {
    type: String,
    default: null,
  },
  sortDir: {
    type: String,
    default: 'asc',
  },
  emptyMessage: {
    type: String,
    default: 'Belum ada data.',
  },
  rowKey: {
    type: String,
    default: 'id',
  },
})

const emit = defineEmits(['sort', 'select', 'select-all'])

const allSelected = computed(() =>
  props.rows.length > 0 &&
  props.rows.every((row) => props.selected.includes(row[props.rowKey]))
)

function toggleAll() {
  const ids = props.rows.map((r) => r[props.rowKey])
  emit('select-all', allSelected.value ? [] : ids)
}

function toggleRow(id) {
  const next = props.selected.includes(id)
    ? props.selected.filter((s) => s !== id)
    : [...props.selected, id]
  emit('select', next)
}

function handleSort(col) {
  if (!col.sortable) return
  const dir = props.sortKey === col.key && props.sortDir === 'asc' ? 'desc' : 'asc'
  emit('sort', { key: col.key, dir })
}
</script>

<template>
  <div class="data-table-wrapper">
    <div class="overflow-x-auto rounded-lg border border-[var(--color-border)]">
      <table class="w-full text-sm">
        <thead class="bg-[var(--color-surface-offset)] text-[var(--color-text-muted)] uppercase tracking-wide text-xs">
          <tr>
            <th v-if="selectable" class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleAll"
                class="rounded border-[var(--color-border)] accent-[var(--color-primary)]"
              />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-4 py-3 text-left font-medium whitespace-nowrap select-none',
                col.class,
                col.sortable ? 'cursor-pointer hover:text-[var(--color-text)]' : '',
              ]"
              @click="handleSort(col)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <span v-if="col.sortable" class="opacity-50">
                  <svg
                    v-if="sortKey === col.key"
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-3 h-3"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      v-if="sortDir === 'asc'"
                      d="M12 19V5M5 12l7-7 7 7"
                    />
                    <path v-else d="M12 5v14M5 12l7 7 7-7" />
                  </svg>
                  <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-3 h-3 opacity-30"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M8 9l4-4 4 4M8 15l4 4 4-4" />
                  </svg>
                </span>
              </span>
            </th>
          </tr>
        </thead>

        <!-- Loading skeleton -->
        <tbody v-if="loading">
          <tr v-for="n in 8" :key="n" class="border-t border-[var(--color-border)]">
            <td v-if="selectable" class="px-4 py-3">
              <div class="skeleton w-4 h-4 rounded" />
            </td>
            <td v-for="col in columns" :key="col.key" class="px-4 py-3">
              <div class="skeleton h-4 rounded" :style="{ width: Math.random() * 40 + 50 + '%' }" />
            </td>
          </tr>
        </tbody>

        <!-- Empty state -->
        <tbody v-else-if="rows.length === 0">
          <tr>
            <td :colspan="selectable ? columns.length + 1 : columns.length" class="text-center py-16 text-[var(--color-text-muted)]">
              <div class="flex flex-col items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-[var(--color-text-faint)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4" />
                </svg>
                <span>{{ emptyMessage }}</span>
              </div>
            </td>
          </tr>
        </tbody>

        <!-- Data rows -->
        <tbody v-else>
          <tr
            v-for="row in rows"
            :key="row[rowKey]"
            class="border-t border-[var(--color-border)] hover:bg-[var(--color-surface-offset)] transition-colors"
            :class="{ 'bg-[var(--color-primary-highlight)]': selectable && selected.includes(row[rowKey]) }"
          >
            <td v-if="selectable" class="px-4 py-3">
              <input
                type="checkbox"
                :checked="selected.includes(row[rowKey])"
                @change="toggleRow(row[rowKey])"
                class="rounded border-[var(--color-border)] accent-[var(--color-primary)]"
              />
            </td>
            <td
              v-for="col in columns"
              :key="col.key"
              :class="['px-4 py-3 text-[var(--color-text)]', col.class]"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
.skeleton {
  background: linear-gradient(
    90deg,
    var(--color-surface-offset) 25%,
    var(--color-surface-dynamic) 50%,
    var(--color-surface-offset) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
</style>
