<script setup>
/**
 * Badge component — covers all status variants used in SITRAS:
 * survey_status: belum_disurvei | terkirim | sedang_mengisi | selesai
 * generic: active | inactive | warning | error | info | default
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) =>
      [
        // Survey status
        'belum_disurvei',
        'terkirim',
        'sedang_mengisi',
        'selesai',
        // Generic
        'active',
        'inactive',
        'warning',
        'error',
        'info',
        'default',
        // Survey period status
        'draft',
        'aktif',
        'arsip',
        'closed',
      ].includes(v),
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md'].includes(v),
  },
  dot: {
    type: Boolean,
    default: false,
  },
})

const variantMap = {
  // Survey status
  belum_disurvei: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
  terkirim: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
  sedang_mengisi: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  selesai: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  // Generic
  active: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  inactive: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
  warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  error: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  info: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
  default: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  // Questionnaire / period status
  draft: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  aktif: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
  arsip: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
  closed: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
}

const dotMap = {
  belum_disurvei: 'bg-gray-400',
  terkirim: 'bg-blue-500',
  sedang_mengisi: 'bg-amber-500',
  selesai: 'bg-green-500',
  active: 'bg-green-500',
  inactive: 'bg-gray-400',
  warning: 'bg-amber-500',
  error: 'bg-red-500',
  info: 'bg-sky-500',
  default: 'bg-gray-400',
  draft: 'bg-gray-400',
  aktif: 'bg-teal-500',
  arsip: 'bg-gray-400',
  closed: 'bg-gray-400',
}

const sizeMap = {
  xs: 'px-1.5 py-0.5 text-[10px]',
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2.5 py-1 text-sm',
}

const labelMap = {
  belum_disurvei: 'Belum Disurvei',
  terkirim: 'Terkirim',
  sedang_mengisi: 'Sedang Mengisi',
  selesai: 'Selesai',
  active: 'Aktif',
  inactive: 'Tidak Aktif',
  warning: 'Peringatan',
  error: 'Error',
  info: 'Info',
  default: 'Default',
  draft: 'Draft',
  aktif: 'Aktif',
  arsip: 'Arsip',
  closed: 'Ditutup',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-full font-medium',
      variantMap[variant],
      sizeMap[size],
    ]"
  >
    <span
      v-if="dot"
      :class="['inline-block rounded-full flex-shrink-0', dotMap[variant], size === 'xs' ? 'w-1.5 h-1.5' : 'w-2 h-2']"
    />
    <slot>{{ labelMap[variant] }}</slot>
  </span>
</template>
