<script setup>
/**
 * ConfirmModal.vue — Confirm / danger dialog
 * Task 2A.20 | Sesuai 06_UI_UX.md Design System
 */
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // v-model:open
  title:      { type: String,  default: 'Konfirmasi' },
  message:    { type: String,  default: 'Apakah Anda yakin?' },
  confirmText:{ type: String,  default: 'Ya, Lanjutkan' },
  cancelText: { type: String,  default: 'Batal' },
  variant:    {
    type: String,
    default: 'default', // 'default' | 'danger'
    validator: (v) => ['default', 'danger'].includes(v),
  },
  loading:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

function confirm() {
  if (props.loading) return
  emit('confirm')
}

function cancel() {
  if (props.loading) return
  isOpen.value = false
  emit('cancel')
}

function onBackdrop(e) {
  if (e.target === e.currentTarget) cancel()
}

function onKeydown(e) {
  if (e.key === 'Escape') cancel()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="modal-backdrop"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="'confirm-title'"
        @click="onBackdrop"
        @keydown="onKeydown"
      >
        <div class="modal-card" tabindex="-1">
          <!-- Header -->
          <div class="modal-header">
            <div :class="['modal-icon', `modal-icon--${variant}`]" aria-hidden="true">
              <svg v-if="variant === 'danger'" width="20" height="20" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
              <svg v-else width="20" height="20" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
            </div>
            <h2 id="confirm-title" class="modal-title">{{ title }}</h2>
          </div>

          <!-- Body -->
          <p class="modal-message">
            <slot>{{ message }}</slot>
          </p>

          <!-- Footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-ghost"
              :disabled="loading"
              @click="cancel"
            >{{ cancelText }}</button>
            <button
              type="button"
              :class="['btn', variant === 'danger' ? 'btn-danger' : 'btn-primary']"
              :disabled="loading"
              @click="confirm"
            >
              <span v-if="loading" class="btn-spinner" aria-hidden="true"></span>
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0;
  background: oklch(0.1 0 0 / 0.55);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
  padding: var(--space-4);
}
.modal-card {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  width: 100%; max-width: 420px;
  padding: var(--space-6);
  display: flex; flex-direction: column; gap: var(--space-4);
}
.modal-header { display: flex; align-items: center; gap: var(--space-3); }
.modal-icon {
  width: 40px; height: 40px; border-radius: var(--radius-full);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.modal-icon--default { background: var(--color-blue-highlight); color: var(--color-blue); }
.modal-icon--danger  { background: var(--color-error-highlight); color: var(--color-error); }
.modal-title { font-size: var(--text-lg); font-weight: 600; color: var(--color-text); margin: 0; }
.modal-message { margin: 0; color: var(--color-text-muted); font-size: var(--text-sm); line-height: 1.6; }
.modal-footer { display: flex; justify-content: flex-end; gap: var(--space-3); padding-top: var(--space-2); }

.btn {
  display: inline-flex; align-items: center; gap: var(--space-2);
  font-size: var(--text-sm); font-weight: 500;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md); cursor: pointer; border: none;
  min-height: 36px;
  transition: background var(--transition-interactive);
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-ghost   { background: transparent; color: var(--color-text-muted); }
.btn-ghost:hover:not(:disabled) { background: var(--color-surface-offset); color: var(--color-text); }
.btn-primary { background: var(--color-primary); color: #fff; }
.btn-primary:hover:not(:disabled) { background: var(--color-primary-hover); }
.btn-danger  { background: var(--color-error); color: #fff; }
.btn-danger:hover:not(:disabled) { background: var(--color-error-hover); }

.btn-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Transitions */
.modal-enter-active, .modal-leave-active { transition: opacity 0.18s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-card, .modal-leave-active .modal-card {
  transition: transform 0.18s cubic-bezier(0.16,1,0.3,1), opacity 0.18s ease;
}
.modal-enter-from .modal-card, .modal-leave-to .modal-card {
  transform: translateY(8px) scale(0.97); opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .modal-enter-active, .modal-leave-active,
  .modal-enter-active .modal-card, .modal-leave-active .modal-card { transition: none; }
  .btn-spinner { animation: none; }
}
</style>
