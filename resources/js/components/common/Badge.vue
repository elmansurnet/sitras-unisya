<script setup>
const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    // default | success | warning | error | info | muted
  },
  size: {
    type: String,
    default: 'md',
  },
  dot: {
    type: Boolean,
    default: false,
  },
})

const variantClass = {
  default: 'bg-[var(--color-surface-offset)] text-[var(--color-text-muted)]',
  success: 'bg-[var(--color-success-highlight)] text-[var(--color-success)]',
  warning: 'bg-[var(--color-warning-highlight)] text-[var(--color-warning)]',
  error: 'bg-[var(--color-error-highlight)] text-[var(--color-error)]',
  info: 'bg-[var(--color-blue-highlight)] text-[var(--color-blue)]',
  primary: 'bg-[var(--color-primary-highlight)] text-[var(--color-primary)]',
  muted: 'bg-[var(--color-surface-dynamic)] text-[var(--color-text-faint)]',
}

const dotClass = {
  default: 'bg-[var(--color-text-faint)]',
  success: 'bg-[var(--color-success)]',
  warning: 'bg-[var(--color-warning)]',
  error: 'bg-[var(--color-error)]',
  info: 'bg-[var(--color-blue)]',
  primary: 'bg-[var(--color-primary)]',
  muted: 'bg-[var(--color-text-faint)]',
}

const sizeClass = {
  sm: 'text-[10px] px-1.5 py-0.5',
  md: 'text-xs px-2 py-0.5',
  lg: 'text-sm px-2.5 py-1',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 font-medium rounded-full',
      variantClass[variant] ?? variantClass.default,
      sizeClass[size] ?? sizeClass.md,
    ]"
  >
    <span
      v-if="dot"
      :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', dotClass[variant] ?? dotClass.default]"
    />
    <slot />
  </span>
</template>
