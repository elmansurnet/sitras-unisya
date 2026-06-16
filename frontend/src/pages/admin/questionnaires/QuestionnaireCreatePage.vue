<script setup>
/**
 * QuestionnaireCreatePage.vue
 * Halaman pembuatan kuesioner baru.
 *
 * Flow (sesuai Bug #6 fix):
 *   1. User isi form dasar (title, type, estimated_minutes, is_paginated)
 *   2. Submit → POST /api/v1/admin/questionnaires
 *   3. Response mengandung { data: { id } }
 *   4. Redirect ke /admin/questionnaires/:id/builder
 *
 * Sebelumnya: route /create langsung memakai QuestionnaireBuilderPage
 * yang menampilkan "Mode create terdeteksi. Data detail tidak dimuat
 * sampai ID kuesioner tersedia." — pesan yang membingungkan user.
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useQuestionnaireStore } from '@/stores/questionnaire'
import { useToast } from '@/composables/useToast'
import FormField from '@/components/common/FormField.vue'

const router = useRouter()
const store = useQuestionnaireStore()
const { toast } = useToast()

const submitting = ref(false)
const errors = reactive({})

const form = reactive({
  title: '',
  type: 'alumni',
  estimated_minutes: 10,
  is_paginated: false,
  description: '',
})

const typeOptions = [
  { value: 'alumni', label: 'Alumni' },
  { value: 'employer', label: 'Employer / Pengguna Lulusan' },
]

function validate() {
  Object.keys(errors).forEach((k) => delete errors[k])
  let valid = true
  if (!form.title.trim()) {
    errors.title = 'Judul kuesioner wajib diisi.'
    valid = false
  }
  if (form.title.trim().length > 255) {
    errors.title = 'Judul maksimal 255 karakter.'
    valid = false
  }
  if (!form.type) {
    errors.type = 'Tipe kuesioner wajib dipilih.'
    valid = false
  }
  if (!form.estimated_minutes || form.estimated_minutes < 1 || form.estimated_minutes > 120) {
    errors.estimated_minutes = 'Estimasi waktu harus antara 1–120 menit.'
    valid = false
  }
  return valid
}

async function handleSubmit() {
  if (!validate()) return
  submitting.value = true
  try {
    const data = await store.create({
      title: form.title.trim(),
      type: form.type,
      estimated_minutes: Number(form.estimated_minutes),
      is_paginated: form.is_paginated,
      description: form.description.trim() || null,
    })
    toast.success('Kuesioner berhasil dibuat. Silakan tambahkan pertanyaan.')
    // Redirect ke builder dengan ID yang baru dibuat
    router.push({ name: 'admin.questionnaires.builder', params: { id: data.id } })
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      Object.assign(errors, serverErrors)
    } else {
      toast.error(err.response?.data?.message ?? 'Gagal membuat kuesioner.')
    }
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'admin.questionnaires.index' })
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <!-- Header -->
    <div class="mb-6 flex items-center gap-3">
      <button
        class="rounded p-1 text-gray-400 hover:text-gray-600 transition-colors"
        aria-label="Kembali ke daftar kuesioner"
        @click="goBack"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Buat Kuesioner Baru</h1>
        <p class="text-sm text-gray-500">Isi data dasar kuesioner, lalu tambahkan pertanyaan di builder.</p>
      </div>
    </div>

    <!-- Form -->
    <form class="card space-y-5" novalidate @submit.prevent="handleSubmit">

      <!-- Judul -->
      <FormField
        label="Judul Kuesioner"
        required
        :error="errors.title"
        html-for="q-title"
      >
        <input
          id="q-title"
          v-model="form.title"
          type="text"
          class="input"
          :class="{ 'input--error': errors.title }"
          placeholder="cth. Tracer Study Angkatan 2022"
          maxlength="255"
          autocomplete="off"
        />
      </FormField>

      <!-- Deskripsi (opsional) -->
      <FormField
        label="Deskripsi"
        html-for="q-desc"
        hint="Opsional. Ditampilkan di halaman pembuka kuesioner."
      >
        <textarea
          id="q-desc"
          v-model="form.description"
          class="input resize-none"
          rows="3"
          maxlength="1000"
          placeholder="Jelaskan tujuan kuesioner ini..."
        />
      </FormField>

      <!-- Tipe kuesioner -->
      <FormField
        label="Tipe Kuesioner"
        required
        :error="errors.type"
        html-for="q-type"
      >
        <select id="q-type" v-model="form.type" class="input">
          <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </FormField>

      <!-- Estimasi waktu -->
      <FormField
        label="Estimasi Waktu Pengisian"
        required
        :error="errors.estimated_minutes"
        html-for="q-duration"
        hint="Dalam menit (1–120)."
      >
        <div class="flex items-center gap-2">
          <input
            id="q-duration"
            v-model.number="form.estimated_minutes"
            type="number"
            class="input w-28"
            :class="{ 'input--error': errors.estimated_minutes }"
            min="1"
            max="120"
          />
          <span class="text-sm text-gray-500">menit</span>
        </div>
      </FormField>

      <!-- Halaman per section -->
      <FormField
        label="Mode Tampilan"
        html-for="q-paginated"
        hint="Pagination: setiap section tampil satu per satu. Tanpa pagination: semua pertanyaan sekaligus."
      >
        <label class="flex cursor-pointer items-center gap-3">
          <input
            id="q-paginated"
            v-model="form.is_paginated"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
          />
          <span class="text-sm text-gray-700">Tampilkan per section (pagination)</span>
        </label>
      </FormField>

      <!-- Tombol aksi -->
      <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
        <button type="button" class="btn btn-ghost" :disabled="submitting" @click="goBack">
          Batal
        </button>
        <button
          type="submit"
          class="btn btn-primary min-w-[140px]"
          :disabled="submitting"
        >
          <span v-if="submitting" class="flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            Menyimpan...
          </span>
          <span v-else>Buat &amp; Lanjut ke Builder</span>
        </button>
      </div>
    </form>
  </div>
</template>
