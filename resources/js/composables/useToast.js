/**
 * composables/useToast.js — Singleton toast state
 * Task 2A.21 | Sesuai 06_UI_UX.md Design System
 *
 * Usage:
 *   const { toast } = useToast()
 *   toast.success('Berhasil disimpan.')
 *   toast.error('Terjadi kesalahan.')
 *   toast.warning('Periksa kembali data Anda.')
 *   toast.info('Memproses permintaan...')
 */
import { reactive } from 'vue'

const DEFAULT_DURATION = 4000 // ms

const state = reactive({
  toasts: [],
})

let idCounter = 0

function add(message, type = 'info', duration = DEFAULT_DURATION) {
  const id = ++idCounter
  state.toasts.push({ id, message, type })

  if (duration > 0) {
    setTimeout(() => remove(id), duration)
  }

  return id
}

function remove(id) {
  const idx = state.toasts.findIndex((t) => t.id === id)
  if (idx !== -1) state.toasts.splice(idx, 1)
}

function clear() {
  state.toasts = []
}

const toast = {
  success: (msg, dur) => add(msg, 'success', dur),
  error:   (msg, dur) => add(msg, 'error',   dur ?? 6000),
  warning: (msg, dur) => add(msg, 'warning', dur),
  info:    (msg, dur) => add(msg, 'info',    dur),
}

export function useToast() {
  return {
    toasts: state.toasts,
    toast,
    remove,
    clear,
  }
}
