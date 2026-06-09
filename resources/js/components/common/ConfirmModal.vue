<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin?' },
  confirmLabel: { type: String, default: 'Ya, Lanjutkan' },
  cancelLabel: { type: String, default: 'Batal' },
  variant: { type: String, default: 'danger' }, // danger | warning | info
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])

const variantBtn = {
  danger: 'bg-[var(--color-error)] hover:bg-[var(--color-error-hover)] text-white',
  warning: 'bg-[var(--color-warning)] hover:bg-[var(--color-warning-hover)] text-white',
  info: 'bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white',
}

const variantIcon = {
  danger: 'text-[var(--color-error)] bg-[var(--color-error-highlight)]',
  warning: 'text-[var(--color-warning)] bg-[var(--color-warning-highlight)]',
  info: 'text-[var(--color-primary)] bg-[var(--color-primary-highlight)]',
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="emit('cancel')"
        />

        <!-- Dialog -->
        <Transition
          enter-active-class="transition duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="show"
            class="relative z-10 w-full max-w-md bg-[var(--color-surface)] rounded-xl shadow-[var(--shadow-lg)] border border-[var(--color-border)] p-6"
          >
            <div class="flex items-start gap-4">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', variantIcon[variant] ?? variantIcon.danger]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-base font-semibold text-[var(--color-text)] mb-1">{{ title }}</h3>
                <p class="text-sm text-[var(--color-text-muted)]">{{ message }}</p>
              </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
              <button
                type="button"
                class="px-4 py-2 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
                :disabled="loading"
                @click="emit('cancel')"
              >
                {{ cancelLabel }}
              </button>
              <button
                type="button"
                :class="[
                  'px-4 py-2 rounded-md text-sm font-medium transition-colors disabled:opacity-60',
                  variantBtn[variant] ?? variantBtn.danger,
                ]"
                :disabled="loading"
                @click="emit('confirm')"
              >
                <span v-if="loading" class="inline-flex items-center gap-2">
                  <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                  </svg>
                  Memproses...
                </span>
                <span v-else>{{ confirmLabel }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
