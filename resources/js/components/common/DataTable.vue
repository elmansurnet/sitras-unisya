<script setup>
import { computed } from 'vue'
import Pagination from '@/components/common/Pagination.vue'

const props = defineProps({
  /** Array of column definitions: { key, label, sortable?, class?, headerClass? } */
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  /** Pagination meta from API: { current_page, per_page, total, last_page, from, to } */
  meta: {
    type: Object,
    default: null,
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
  sortBy: {
    type: String,
    default: null,
  },
  sortDir: {
    type: String,
    default: 'asc',
    validator: (v) => ['asc', 'desc'].includes(v),
  },
  emptyMessage: {
    type: String,
    default: 'Tidak ada data yang tersedia.',
  },
  rowKey: {
    type: String,
    default: 'id',
  },
})

const emit = defineEmits(['sort', 'select', 'select-all', 'page-change'])

const allSelected = computed(() => {
  if (!props.rows.length) return false
  return props.rows.every((row) => props.selected.includes(row[props.rowKey]))
})

const someSelected = computed(
  () => props.selected.length > 0 && !allSelected.value,
)

function toggleAll() {
  if (allSelected.value) {
    emit('select-all', [])
  } else {
    emit('select-all', props.rows.map((r) => r[props.rowKey]))
  }
}

function toggleRow(id) {
  const next = props.selected.includes(id)
    ? props.selected.filter((s) => s !== id)
    : [...props.selected, id]
  emit('select', next)
}

function handleSort(col) {
  if (!col.sortable) return
  const dir = props.sortBy === col.key && props.sortDir === 'asc' ? 'desc' : 'asc'
  emit('sort', { key: col.key, dir })
}

function sortIcon(col) {
  if (props.sortBy !== col.key) return null
  return props.sortDir === 'asc' ? 'up' : 'down'
}
</script>

<template>
  <div class="flex flex-col gap-0">
    <!-- Table wrapper -->
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <!-- Head -->
        <thead class="bg-gray-50 dark:bg-gray-800">
          <tr>
            <!-- Checkbox col -->
            <th v-if="selectable" class="w-10 px-4 py-3">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                @change="toggleAll"
              />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap select-none',
                col.headerClass,
                col.sortable ? 'cursor-pointer hover:text-gray-900 dark:hover:text-gray-100' : '',
              ]"
              @click="handleSort(col)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <span v-if="col.sortable" class="text-xs text-gray-400">
                  <template v-if="sortIcon(col) === 'up'">&#9650;</template>
                  <template v-else-if="sortIcon(col) === 'down'">&#9660;</template>
                  <template v-else>&#8693;</template>
                </span>
              </span>
            </th>
          </tr>
        </thead>

        <!-- Body -->
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
          <!-- Loading skeleton -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="'sk-' + n">
              <td v-if="selectable" class="px-4 py-3">
                <div class="h-4 w-4 rounded bg-gray-200 dark:bg-gray-700 animate-pulse" />
              </td>
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3"
              >
                <div class="h-4 rounded bg-gray-200 dark:bg-gray-700 animate-pulse" :style="{ width: Math.random() * 40 + 50 + '%' }" />
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <template v-else-if="!rows.length">
            <tr>
              <td
                :colspan="selectable ? columns.length + 1 : columns.length"
                class="py-16 text-center text-gray-400 dark:text-gray-600"
              >
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-10 h-10 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4" />
                  </svg>
                  <p class="text-sm">{{ emptyMessage }}</p>
                </div>
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr
              v-for="row in rows"
              :key="row[rowKey]"
              class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
            >
              <td v-if="selectable" class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="selected.includes(row[rowKey])"
                  class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                  @change="toggleRow(row[rowKey])"
                />
              </td>
              <td
                v-for="col in columns"
                :key="col.key"
                :class="['px-4 py-3 text-gray-700 dark:text-gray-300', col.class]"
              >
                <!-- Slot for custom cell rendering -->
                <slot :name="'cell-' + col.key" :row="row" :value="row[col.key]">
                  {{ row[col.key] }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination
      v-if="meta && meta.last_page > 1"
      :meta="meta"
      class="mt-4"
      @page-change="emit('page-change', $event)"
    />
  </div>
</template>
