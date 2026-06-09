import { ref } from 'vue'

const toasts = ref([])
let _nextId = 0

export function useToast() {
  function show({ message, type = 'info', duration = 4000 }) {
    const id = ++_nextId
    toasts.value.push({ id, message, type })
    if (duration > 0) {
      setTimeout(() => dismiss(id), duration)
    }
    return id
  }

  function success(message, duration = 4000) {
    return show({ message, type: 'success', duration })
  }

  function error(message, duration = 6000) {
    return show({ message, type: 'error', duration })
  }

  function warning(message, duration = 5000) {
    return show({ message, type: 'warning', duration })
  }

  function info(message, duration = 4000) {
    return show({ message, type: 'info', duration })
  }

  function dismiss(id) {
    const idx = toasts.value.findIndex((t) => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  function clearAll() {
    toasts.value = []
  }

  return { toasts, show, success, error, warning, info, dismiss, clearAll }
}
