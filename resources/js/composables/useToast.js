import { reactive } from 'vue'

/** Shared reactive state — singleton per app instance */
const toasts = reactive([])

let _idCounter = 0

/**
 * useToast — composable for displaying toast notifications
 *
 * Usage:
 *   const { toast } = useToast()
 *   toast.success('Alumni berhasil disimpan')
 *   toast.error('Gagal menghapus data', { title: 'Error' })
 *   toast.show({ type: 'warning', message: 'Perhatikan...', duration: 6000 })
 */
export function useToast() {
  /**
   * Show a toast
   * @param {{ type: 'success'|'error'|'warning'|'info', message: string, title?: string, duration?: number }} options
   */
  function show({ type = 'info', message, title, duration = 4000 }) {
    const id = ++_idCounter
    toasts.push({ id, type, message, title })
    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
    return id
  }

  function remove(id) {
    const idx = toasts.findIndex((t) => t.id === id)
    if (idx !== -1) toasts.splice(idx, 1)
  }

  const toast = {
    success: (message, opts = {}) => show({ type: 'success', message, ...opts }),
    error: (message, opts = {}) => show({ type: 'error', message, ...opts }),
    warning: (message, opts = {}) => show({ type: 'warning', message, ...opts }),
    info: (message, opts = {}) => show({ type: 'info', message, ...opts }),
    show,
  }

  return { toasts, toast, remove }
}
