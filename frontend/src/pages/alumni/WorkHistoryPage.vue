<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const alumniStore = useAlumniStore()
const { showToast } = useToast()

const showForm = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)
const showDeleteModal = ref(false)
const saving = ref(false)
const errors = ref({})

const emptyForm = {
  company_name: '',
  position: '',
  employment_type: '',
  industry_sector: '',
  start_date: '',
  end_date: '',
  is_current: false,
  city: '',
  province: '',
  country: 'Indonesia',
  monthly_salary_range: '',
  is_relevant_to_study: false,
  waiting_time_months: '',
  description: '',
}

const form = ref({ ...emptyForm })

const employmentTypes = [
  { value: 'penuh_waktu', label: 'Penuh Waktu' },
  { value: 'paruh_waktu', label: 'Paruh Waktu' },
  { value: 'freelance', label: 'Freelance' },
  { value: 'magang', label: 'Magang' },
  { value: 'wirausaha', label: 'Wirausaha' },
]

const salaryRanges = [
  { value: 'lt1jt', label: '< Rp 1 Juta' },
  { value: '1-3jt', label: 'Rp 1–3 Juta' },
  { value: '3-5jt', label: 'Rp 3–5 Juta' },
  { value: 'gt5jt', label: '> Rp 5 Juta' },
]

onMounted(() => {
  alumniStore.fetchWorkHistories()
})

const workHistories = computed(() => alumniStore.workHistories ?? [])

function openCreate() {
  editTarget.value = null
  form.value = { ...emptyForm }
  errors.value = {}
  showForm.value = true
}

function openEdit(job) {
  editTarget.value = job
  form.value = { ...emptyForm, ...job }
  errors.value = {}
  showForm.value = true
}

function cancelForm() {
  showForm.value = false
  editTarget.value = null
}

async function handleSubmit() {
  errors.value = {}
  saving.value = true
  try {
    if (editTarget.value) {
      await alumniStore.updateWorkHistory(editTarget.value.id, form.value)
      showToast('Riwayat pekerjaan berhasil diperbarui.', 'success')
    } else {
      await alumniStore.createWorkHistory(form.value)
      showToast('Riwayat pekerjaan berhasil ditambahkan.', 'success')
    }
    showForm.value = false
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
      showToast('Periksa kembali data Anda.', 'error')
    } else {
      showToast('Gagal menyimpan. Coba lagi.', 'error')
    }
  } finally {
    saving.value = false
  }
}

function confirmDelete(job) {
  deleteTarget.value = job
  showDeleteModal.value = true
}

async function handleDelete() {
  try {
    await alumniStore.deleteWorkHistory(deleteTarget.value.id)
    showToast('Riwayat pekerjaan berhasil dihapus.', 'success')
    showDeleteModal.value = false
  } catch {
    showToast('Gagal menghapus.', 'error')
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-6 px-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Riwayat Pekerjaan</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola daftar pengalaman kerja Anda</p>
      </div>
      <button v-if="!showForm" class="btn-primary flex items-center gap-2" @click="openCreate">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Pekerjaan
      </button>
    </div>

    <!-- Form -->
    <div v-if="showForm" class="card p-6 mb-6">
      <h2 class="font-semibold text-gray-800 mb-5">{{ editTarget ? 'Edit Pekerjaan' : 'Tambah Pekerjaan' }}</h2>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="form-label">Nama Perusahaan <span class="text-red-500">*</span></label>
            <input v-model="form.company_name" type="text" class="form-input" required />
            <p v-if="errors.company_name" class="form-error">{{ errors.company_name[0] }}</p>
          </div>
          <div>
            <label class="form-label">Posisi / Jabatan <span class="text-red-500">*</span></label>
            <input v-model="form.position" type="text" class="form-input" required />
            <p v-if="errors.position" class="form-error">{{ errors.position[0] }}</p>
          </div>
          <div>
            <label class="form-label">Tipe Pekerjaan</label>
            <select v-model="form.employment_type" class="form-input">
              <option value="">Pilih...</option>
              <option v-for="opt in employmentTypes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Sektor Industri</label>
            <input v-model="form.industry_sector" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
            <input v-model="form.start_date" type="date" class="form-input" required />
          </div>
          <div>
            <label class="form-label">Tanggal Selesai</label>
            <input v-model="form.end_date" type="date" class="form-input" :disabled="form.is_current" />
            <label class="flex items-center gap-2 mt-2 text-sm">
              <input v-model="form.is_current" type="checkbox" class="rounded" />
              Masih bekerja di sini
            </label>
          </div>
          <div>
            <label class="form-label">Kota</label>
            <input v-model="form.city" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Provinsi</label>
            <input v-model="form.province" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Rentang Gaji</label>
            <select v-model="form.monthly_salary_range" class="form-input">
              <option value="">Pilih...</option>
              <option v-for="opt in salaryRanges" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Lama Tunggu (bulan)</label>
            <input v-model="form.waiting_time_months" type="number" min="0" class="form-input" />
          </div>
          <div class="md:col-span-2">
            <label class="flex items-center gap-2 text-sm">
              <input v-model="form.is_relevant_to_study" type="checkbox" class="rounded" />
              Pekerjaan ini relevan dengan program studi saya
            </label>
          </div>
          <div class="md:col-span-2">
            <label class="form-label">Deskripsi (opsional)</label>
            <textarea v-model="form.description" rows="3" class="form-input" />
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 mt-5">
          <button type="button" class="btn-secondary" @click="cancelForm">Batal</button>
          <button type="submit" class="btn-primary flex items-center gap-2" :disabled="saving">
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            {{ saving ? 'Menyimpan...' : (editTarget ? 'Simpan Perubahan' : 'Tambah') }}
          </button>
        </div>
      </form>
    </div>

    <!-- List -->
    <div v-if="!workHistories.length && !showForm" class="card p-12 text-center">
      <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <p class="text-gray-500 mb-4">Belum ada riwayat pekerjaan.</p>
      <button class="btn-primary" @click="openCreate">Tambah Pekerjaan Pertama</button>
    </div>

    <div v-else-if="!showForm" class="space-y-4">
      <div
        v-for="job in workHistories"
        :key="job.id"
        class="card p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-gray-900">{{ job.position }}</p>
              <span v-if="job.is_current" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Aktif</span>
            </div>
            <p class="text-sm text-gray-600">{{ job.company_name }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ job.start_date }} — {{ job.is_current ? 'Sekarang' : (job.end_date ?? '-') }}</p>
            <div class="flex items-center gap-3 mt-2">
              <span v-if="job.employment_type" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ job.employment_type }}</span>
              <span v-if="job.is_relevant_to_study" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">Relevan</span>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              class="text-gray-400 hover:text-blue-600 p-1.5 rounded"
              title="Edit"
              @click="openEdit(job)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              class="text-gray-400 hover:text-red-600 p-1.5 rounded"
              title="Hapus"
              @click="confirmDelete(job)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Riwayat Pekerjaan"
      :message="`Hapus pekerjaan sebagai ${deleteTarget?.position} di ${deleteTarget?.company_name}?`"
      confirm-text="Ya, Hapus"
      confirm-variant="danger"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors; }
.form-error { @apply text-xs text-red-600 mt-1; }
</style>
