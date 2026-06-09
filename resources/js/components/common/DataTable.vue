<script setup>
/**
 * DataTable.vue — Reusable sortable + selectable table
 * Task 2A.16 | Sesuai 06_UI_UX.md Design System
 */
import { ref, computed } from 'vue'

const props = defineProps({
  columns: {
    // [{ key, label, sortable?, width?, class? }]
    type: Array,
    required: true,
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
  sortBy: {
    type: String,
    default: null,
  },
  sortDir: {
    type: String,
    default: 'asc', // 'asc' | 'desc'
  },
  emptyMessage: {
    type: String,
    default: 'Tidak ada data.',
  },
  rowKey: {
    type: String,
    default: 'id',
  },
})

const emit = defineEmits(['sort', 'select', 'select-all'])

const selectedRows = ref(new Set())

const allSelected = computed(() => {
  if (!props.rows.length) return false
  return props.rows.every((r) => selectedRows.value.has(r[props.rowKey]))
})

function toggleSelectAll() {
  if (allSelected.value) {
    selectedRows.value.clear()
  } else {
    props.rows.forEach((r) => selectedRows.value.add(r[props.rowKey]))
  }
  emit('select-all', [...selectedRows.value])
}

function toggleRow(row) {
  const key = row[props.rowKey]
  if (selectedRows.value.has(key)) {
    selectedRows.value.delete(key)
  } else {
    selectedRows.value.add(key)
  }
  emit('select', [...selectedRows.value])
}

function isSelected(row) {
  return selectedRows.value.has(row[props.rowKey])
}

function handleSort(col) {
  if (!col.sortable) return
  const dir =
    props.sortBy === col.key && props.sortDir === 'asc' ? 'desc' : 'asc'
  emit('sort', { key: col.key, dir })
}

function clearSelection() {
  selectedRows.value.clear()
  emit('select', [])
}

defineExpose({ clearSelection, selectedRows })
</script>

<template>
  <div class="dt-wrapper">
    <!-- Loading overlay -->
    <div v-if="loading" class="dt-loading" role="status" aria-live="polite">
      <span class="dt-spinner" aria-hidden="true"></span>
      <span class="sr-only">Memuat data…</span>
    </div>

    <div class="dt-scroll">
      <table class="dt-table" aria-busy="loading">
        <!-- HEAD -->
        <thead>
          <tr>
            <!-- Checkbox select all -->
            <th v-if="selectable" class="dt-col-check" scope="col">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="selectedRows.size > 0 && !allSelected"
                aria-label="Pilih semua baris"
                @change="toggleSelectAll"
              />
            </th>

            <th
              v-for="col in columns"
              :key="col.key"
              :class="['dt-th', col.class, { 'dt-sortable': col.sortable }]"
              :style="col.width ? { width: col.width } : {}"
              :aria-sort="
                col.sortable && sortBy === col.key
                  ? sortDir === 'asc'
                    ? 'ascending'
                    : 'descending'
                  : undefined
              "
              scope="col"
              @click="handleSort(col)"
            >
              <span class="dt-th-inner">
                {{ col.label }}
                <span v-if="col.sortable" class="dt-sort-icon" aria-hidden="true">
                  <svg
                    v-if="sortBy !== col.key"
                    width="12" height="12" viewBox="0 0 12 12" fill="currentColor"
                  >
                    <path d="M6 2 10 7H2L6 2Z" opacity=".4"/>
                    <path d="M6 10 2 5h8L6 10Z" opacity=".4"/>
                  </svg>
                  <svg
                    v-else-if="sortDir === 'asc'"
                    width="12" height="12" viewBox="0 0 12 12" fill="currentColor"
                  >
                    <path d="M6 2 10 7H2L6 2Z"/>
                    <path d="M6 10 2 5h8L6 10Z" opacity=".3"/>
                  </svg>
                  <svg v-else width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                    <path d="M6 2 10 7H2L6 2Z" opacity=".3"/>
                    <path d="M6 10 2 5h8L6 10Z"/>
                  </svg>
                </span>
              </span>
            </th>
          </tr>
        </thead>

        <!-- BODY -->
        <tbody>
          <!-- Skeleton rows saat loading -->
          <template v-if="loading && !rows.length">
            <tr v-for="n in 8" :key="`sk-${n}`" class="dt-row-skeleton">
              <td v-if="selectable"><span class="skeleton skeleton-text" style="width:16px;height:16px;"></span></td>
              <td v-for="col in columns" :key="col.key">
                <span class="skeleton skeleton-text" :style="{ width: n % 2 === 0 ? '70%' : '50%' }"></span>
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <template v-else-if="!rows.length">
            <tr>
              <td
                :colspan="selectable ? columns.length + 1 : columns.length"
                class="dt-empty"
              >
                <slot name="empty">
                  <div class="dt-empty-inner">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                      <path d="M9 12h.01M15 12h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                    </svg>
                    <p>{{ emptyMessage }}</p>
                  </div>
                </slot>
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr
              v-for="row in rows"
              :key="row[rowKey]"
              :class="['dt-row', { 'dt-row-selected': isSelected(row) }]"
              @click="selectable ? toggleRow(row) : undefined"
            >
              <td v-if="selectable" class="dt-col-check">
                <input
                  type="checkbox"
                  :checked="isSelected(row)"
                  :aria-label="`Pilih baris ${row[rowKey]}`"
                  @click.stop
                  @change="toggleRow(row)"
                />
              </td>
              <td
                v-for="col in columns"
                :key="col.key"
                :class="['dt-td', col.tdClass]"
              >
                <!-- Slot per kolom: #col-{key}="{ row }" -->
                <slot :name="`col-${col.key}`" :row="row" :value="row[col.key]">
                  {{ row[col.key] ?? '—' }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.dt-wrapper   { position: relative; }
.dt-scroll    { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.dt-table     { width: 100%; border-collapse: collapse; font-size: var(--text-sm); }
.dt-th        { padding: var(--space-3) var(--space-4); text-align: left; font-weight: 600;
                color: var(--color-text-muted); background: var(--color-surface-offset);
                border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.dt-th.dt-sortable { cursor: pointer; user-select: none; }
.dt-th.dt-sortable:hover { color: var(--color-text); }
.dt-th-inner  { display: flex; align-items: center; gap: var(--space-1); }
.dt-sort-icon { display: flex; flex-shrink: 0; color: var(--color-text-faint); }
.dt-td        { padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--color-divider);
                color: var(--color-text); vertical-align: middle; }
.dt-row:last-child .dt-td { border-bottom: none; }
.dt-row:hover .dt-td      { background: var(--color-surface-offset); }
.dt-row-selected .dt-td   { background: var(--color-primary-highlight); }
.dt-col-check { width: 44px; padding-inline: var(--space-4); }
.dt-empty     { padding: var(--space-16) var(--space-4); text-align: center; }
.dt-empty-inner { display:flex; flex-direction:column; align-items:center; gap:var(--space-3);
                  color:var(--color-text-muted); }
.dt-empty-inner p { margin:0; font-size:var(--text-sm); }

/* Loading */
.dt-loading {
  position: absolute; inset: 0; background: oklch(from var(--color-bg) l c h / 0.7);
  display: flex; align-items: center; justify-content: center; z-index: 10;
  border-radius: var(--radius-md);
}
.dt-spinner {
  width: 28px; height: 28px; border: 3px solid var(--color-border);
  border-top-color: var(--color-primary); border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Skeleton */
.skeleton {
  display: inline-block; height: 0.85em; border-radius: var(--radius-sm);
  background: linear-gradient(
    90deg,
    var(--color-surface-offset) 25%,
    var(--color-surface-dynamic) 50%,
    var(--color-surface-offset) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.4s ease-in-out infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }
.skeleton-text { display: block; }

@media (prefers-reduced-motion: reduce) {
  .dt-spinner, .skeleton { animation: none; }
}
</style>
