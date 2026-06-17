<script setup>
/**
 * QuestionnaireCreatePage.vue
 * Halaman pembuatan kuesioner baru.
 *
 * Flow:
 *   1. User isi form dasar (title, type, estimated_minutes, is_paginated, description)
 *   2. Submit → POST /api/v1/admin/questionnaires (hanya header, questions opsional)
 *   3. Response mengandung { data: { id } }
 *   4. Redirect ke /admin/questionnaires/:id/builder
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useQuestionnaireStore } from '@/stores/questionnaire'
import { useToast } from '@/composables/useToast'

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
  <div>
    <!-- Page Header — konsisten dengan halaman admin lain -->
    <div class="mb-6 flex items-center gap-3">
      <button
        class="rounded p-1 text-gray-500 hover:text-gray-700 transition-colors"
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

    <form novalidate @submit.prevent="handleSubmit">

      <!-- Informasi Dasar -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Informasi Dasar
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <!-- Judul -->
          <div class="sm:col-span-2">
            <label for="q-title" class="mb-1 block text-sm font-medium text-gray-700">
              Judul Kuesioner <span class="text-red-500">*</span>
            </label>
            <input
              id="q-title"
              v-model="form.title"
              type="text"
              class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :class="errors.title ? 'border-red-400' : 'border-gray-300'"
              placeholder="cth. Tracer Study Angkatan 2022"
              maxlength="255"
              autocomplete="off"
            />
            <p v-if="errors.title" class="mt-1 text-xs text-red-500">{{ errors.title }}</p>
          </div>

          <!-- Tipe kuesioner -->
          <div>
            <label for="q-type" class="mb-1 block text-sm font-medium text-gray-700">
              Tipe Kuesioner <span class="text-red-500">*</span>
            </label>
            <select
              id="q-type"
              v-model="form.type"
              class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :class="errors.type ? 'border-red-400' : 'border-gray-300'"
            >
              <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="errors.type" class="mt-1 text-xs text-red-500">{{ errors.type }}</p>
          </div>

          <!-- Estimasi waktu -->
          <div>
            <label for="q-duration" class="mb-1 block text-sm font-medium text-gray-700">
              Estimasi Waktu Pengisian <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                id="q-duration"
                v-model.number="form.estimated_minutes"
                type="number"
                class="block w-28 rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                :class="errors.estimated_minutes ? 'border-red-400' : 'border-gray-300'"
                min="1"
                max="120"
              />
              <span class="text-sm text-gray-500">menit (1–120)</span>
            </div>
            <p v-if="errors.estimated_minutes" class="mt-1 text-xs text-red-500">{{ errors.estimated_minutes }}</p>
          </div>

          <!-- Deskripsi -->
          <div class="sm:col-span-2">
            <label for="q-desc" class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
            <p class="mb-1 text-xs text-gray-400">Opsional. Ditampilkan di halaman pembuka kuesioner.</p>
            <textarea
              id="q-desc"
              v-model="form.description"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 resize-none"
              rows="3"
              maxlength="1000"
              placeholder="Jelaskan tujuan kuesioner ini..."
            />
          </div>

        </div>
      </div>

      <!-- Pengaturan Tampilan -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Pengaturan Tampilan
        </h2>
        <label class="flex cursor-pointer items-center gap-3">
          <input
            id="q-paginated"
            v-model="form.is_paginated"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
          />
          <div>
            <span class="text-sm font-medium text-gray-700">Tampilkan per section (pagination)</span>
            <p class="text-xs text-gray-400">Setiap section tampil satu per satu. Tanpa pagination: semua pertanyaan sekaligus.</p>
          </div>
        </label>
      </div>

      <!-- Tombol aksi -->
      <div class="flex items-center justify-end gap-3">
        <button
          type="button"
          class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          :disabled="submitting"
          @click="goBack"
        >
          Batal
        </button>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60"
          :disabled="submitting"
        >
          <svg v-if="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          <span>{{ submitting ? 'Menyimpan...' : 'Buat &amp; Lanjut ke Builder' }}</span>
        </button>
      </div>

    </form>
  </div>
</template>
