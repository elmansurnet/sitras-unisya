/**
 * useToast.js — Composable manajemen toast notification
 *
 * Usage:
 *   const { toast } = useToast()
 *   toast.success('Alumni berhasil disimpan')
 *   toast.error('Gagal menghapus data', { title: 'Error' })
 *   toast.warning('Perhatian', { duration: 8000 })
 *   toast.info('Info pesan')
 */

import { ref } from 'vue'

/** Singleton state — shared across all useToast() calls */
const toasts = ref([])
let _counter = 0

/**
 * Add a toast
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {string} message
 * @param {{ title?: string, duration?: number }} options
 */
function add(type, message, options = {}) {
  const id       = ++_counter
  const duration = options.duration ?? 4000
  const title    = options.title ?? null

  toasts.value.push({ id, type, message, title })

  if (duration > 0) {
    setTimeout(() => remove(id), duration)
  }

  return id
}

function remove(id) {
  const idx = toasts.value.findIndex(t => t.id === id)
  if (idx !== -1) toasts.value.splice(idx, 1)
}

function clear() {
  toasts.value = []
}

/**
 * Convenience helpers
 */
const toast = {
  success: (message, opts = {}) => add('success', message, opts),
  error:   (message, opts = {}) => add('error',   message, opts),
  warning: (message, opts = {}) => add('warning', message, opts),
  info:    (message, opts = {}) => add('info',    message, opts),
}

export function useToast() {
  return { toasts, toast, add, remove, clear }
}
