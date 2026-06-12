<script setup>
/**
 * ConfirmModal.vue — Dialog konfirmasi aksi destruktif
 *
 * Props:
 *   modelValue : Boolean — v-model show/hide
 *   title      : String
 *   message    : String
 *   confirmLabel : String (default: 'Ya, Lanjutkan')
 *   cancelLabel  : String (default: 'Batal')
 *   danger     : Boolean — warnai tombol konfirmasi merah
 *   loading    : Boolean — loading state tombol konfirmasi
 *
 * Emits:
 *   update:modelValue(Boolean)
 *   confirm()
 *   cancel()
 */
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue:   { type: Boolean, required: true },
  title:        { type: String,  default: 'Konfirmasi' },
  message:      { type: String,  default: 'Apakah Anda yakin?' },
  confirmLabel: { type: String,  default: 'Ya, Lanjutkan' },
  cancelLabel:  { type: String,  default: 'Batal' },
  danger:       { type: Boolean, default: false },
  loading:      { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

function close() {
  emit('update:modelValue', false)
  emit('cancel')
}

function confirm() {
  emit('confirm')
}

function onKeydown(e) {
  if (!props.modelValue) return
  if (e.key === 'Escape') close()
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="'modal-title'"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
          @click="close"
        />

        <!-- Panel -->
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="modelValue"
            class="relative z-10 w-full max-w-md rounded-xl bg-white shadow-xl"
          >
            <div class="p-6">
              <!-- Icon -->
              <div
                :class="[
                  'mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full',
                  danger ? 'bg-red-100' : 'bg-amber-100',
                ]"
              >
                <svg
                  :class="['h-6 w-6', danger ? 'text-red-600' : 'text-amber-600']"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
              </div>

              <h3
                id="modal-title"
                class="mb-2 text-center text-base font-semibold text-gray-900"
              >
                {{ title }}
              </h3>
              <p class="text-center text-sm text-gray-500">
                {{ message }}
              </p>

              <!-- Slot untuk konten tambahan -->
              <slot />
            </div>

            <!-- Actions -->
            <div class="flex gap-3 border-t border-gray-100 px-6 py-4">
              <button
                type="button"
                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50"
                :disabled="loading"
                @click="close"
              >
                {{ cancelLabel }}
              </button>
              <button
                type="button"
                :class="[
                  'flex-1 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-60',
                  danger
                    ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                    : 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500',
                ]"
                :disabled="loading"
                @click="confirm"
              >
                <svg
                  v-if="loading"
                  class="h-4 w-4 animate-spin"
                  viewBox="0 0 24 24" fill="none"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ confirmLabel }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
