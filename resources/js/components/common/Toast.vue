<script setup>
/**
 * Toast.vue — Notifikasi stack toast
 * Dipakai bersama composable useToast.js
 * Tempatkan 1x di App.vue: <Toast />
 */
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

const TYPE_CLASSES = {
  success: 'bg-green-50  border-green-400  text-green-800',
  error:   'bg-red-50    border-red-400    text-red-800',
  warning: 'bg-amber-50  border-amber-400  text-amber-800',
  info:    'bg-blue-50   border-blue-400   text-blue-800',
}

const ICON_CLASSES = {
  success: 'text-green-500',
  error:   'text-red-500',
  warning: 'text-amber-500',
  info:    'text-blue-500',
}

const ICONS = {
  success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  error:   'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  warning: 'M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
  info:    'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}
</script>

<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      aria-atomic="false"
      class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]"
    >
      <TransitionGroup
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition-all duration-200 absolute w-full"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 translate-y-1"
        move-class="transition-all duration-200"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-start gap-3 rounded-lg border p-3.5 shadow-lg',
            TYPE_CLASSES[toast.type] ?? TYPE_CLASSES.info,
          ]"
          role="alert"
        >
          <!-- Icon -->
          <svg
            :class="['mt-0.5 h-5 w-5 shrink-0', ICON_CLASSES[toast.type] ?? ICON_CLASSES.info]"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS[toast.type] ?? ICONS.info" />
          </svg>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p v-if="toast.title" class="text-sm font-semibold leading-tight">
              {{ toast.title }}
            </p>
            <p class="text-sm leading-snug" :class="toast.title ? 'mt-0.5 opacity-90' : ''">
              {{ toast.message }}
            </p>
          </div>

          <!-- Close -->
          <button
            type="button"
            class="shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 focus:outline-none focus:ring-1 focus:ring-current"
            @click="remove(toast.id)"
            aria-label="Tutup notifikasi"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
