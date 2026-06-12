<script setup>
/**
 * DataTable.vue — Tabel data generik
 * Props:
 *   columns   : Array<{ key, label, sortable?, align?, width? }>
 *   rows      : Array<object>
 *   loading   : Boolean
 *   selectable: Boolean — tampilkan checkbox per baris
 *   sortBy    : String  — kolom aktif sort
 *   sortDir   : 'asc'|'desc'
 * Emits:
 *   sort(key)           — klik header kolom sortable
 *   select(ids)         — array id baris yang dipilih
 *   select-all(boolean) — select/deselect semua
 */
import { ref, computed, watch } from 'vue'

const props = defineProps({
  columns:    { type: Array,   required: true },
  rows:       { type: Array,   default: () => [] },
  loading:    { type: Boolean, default: false },
  selectable: { type: Boolean, default: false },
  sortBy:     { type: String,  default: '' },
  sortDir:    { type: String,  default: 'asc' },
  rowKey:     { type: String,  default: 'id' },
  emptyLabel: { type: String,  default: 'Tidak ada data.' },
})

const emit = defineEmits(['sort', 'select', 'select-all'])

const selected = ref(new Set())

const allSelected = computed(() =>
  props.rows.length > 0 && props.rows.every(r => selected.value.has(r[props.rowKey]))
)

const someSelected = computed(() =>
  !allSelected.value && props.rows.some(r => selected.value.has(r[props.rowKey]))
)

watch(() => props.rows, () => {
  const ids = new Set(props.rows.map(r => r[props.rowKey]))
  for (const id of selected.value) {
    if (!ids.has(id)) selected.value.delete(id)
  }
  emit('select', [...selected.value])
})

function toggleRow(id) {
  if (selected.value.has(id)) selected.value.delete(id)
  else selected.value.add(id)
  emit('select', [...selected.value])
}

function toggleAll() {
  if (allSelected.value) {
    selected.value.clear()
    emit('select-all', false)
  } else {
    props.rows.forEach(r => selected.value.add(r[props.rowKey]))
    emit('select-all', true)
  }
  emit('select', [...selected.value])
}

function handleSort(col) {
  if (!col.sortable) return
  emit('sort', col.key)
}

function alignClass(align) {
  return align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left'
}
</script>

<template>
  <div class="relative w-full overflow-x-auto rounded-lg border border-gray-200">
    <!-- Loading overlay -->
    <div
      v-if="loading"
      class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm"
    >
      <svg class="h-7 w-7 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <!-- Header -->
      <thead class="bg-gray-50">
        <tr>
          <th v-if="selectable" class="w-10 px-3 py-3">
            <input
              type="checkbox"
              :checked="allSelected"
              :indeterminate="someSelected"
              class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              @change="toggleAll"
            />
          </th>

          <th
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-4 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs whitespace-nowrap',
              alignClass(col.align),
              col.sortable ? 'cursor-pointer select-none hover:text-gray-900' : '',
              col.width ? col.width : '',
            ]"
            @click="handleSort(col)"
          >
            <span class="inline-flex items-center gap-1">
              {{ col.label }}
              <template v-if="col.sortable">
                <svg
                  v-if="sortBy === col.key"
                  :class="['h-3.5 w-3.5 transition-transform', sortDir === 'desc' ? 'rotate-180' : '']"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
                <svg v-else class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                </svg>
              </template>
            </span>
          </th>
        </tr>
      </thead>

      <!-- Body -->
      <tbody class="divide-y divide-gray-100 bg-white">
        <tr v-if="!loading && rows.length === 0">
          <td
            :colspan="selectable ? columns.length + 1 : columns.length"
            class="py-16 text-center text-gray-400"
          >
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"/>
            </svg>
            <p class="text-sm font-medium">{{ emptyLabel }}</p>
          </td>
        </tr>

        <tr v-else-if="loading && rows.length === 0" v-for="n in 5" :key="'skel-' + n">
          <td v-if="selectable" class="px-3 py-3">
            <div class="h-4 w-4 animate-pulse rounded bg-gray-200"/>
          </td>
          <td v-for="col in columns" :key="col.key" class="px-4 py-3">
            <div class="h-4 animate-pulse rounded bg-gray-200" :class="col.width ?? 'w-3/4'"/>
          </td>
        </tr>

        <tr
          v-else
          v-for="row in rows"
          :key="row[rowKey]"
          :class="[
            'transition-colors hover:bg-gray-50',
            selected.has(row[rowKey]) ? 'bg-primary-50' : '',
          ]"
        >
          <td v-if="selectable" class="px-3 py-3">
            <input
              type="checkbox"
              :checked="selected.has(row[rowKey])"
              class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              @change="toggleRow(row[rowKey])"
            />
          </td>
          <td
            v-for="col in columns"
            :key="col.key"
            :class="['px-4 py-3 text-gray-700', alignClass(col.align)]"
          >
            <slot :name="'cell-' + col.key" :row="row" :value="row[col.key]">
              {{ row[col.key] ?? '—' }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
