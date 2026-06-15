<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const alumniStore = useAlumniStore()
const { toast } = useToast()

const isEdit = computed(() => !!route.params.id)
const alumniId = computed(() => route.params.id)
const activeTab = ref('pribadi')
const saving = ref(false)

const tabs = [
  { key: 'pribadi', label: '1. Data Pribadi' },
  { key: 'akademik', label: '2. Data Akademik' },
  { key: 'alamat', label: '3. Alamat' },
  { key: 'kontak', label: '4. Kontak' },
  { key: 'foto', label: '5. Foto' },
]

const form = ref({
  nik: '',
  full_name: '',
  gender: '',
  birthplace: '',
  birthdate: '',
  nim: '',
  study_program_id: '',
  graduation_year_id: '',
  gpa: '',
  thesis_title: '',
  graduation_predicate: '',
  address_street: '',
  address_village: '',
  address_district: '',
  address_city: '',
  address_province: '',
  address_postal_code: '',
  phone: '',
  email: '',
  linkedin_url: '',
})

const photoFile = ref(null)
const photoPreview = ref(null)
const errors = ref({})

onMounted(async () => {
  await alumniStore.fetchMasterData()
  if (isEdit.value) {
    await alumniStore.fetchDetail(alumniId.value)
    if (alumniStore.current) {
      Object.keys(form.value).forEach((key) => {
        if (alumniStore.current[key] !== undefined) {
          form.value[key] = alumniStore.current[key] ?? ''
        }
      })
      if (alumniStore.current.study_program_id) form.value.study_program_id = alumniStore.current.study_program_id
      if (alumniStore.current.graduation_year_id) form.value.graduation_year_id = alumniStore.current.graduation_year_id
    }
  }
})

function handlePhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

function goBack() {
  router.push({ name: 'admin.alumni.index' })
}

async function handleSubmit() {
  errors.value = {}
  saving.value = true
  try {
    if (isEdit.value) {
      await alumniStore.update(alumniId.value, form.value)
      toast.success('Data alumni berhasil diperbarui.')
    } else {
      await alumniStore.create(form.value)
      toast.success('Alumni berhasil ditambahkan.')
    }
    router.push({ name: 'admin.alumni.index' })
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
      toast.error('Periksa kembali data yang Anda masukkan.')
    } else {
      toast.error('Terjadi kesalahan. Coba lagi.')
    }
  } finally {
    saving.value = false
  }
}

const graduationPredicateOptions = [
  { value: 'Dengan Pujian', label: 'Dengan Pujian (Cumlaude)' },
  { value: 'Sangat Memuaskan', label: 'Sangat Memuaskan' },
  { value: 'Memuaskan', label: 'Memuaskan' },
]
</script>

<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <button class="text-gray-500 hover:text-gray-700 p-1 rounded" @click="goBack">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Alumni' : 'Tambah Alumni Baru' }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">Isi seluruh data yang diperlukan</p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit">
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors',
              activeTab === tab.key
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700',
            ]"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <div v-show="activeTab === 'pribadi'" class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="form-label">NIK</label>
            <input v-model="form.nik" type="text" class="form-input" placeholder="16 digit NIK" maxlength="16" />
            <p v-if="errors.nik" class="form-error">{{ errors.nik[0] }}</p>
          </div>
          <div>
            <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
            <input v-model="form.full_name" type="text" class="form-input" required />
            <p v-if="errors.full_name" class="form-error">{{ errors.full_name[0] }}</p>
          </div>
          <div>
            <label class="form-label">Jenis Kelamin</label>
            <select v-model="form.gender" class="form-input">
              <option value="">Pilih...</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div>
            <label class="form-label">Tempat Lahir</label>
            <input v-model="form.birthplace" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Tanggal Lahir</label>
            <input v-model="form.birthdate" type="date" class="form-input" />
          </div>
        </div>
      </div>

      <div v-show="activeTab === 'akademik'" class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="form-label">NIM <span class="text-red-500">*</span></label>
            <input v-model="form.nim" type="text" class="form-input" required />
            <p v-if="errors.nim" class="form-error">{{ errors.nim[0] }}</p>
          </div>
          <div>
            <label class="form-label">Program Studi <span class="text-red-500">*</span></label>
            <select v-model="form.study_program_id" class="form-input" required>
              <option value="">Pilih Program Studi</option>
              <option v-for="opt in alumniStore.studyProgramOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="errors.study_program_id" class="form-error">{{ errors.study_program_id[0] }}</p>
          </div>
          <div>
            <label class="form-label">Angkatan <span class="text-red-500">*</span></label>
            <select v-model="form.graduation_year_id" class="form-input" required>
              <option value="">Pilih Angkatan</option>
              <option v-for="opt in alumniStore.graduationYearOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="errors.graduation_year_id" class="form-error">{{ errors.graduation_year_id[0] }}</p>
          </div>
          <div>
            <label class="form-label">IPK <span class="text-red-500">*</span></label>
            <input v-model="form.gpa" type="number" step="0.01" min="0" max="4" class="form-input" required />
            <p v-if="errors.gpa" class="form-error">{{ errors.gpa[0] }}</p>
          </div>
          <div class="md:col-span-2">
            <label class="form-label">Judul Tugas Akhir</label>
            <textarea v-model="form.thesis_title" rows="2" class="form-input" />
          </div>
          <div>
            <label class="form-label">Predikat Kelulusan</label>
            <select v-model="form.graduation_predicate" class="form-input">
              <option value="">Pilih...</option>
              <option v-for="opt in graduationPredicateOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <div v-show="activeTab === 'alamat'" class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="md:col-span-2">
            <label class="form-label">Jalan / Alamat Lengkap</label>
            <textarea v-model="form.address_street" rows="2" class="form-input" />
          </div>
          <div><label class="form-label">Kelurahan/Desa</label><input v-model="form.address_village" type="text" class="form-input" /></div>
          <div><label class="form-label">Kecamatan</label><input v-model="form.address_district" type="text" class="form-input" /></div>
          <div><label class="form-label">Kota/Kabupaten</label><input v-model="form.address_city" type="text" class="form-input" /></div>
          <div><label class="form-label">Provinsi</label><input v-model="form.address_province" type="text" class="form-input" /></div>
          <div><label class="form-label">Kode Pos</label><input v-model="form.address_postal_code" type="text" class="form-input" maxlength="5" /></div>
        </div>
      </div>

      <div v-show="activeTab === 'kontak'" class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div><label class="form-label">No. WhatsApp <span class="text-red-500">*</span></label><input v-model="form.phone" type="text" class="form-input" required /></div>
          <div><label class="form-label">Email <span class="text-red-500">*</span></label><input v-model="form.email" type="email" class="form-input" required /></div>
          <div class="md:col-span-2"><label class="form-label">LinkedIn URL</label><input v-model="form.linkedin_url" type="url" class="form-input" placeholder="https://linkedin.com/in/..." /></div>
        </div>
      </div>

      <div v-show="activeTab === 'foto'" class="card p-6">
        <div class="max-w-md">
          <label class="form-label">Foto Profil</label>
          <input type="file" accept="image/*" class="form-input" @change="handlePhotoChange" />
          <div v-if="photoPreview" class="mt-4">
            <img :src="photoPreview" alt="Preview foto" class="w-32 h-32 object-cover rounded-lg border border-gray-200" />
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 mt-6">
        <button type="button" class="btn-secondary" @click="goBack">Batal</button>
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Alumni' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
.form-label { @apply block text-sm font-medium text-gray-700 mb-1.5; }
.form-input { @apply w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none; }
.form-error { @apply text-xs text-red-600 mt-1; }
</style>
