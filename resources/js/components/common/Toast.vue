<script setup>
/**
 * Toast.vue — Toast notification container
 * Task 2A.21 | Sesuai 06_UI_UX.md Design System
 * Digunakan bersama composable useToast.js
 */
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

const ICONS = {
  success: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>`,
  error:   `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
  warning: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  info:    `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
}
</script>

<template>
  <Teleport to="body">
    <div class="toast-container" aria-live="polite" aria-atomic="false" role="status">
      <TransitionGroup name="toast" tag="div" class="toast-list">
        <div
          v-for="t in toasts"
          :key="t.id"
          :class="['toast', `toast--${t.type}`]"
          role="alert"
        >
          <span class="toast-icon" v-html="ICONS[t.type] ?? ICONS.info"></span>
          <span class="toast-msg">{{ t.message }}</span>
          <button
            type="button"
            class="toast-close"
            :aria-label="'Tutup notifikasi'"
            @click="remove(t.id)"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container {
  position: fixed; bottom: var(--space-6); right: var(--space-6);
  z-index: 2000; display: flex; flex-direction: column; gap: var(--space-2);
  pointer-events: none;
  max-width: min(380px, calc(100vw - var(--space-8)));
}
.toast-list { display: flex; flex-direction: column; gap: var(--space-2); }
.toast {
  display: flex; align-items: flex-start; gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  pointer-events: all;
  font-size: var(--text-sm);
  color: var(--color-text);
  min-width: 260px;
}
.toast-icon { flex-shrink: 0; margin-top: 1px; }
.toast-msg  { flex: 1; line-height: 1.5; }
.toast-close {
  flex-shrink: 0; padding: 2px;
  color: var(--color-text-faint); cursor: pointer; background: none; border: none;
  border-radius: var(--radius-sm);
  transition: color var(--transition-interactive);
}
.toast-close:hover { color: var(--color-text); }

/* Variants */
.toast--success .toast-icon { color: var(--color-success); }
.toast--error   .toast-icon { color: var(--color-error); }
.toast--warning .toast-icon { color: var(--color-warning); }
.toast--info    .toast-icon { color: var(--color-blue); }

/* Transition */
.toast-enter-active { transition: all 0.22s cubic-bezier(0.16,1,0.3,1); }
.toast-leave-active { transition: all 0.18s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(20px); }
.toast-leave-to     { opacity: 0; transform: translateX(20px); }
.toast-move         { transition: transform 0.2s ease; }

@media (prefers-reduced-motion: reduce) {
  .toast-enter-active, .toast-leave-active, .toast-move { transition: none; }
}
</style>
