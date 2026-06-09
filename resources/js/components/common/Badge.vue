<script setup>
/**
 * Badge.vue — Semua variant status
 * Task 2A.19 | Sesuai 06_UI_UX.md Design System
 *
 * Variants: default | primary | success | warning | error | info | muted
 * Sizes:    sm | md (default)
 */
defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'primary', 'success', 'warning', 'error', 'info', 'muted'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v),
  },
  dot: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <span :class="['badge', `badge--${variant}`, `badge--${size}`]">
    <span v-if="dot" class="badge-dot" aria-hidden="true"></span>
    <slot />
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-weight: 500;
  border-radius: var(--radius-full);
  white-space: nowrap;
  letter-spacing: 0.01em;
}

/* Sizes */
.badge--sm { font-size: 0.6875rem; padding: 1px var(--space-2); }
.badge--md { font-size: var(--text-xs); padding: var(--space-1) var(--space-2); }

/* Dot */
.badge-dot {
  width: 6px; height: 6px;
  border-radius: var(--radius-full);
  background: currentColor;
  flex-shrink: 0;
}

/* Variants */
.badge--default {
  background: var(--color-surface-offset-2);
  color: var(--color-text-muted);
}
.badge--primary {
  background: var(--color-primary-highlight);
  color: var(--color-primary);
}
.badge--success {
  background: var(--color-success-highlight);
  color: var(--color-success);
}
.badge--warning {
  background: var(--color-warning-highlight);
  color: var(--color-warning);
}
.badge--error {
  background: var(--color-error-highlight);
  color: var(--color-error);
}
.badge--info {
  background: var(--color-blue-highlight);
  color: var(--color-blue);
}
.badge--muted {
  background: transparent;
  color: var(--color-text-faint);
  border: 1px solid var(--color-border);
}
</style>
