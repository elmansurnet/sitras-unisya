<script setup>
/**
 * Badge.vue — Status badge dengan semua variant
 *
 * Props:
 *   variant : 'success'|'warning'|'danger'|'info'|'neutral'|'purple'
 *   size    : 'sm'|'md'
 *   dot     : Boolean — tampilkan dot indikator
 *
 * Shortcut props (auto-map variant dari value survei/employment):
 *   status  : nilai status dari API (survey_status, is_current, dll)
 */
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['success', 'warning', 'danger', 'info', 'neutral', 'purple'].includes(v),
  },
  size:    { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
  dot:     { type: Boolean, default: false },
  status:  { type: String, default: null },
})

// Auto-map status → variant
const STATUS_MAP = {
  completed:   'success',
  submitted:   'success',
  active:      'success',
  invited:     'info',
  pending:     'warning',
  in_progress: 'warning',
  not_invited: 'neutral',
  inactive:    'neutral',
  expired:     'danger',
  rejected:    'danger',
  superadmin:  'purple',
  admin:       'info',
  alumni:      'success',
  employer:    'warning',
}

const resolvedVariant = computed(() =>
  props.status ? (STATUS_MAP[props.status] ?? 'neutral') : props.variant
)

const VARIANT_CLASSES = {
  success: 'bg-green-50  text-green-700  ring-green-600/20',
  warning: 'bg-amber-50  text-amber-700  ring-amber-600/20',
  danger:  'bg-red-50    text-red-700    ring-red-600/20',
  info:    'bg-blue-50   text-blue-700   ring-blue-600/20',
  neutral: 'bg-gray-100  text-gray-600   ring-gray-500/20',
  purple:  'bg-purple-50 text-purple-700 ring-purple-600/20',
}

const DOT_CLASSES = {
  success: 'bg-green-500',
  warning: 'bg-amber-500',
  danger:  'bg-red-500',
  info:    'bg-blue-500',
  neutral: 'bg-gray-400',
  purple:  'bg-purple-500',
}

const badgeClass = computed(() => [
  'inline-flex items-center gap-1.5 rounded-full font-medium ring-1 ring-inset',
  VARIANT_CLASSES[resolvedVariant.value],
  props.size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
])

const dotClass = computed(() => [
  'h-1.5 w-1.5 rounded-full',
  DOT_CLASSES[resolvedVariant.value],
])

// Label otomatis dari status
const STATUS_LABELS = {
  completed:   'Selesai',
  submitted:   'Terkirim',
  active:      'Aktif',
  invited:     'Diundang',
  pending:     'Menunggu',
  in_progress: 'Berjalan',
  not_invited: 'Belum Diundang',
  inactive:    'Tidak Aktif',
  expired:     'Kedaluwarsa',
  rejected:    'Ditolak',
  superadmin:  'Super Admin',
  admin:       'Admin',
  alumni:      'Alumni',
  employer:    'Employer',
}

const autoLabel = computed(() =>
  props.status ? (STATUS_LABELS[props.status] ?? props.status) : null
)
</script>

<template>
  <span :class="badgeClass">
    <span v-if="dot" :class="dotClass" aria-hidden="true" />
    <slot>{{ autoLabel }}</slot>
  </span>
</template>
