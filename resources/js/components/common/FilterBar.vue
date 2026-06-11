<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  /**
   * Array of filter definitions:
   * { key, label, type: 'text'|'select'|'date', options?: [{value, label}], placeholder? }
   */
  filters: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'apply', 'reset'])

// Local copy so we can batch-apply
const localValues = ref({ ...props.modelValue })

watch(
  () => props.modelValue,
  (val) => {
    localValues.value = { ...val }
  },
  { deep: true },
)

function apply() {
  emit('update:modelValue', { ...localValues.value })
  emit('apply', { ...localValues.value })
}

function reset() {
  const empty = {}
  props.filters.forEach((f) => {
    empty[f.key] = f.type === 'text' ? '' : null
  })
  localValues.value = empty
  emit('update:modelValue', { ...empty })
  emit('reset')
}

const hasAnyValue = () =>
  Object.values(localValues.value).some((v) => v !== null && v !== '')
</script>

<template>
  <div class="flex flex-wrap gap-3 items-end">
    <template v-for="filter in filters" :key="filter.key">
      <!-- Text input -->
      <div v-if="filter.type === 'text'" class="flex flex-col gap-1 min-w-[180px]">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ filter.label }}</label>
        <input
          v-model="localValues[filter.key]"
          type="text"
          :placeholder="filter.placeholder ?? 'Cari...' "
          class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
          @keyup.enter="apply"
        />
      </div>

      <!-- Select -->
      <div v-else-if="filter.type === 'select'" class="flex flex-col gap-1 min-w-[160px]">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ filter.label }}</label>
        <select
          v-model="localValues[filter.key]"
          class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
        >
          <option :value="null">Semua</option>
          <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>

      <!-- Date -->
      <div v-else-if="filter.type === 'date'" class="flex flex-col gap-1 min-w-[160px]">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ filter.label }}</label>
        <input
          v-model="localValues[filter.key]"
          type="date"
          class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
      </div>
    </template>

    <!-- Action buttons -->
    <div class="flex gap-2 pb-0.5">
      <button
        type="button"
        :disabled="loading"
        class="inline-flex items-center gap-1.5 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
        @click="apply"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
        </svg>
        Filter
      </button>
      <button
        v-if="hasAnyValue()"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        @click="reset"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Reset
      </button>
    </div>
  </div>
</template>
