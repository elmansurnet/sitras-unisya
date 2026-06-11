<script setup>
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

const iconMap = {
  success:
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
  error:
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
  warning:
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
  info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
}

const colorMap = {
  success: 'border-green-200 dark:border-green-800 bg-white dark:bg-gray-900',
  error: 'border-red-200 dark:border-red-800 bg-white dark:bg-gray-900',
  warning: 'border-amber-200 dark:border-amber-800 bg-white dark:bg-gray-900',
  info: 'border-sky-200 dark:border-sky-800 bg-white dark:bg-gray-900',
}

const iconColorMap = {
  success: 'text-green-500',
  error: 'text-red-500',
  warning: 'text-amber-500',
  info: 'text-sky-500',
}
</script>

<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      aria-atomic="false"
      class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]"
    >
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95"
        tag="div"
        class="flex flex-col gap-2"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          role="alert"
          :class="[
            'flex items-start gap-3 rounded-xl border p-4 shadow-lg',
            colorMap[toast.type] ?? colorMap.info,
          ]"
        >
          <!-- Icon -->
          <svg
            :class="['w-5 h-5 flex-shrink-0 mt-0.5', iconColorMap[toast.type] ?? 'text-sky-500']"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            v-html="iconMap[toast.type] ?? iconMap.info"
          />

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p
              v-if="toast.title"
              class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-tight"
            >
              {{ toast.title }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-snug" :class="{ 'mt-0.5': toast.title }">
              {{ toast.message }}
            </p>
          </div>

          <!-- Dismiss -->
          <button
            type="button"
            class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
            aria-label="Tutup notifikasi"
            @click="remove(toast.id)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
