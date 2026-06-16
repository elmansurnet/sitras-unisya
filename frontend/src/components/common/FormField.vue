<script setup>
/**
 * FormField.vue — Bug #9
 * Wrapper reusable untuk form field: label + slot input + error inline + hint.
 *
 * Props:
 *   label       — teks label (wajib)
 *   htmlFor     — atribut `for` pada label (untuk aksesibilitas)
 *   required    — tampilkan tanda * merah
 *   error       — pesan error (string), tampil jika truthy
 *   hint        — teks petunjuk di bawah input (opsional)
 *
 * Penggunaan:
 *   <FormField label="Nama" required :error="errors.name" html-for="name">
 *     <input id="name" v-model="form.name" class="input" />
 *   </FormField>
 */
defineProps({
  label: {
    type: String,
    required: true,
  },
  htmlFor: {
    type: String,
    default: null,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  hint: {
    type: String,
    default: null,
  },
})
</script>

<template>
  <div class="form-field">
    <label
      :for="htmlFor ?? undefined"
      class="form-field__label"
    >
      {{ label }}
      <span v-if="required" class="form-field__required" aria-hidden="true">*</span>
    </label>

    <!-- Slot untuk input / select / textarea -->
    <div class="form-field__control">
      <slot />
    </div>

    <!-- Hint — ditampilkan jika tidak ada error -->
    <p v-if="hint && !error" class="form-field__hint">{{ hint }}</p>

    <!-- Error inline — animasi masuk -->
    <transition name="field-error">
      <p v-if="error" class="form-field__error" role="alert">
        <svg class="inline h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ error }}
      </p>
    </transition>
  </div>
</template>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-field__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151; /* gray-700 */
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.form-field__required {
  color: #ef4444; /* red-500 */
  font-size: 0.875rem;
  line-height: 1;
}

.form-field__control {
  display: flex;
  flex-direction: column;
}

.form-field__hint {
  font-size: 0.75rem;
  color: #9ca3af; /* gray-400 */
  margin-top: 0.125rem;
}

.form-field__error {
  font-size: 0.75rem;
  color: #dc2626; /* red-600 */
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.125rem;
}

/* Animasi error masuk */
.field-error-enter-active {
  transition: all 0.2s ease;
}
.field-error-leave-active {
  transition: all 0.15s ease;
}
.field-error-enter-from,
.field-error-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
