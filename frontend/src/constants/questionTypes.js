/**
 * questionTypes.js
 * Konstanta tipe pertanyaan untuk kuesioner SITRAS UNISYA.
 * Dipisah dari QuestionEditor.vue agar bisa di-import
 * di luar <script setup> (ES module named export).
 */

export const QUESTION_TYPES = [
  { value: 'text',      label: 'Teks Singkat' },
  { value: 'textarea',  label: 'Teks Panjang' },
  { value: 'radio',     label: 'Pilihan Ganda (Radio)' },
  { value: 'checkbox',  label: 'Pilihan Banyak (Checkbox)' },
  { value: 'select',    label: 'Dropdown' },
  { value: 'scale',     label: 'Skala Numerik' },
  { value: 'date',      label: 'Tanggal' },
  { value: 'number',    label: 'Angka' },
  { value: 'email',     label: 'Email' },
  { value: 'file',      label: 'Upload File' },
]
