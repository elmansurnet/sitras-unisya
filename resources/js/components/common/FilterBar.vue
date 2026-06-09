<script setup>
import { ref } from 'vue'

const props = defineProps({
  filters: {
    type: Array,
    required: true,
    // [{ key, label, type: 'text'|'select', options?: [{value, label}], placeholder? }]
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

const emit = defineEmits(['update:modelValue', 'search', 'reset'])

const localValues = ref({ ...props.modelValue })

function onInput(key, value) {
  localValues.value[key] = value
  emit('update:modelValue', { ...localValues.value })
}

function onSearch() {
  emit('search', { ...localValues.value })
}

function onReset() {
  const cleared = {}
  props.filters.forEach((f) => {
    cleared[f.key] = f.type === 'text' ? '' : null
  })
  localValues.value = cleared
  emit('update:modelValue', cleared)
  emit('reset')
}

const hasActiveFilters = () =>
  Object.values(localValues.value).some((v) => v !== null && v !== '')
</script>

<template>
  <div class="filter-bar flex flex-wrap items-end gap-3 p-4 bg-[var(--color-surface)] rounded-lg border border-[var(--color-border)]">
    <div
      v-for="filter in filters"
      :key="filter.key"
      class="flex flex-col gap-1 min-w-[140px]"
    >
      <label class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">
        {{ filter.label }}
      </label>

      <input
        v-if="filter.type === 'text'"
        type="text"
        :placeholder="filter.placeholder ?? `Cari ${filter.label}...`"
        :value="localValues[filter.key] ?? ''"
        @input="onInput(filter.key, $event.target.value)"
        @keyup.enter="onSearch"
        class="h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent"
      />

      <select
        v-else-if="filter.type === 'select'"
        :value="localValues[filter.key] ?? ''"
        @change="onInput(filter.key, $event.target.value || null)"
        class="h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent"
      >
        <option value="">Semua</option>
        <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>

    <div class="flex gap-2 ml-auto">
      <button
        type="button"
        class="h-9 px-4 rounded-md border border-[var(--color-border)] text-sm text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
        @click="onReset"
      >
        Reset
      </button>
      <button
        type="button"
        class="h-9 px-4 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors disabled:opacity-50"
        :disabled="loading"
        @click="onSearch"
      >
        <span v-if="loading">Mencari...</span>
        <span v-else>Cari</span>
      </button>
    </div>
  </div>
</template>
