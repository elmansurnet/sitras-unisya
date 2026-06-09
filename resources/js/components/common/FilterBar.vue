<script setup>
/**
 * FilterBar.vue — Multi-filter bar dengan reset
 * Task 2A.17 | Sesuai 06_UI_UX.md Design System
 */
import { ref, watch } from 'vue'

const props = defineProps({
  /**
   * filters: [
   *   { key, type: 'text'|'select'|'date', label, placeholder?, options?: [{value, label}] }
   * ]
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

const emit = defineEmits(['update:modelValue', 'search', 'reset'])

const local = ref({ ...props.modelValue })

watch(
  () => props.modelValue,
  (v) => { local.value = { ...v } },
  { deep: true }
)

function onInput(key, value) {
  local.value[key] = value
  emit('update:modelValue', { ...local.value })
}

function onSearch() {
  emit('search', { ...local.value })
}

function onReset() {
  const cleared = {}
  props.filters.forEach((f) => { cleared[f.key] = '' })
  local.value = cleared
  emit('update:modelValue', { ...local.value })
  emit('reset')
}

function onKeydown(e) {
  if (e.key === 'Enter') onSearch()
}
</script>

<template>
  <div class="filter-bar" role="search">
    <template v-for="f in filters" :key="f.key">
      <!-- Text search -->
      <div v-if="f.type === 'text'" class="filter-field filter-field--text">
        <label :for="`filter-${f.key}`" class="sr-only">{{ f.label }}</label>
        <div class="filter-input-wrap">
          <svg class="filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input
            :id="`filter-${f.key}`"
            type="text"
            class="filter-input"
            :placeholder="f.placeholder ?? f.label"
            :value="local[f.key] ?? ''"
            :disabled="loading"
            @input="onInput(f.key, $event.target.value)"
            @keydown="onKeydown"
          />
        </div>
      </div>

      <!-- Select -->
      <div v-else-if="f.type === 'select'" class="filter-field">
        <label :for="`filter-${f.key}`" class="sr-only">{{ f.label }}</label>
        <select
          :id="`filter-${f.key}`"
          class="filter-select"
          :value="local[f.key] ?? ''"
          :disabled="loading"
          @change="onInput(f.key, $event.target.value || null)"
        >
          <option value="">{{ f.placeholder ?? `Semua ${f.label}` }}</option>
          <option
            v-for="opt in f.options"
            :key="opt.value"
            :value="opt.value"
          >{{ opt.label }}</option>
        </select>
      </div>

      <!-- Date -->
      <div v-else-if="f.type === 'date'" class="filter-field">
        <label :for="`filter-${f.key}`" class="sr-only">{{ f.label }}</label>
        <input
          :id="`filter-${f.key}`"
          type="date"
          class="filter-input"
          :value="local[f.key] ?? ''"
          :disabled="loading"
          @change="onInput(f.key, $event.target.value || null)"
        />
      </div>
    </template>

    <!-- Actions -->
    <div class="filter-actions">
      <button
        type="button"
        class="btn btn-primary btn-sm"
        :disabled="loading"
        @click="onSearch"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        Cari
      </button>
      <button
        type="button"
        class="btn btn-ghost btn-sm"
        :disabled="loading"
        @click="onReset"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<style scoped>
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  align-items: flex-end;
  padding: var(--space-4);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}
.filter-field { display: flex; flex-direction: column; min-width: 160px; flex: 1; }
.filter-field--text { min-width: 200px; flex: 2; }
.filter-input-wrap { position: relative; }
.filter-icon {
  position: absolute; left: var(--space-3); top: 50%; transform: translateY(-50%);
  color: var(--color-text-faint); pointer-events: none;
}
.filter-input {
  width: 100%; padding: var(--space-2) var(--space-3);
  padding-left: var(--space-9);
  border: 1px solid var(--color-border); border-radius: var(--radius-md);
  background: var(--color-surface-2); color: var(--color-text);
  font-size: var(--text-sm);
  transition: border-color var(--transition-interactive);
}
.filter-input:not(.filter-input-wrap .filter-input) { padding-left: var(--space-3); }
.filter-select {
  width: 100%; padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border); border-radius: var(--radius-md);
  background: var(--color-surface-2); color: var(--color-text);
  font-size: var(--text-sm); cursor: pointer;
  transition: border-color var(--transition-interactive);
}
.filter-input:focus, .filter-select:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
  border-color: var(--color-primary);
}
.filter-actions { display: flex; gap: var(--space-2); align-items: center; }

/* Btn utils (jika belum global) */
.btn { display:inline-flex; align-items:center; gap:var(--space-2); font-size:var(--text-sm);
       font-weight:500; border-radius:var(--radius-md); cursor:pointer; border:none;
       transition: background var(--transition-interactive), color var(--transition-interactive); }
.btn:disabled { opacity:0.5; cursor:not-allowed; }
.btn-sm { padding: var(--space-2) var(--space-3); }
.btn-primary { background:var(--color-primary); color:#fff; }
.btn-primary:hover:not(:disabled) { background:var(--color-primary-hover); }
.btn-ghost { background:transparent; color:var(--color-text-muted); }
.btn-ghost:hover:not(:disabled) { background:var(--color-surface-offset); color:var(--color-text); }
</style>
