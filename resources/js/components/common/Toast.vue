<script setup>
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

const iconMap = {
  success: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>`,
  error: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>`,
  warning: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>`,
  info: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
}

const colorMap = {
  success: 'bg-[var(--color-success-highlight)] text-[var(--color-success)] border-[var(--color-success)]',
  error: 'bg-[var(--color-error-highlight)] text-[var(--color-error)] border-[var(--color-error)]',
  warning: 'bg-[var(--color-warning-highlight)] text-[var(--color-warning)] border-[var(--color-warning)]',
  info: 'bg-[var(--color-blue-highlight)] text-[var(--color-blue)] border-[var(--color-blue)]',
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80 pointer-events-none"
      aria-live="polite"
      aria-label="Notifikasi"
    >
      <TransitionGroup
        enter-active-class="transition duration-300"
        enter-from-class="opacity-0 translate-x-6"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-200"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-6"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-start gap-3 p-4 rounded-lg border shadow-[var(--shadow-md)]',
            colorMap[toast.type] ?? colorMap.info,
          ]"
          role="alert"
        >
          <span class="flex-shrink-0 mt-0.5" v-html="iconMap[toast.type] ?? iconMap.info" />
          <p class="flex-1 text-sm font-medium">{{ toast.message }}</p>
          <button
            class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity pointer-events-auto"
            @click="dismiss(toast.id)"
            aria-label="Tutup notifikasi"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
