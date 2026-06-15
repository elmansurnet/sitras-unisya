<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const alumniStore = useAlumniStore()
const { toast } = useToast()

const isEdit  = computed(() => !!route.params.id)
const alumniId = computed(() => route.params.id)
const activeTab = ref('pribadi')
const saving  = ref(false)
const errors  = ref({})

// Semua field sesuai $fillable model Alumni dan kolom migration
const form = ref({
  // Identitas — kolom DB: birth_place, birth_date (bukan birthplace/birthdate)
  nik:                 '',
  full_name:           '',
  gender:              '',
  birth_place:         '',
  birth_date:          '',
  // Akademik
  nim:                 '',
  study_program_id:    '',
  graduation_year_id:  '',
  gpa:                 '',
  thesis_title:        '',
  graduation_predicate:'',
  // Alamat
  address_street:      '',
  address_village:     '',
  address_district:    '',
  address_city:        '',
  address_province:    '',
  address_postal_code: '',
  address_latitude:    '',
  address_longitude:   '',
  // Kontak
  phone:               '',
  email:               '',
  linkedin_url:        '',
})

const TABS = [
  { key: 'pribadi',  label: 'Data Pribadi' },
  { key: 'akademik', label: 'Akademik' },
  { key: 'alamat',   label: 'Alamat' },
  { key: 'kontak',   label: 'Kontak' },
]

const GENDER_OPTIONS = [
  { value: 'L', label: 'Laki-laki' },
  { value: 'P', label: 'Perempuan' },
]

const PREDICATE_OPTIONS = [
  { value: 'Memuaskan',        label: 'Memuaskan' },
  { value: 'Sangat Memuaskan', label: 'Sangat Memuaskan' },
  { value: 'Pujian',           label: 'Pujian (Cum Laude)' },
]

onMounted(async () => {
  try {
    await alumniStore.fetchMasterData()
    if (isEdit.value) {
      await alumniStore.fetchDetail(alumniId.value)
      if (alumniStore.current) {
        Object.keys(form.value).forEach((key) => {
          if (alumniStore.current[key] !== undefined) {
            form.value[key] = alumniStore.current[key] ?? ''
          }
        })
      }
    }
  } catch {
    toast.error('Gagal memuat data master alumni.')
  }
})

async function fillCurrentLocation() {
  if (!navigator.geolocation) {
    toast.error('Browser tidak mendukung geolokasi.')
    return
  }
  navigator.geolocation.getCurrentPosition(
    (position) => {
      form.value.address_latitude  = String(position.coords.latitude)
      form.value.address_longitude = String(position.coords.longitude)
      toast.success('Lokasi saat ini berhasil diisi.')
    },
    () => toast.error('Gagal mengambil lokasi saat ini.'),
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}
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
    errors.value = err.response?.data?.errors ?? {}
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan data alumni.')
    // Pindah ke tab pertama yang ada error — gunakan nama kolom DB yang benar
    const errKeys = Object.keys(errors.value)
    if (errKeys.some(k => ['nik', 'full_name', 'gender', 'birth_place', 'birth_date'].includes(k))) {
      activeTab.value = 'pribadi'
    } else if (errKeys.some(k => ['nim', 'study_program_id', 'graduation_year_id', 'gpa', 'thesis_title', 'graduation_predicate'].includes(k))) {
      activeTab.value = 'akademik'
    } else if (errKeys.some(k => k.startsWith('address_'))) {
      activeTab.value = 'alamat'
    } else if (errKeys.some(k => ['phone', 'email', 'linkedin_url'].includes(k))) {
      activeTab.value = 'kontak'
    }
  } finally {
    saving.value = false
  }
}

function fieldError(key) {
  return errors.value[key]?.[0] ?? null
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mb-6 flex items-center gap-3">
      <button
        class="rounded p-1 text-gray-500 hover:text-gray-700"
        @click="router.push({ name: 'admin.alumni.index' })"
        aria-label="Kembali"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-xl font-semibold text-gray-900">
        {{ isEdit ? 'Edit Data Alumni' : 'Tambah Alumni Baru' }}
      </h1>
    </div>

    <form @submit.prevent="handleSubmit" novalidate>
      <!-- Tabs -->
      <div class="mb-1 border-b border-gray-200">
        <nav class="-mb-px flex gap-1" aria-label="Form tabs">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            :class="[
              'px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
              activeTab === tab.key
                ? 'border-teal-600 text-teal-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- ── Tab: Data Pribadi ──────────────────────────────────────────────── -->
      <div v-show="activeTab === 'pribadi'" class="card mt-4 p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <!-- NIK -->
          <div>
            <label class="form-label">NIK <span class="text-red-500">*</span></label>
            <input
              v-model="form.nik"
              class="form-input"
              :class="{ 'border-red-400': fieldError('nik') }"
              maxlength="16"
              placeholder="16 digit NIK"
            />
            <p v-if="fieldError('nik')" class="form-error">{{ fieldError('nik') }}</p>
          </div>

          <!-- Nama Lengkap -->
          <div>
            <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
            <input
              v-model="form.full_name"
              class="form-input"
              :class="{ 'border-red-400': fieldError('full_name') }"
              placeholder="Nama sesuai ijazah"
            />
            <p v-if="fieldError('full_name')" class="form-error">{{ fieldError('full_name') }}</p>
          </div>

          <!-- Jenis Kelamin -->
          <div>
            <label class="form-label">Jenis Kelamin</label>
            <select
              v-model="form.gender"
              class="form-input"
              :class="{ 'border-red-400': fieldError('gender') }"
            >
              <option value="">Pilih Jenis Kelamin</option>
              <option v-for="opt in GENDER_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="fieldError('gender')" class="form-error">{{ fieldError('gender') }}</p>
          </div>

          <!-- Tempat Lahir — kolom DB: birth_place -->
          <div>
            <label class="form-label">Tempat Lahir</label>
            <input
              v-model="form.birth_place"
              class="form-input"
              :class="{ 'border-red-400': fieldError('birth_place') }"
              placeholder="Kota/Kabupaten"
            />
            <p v-if="fieldError('birth_place')" class="form-error">{{ fieldError('birth_place') }}</p>
          </div>

          <!-- Tanggal Lahir — kolom DB: birth_date -->
          <div>
            <label class="form-label">Tanggal Lahir</label>
            <input
              v-model="form.birth_date"
              type="date"
              class="form-input"
              :class="{ 'border-red-400': fieldError('birth_date') }"
            />
            <p v-if="fieldError('birth_date')" class="form-error">{{ fieldError('birth_date') }}</p>
          </div>
        </div>
      </div>

      <!-- ── Tab: Akademik ──────────────────────────────────────────────────── -->
      <div v-show="activeTab === 'akademik'" class="card mt-4 p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <!-- NIM -->
          <div>
            <label class="form-label">NIM <span class="text-red-500">*</span></label>
            <input
              v-model="form.nim"
              class="form-input"
              :class="{ 'border-red-400': fieldError('nim') }"
              placeholder="Nomor Induk Mahasiswa"
            />
            <p v-if="fieldError('nim')" class="form-error">{{ fieldError('nim') }}</p>
          </div>

          <!-- Program Studi -->
          <div>
            <label class="form-label">Program Studi <span class="text-red-500">*</span></label>
            <select
              v-model="form.study_program_id"
              class="form-input"
              :class="{ 'border-red-400': fieldError('study_program_id') }"
            >
              <option value="">Pilih Program Studi</option>
              <option
                v-for="opt in alumniStore.studyProgramOptions"
                :key="opt.value"
                :value="opt.value"
              >{{ opt.label }}</option>
            </select>
            <p v-if="fieldError('study_program_id')" class="form-error">{{ fieldError('study_program_id') }}</p>
          </div>

          <!-- Angkatan -->
          <div>
            <label class="form-label">Angkatan <span class="text-red-500">*</span></label>
            <select
              v-model="form.graduation_year_id"
              class="form-input"
              :class="{ 'border-red-400': fieldError('graduation_year_id') }"
            >
              <option value="">Pilih Angkatan</option>
              <option
                v-for="opt in alumniStore.graduationYearOptions"
                :key="opt.value"
                :value="opt.value"
              >{{ opt.label }}</option>
            </select>
            <p v-if="fieldError('graduation_year_id')" class="form-error">{{ fieldError('graduation_year_id') }}</p>
          </div>

          <!-- IPK -->
          <div>
            <label class="form-label">IPK</label>
            <input
              v-model="form.gpa"
              type="number"
              step="0.01"
              min="0"
              max="4"
              class="form-input"
              :class="{ 'border-red-400': fieldError('gpa') }"
              placeholder="0.00 – 4.00"
            />
            <p v-if="fieldError('gpa')" class="form-error">{{ fieldError('gpa') }}</p>
          </div>

          <!-- Predikat Kelulusan -->
          <div>
            <label class="form-label">Predikat Kelulusan</label>
            <select
              v-model="form.graduation_predicate"
              class="form-input"
              :class="{ 'border-red-400': fieldError('graduation_predicate') }"
            >
              <option value="">Pilih Predikat</option>
              <option v-for="opt in PREDICATE_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="fieldError('graduation_predicate')" class="form-error">{{ fieldError('graduation_predicate') }}</p>
          </div>

          <!-- Judul Skripsi/Tesis -->
          <div class="sm:col-span-2">
            <label class="form-label">Judul Skripsi / Tesis</label>
            <textarea
              v-model="form.thesis_title"
              class="form-input resize-none"
              :class="{ 'border-red-400': fieldError('thesis_title') }"
              rows="3"
              placeholder="Judul tugas akhir (opsional)"
            />
            <p v-if="fieldError('thesis_title')" class="form-error">{{ fieldError('thesis_title') }}</p>
          </div>
        </div>
      </div>

      <!-- ── Tab: Alamat ────────────────────────────────────────────────────── -->
      <div v-show="activeTab === 'alamat'" class="card mt-4 p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <!-- Jalan -->
          <div class="sm:col-span-2">
            <label class="form-label">Jalan / Nama Jalan</label>
            <input
              v-model="form.address_street"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_street') }"
              placeholder="Jl. Contoh No. 1"
            />
            <p v-if="fieldError('address_street')" class="form-error">{{ fieldError('address_street') }}</p>
          </div>

          <!-- Desa/Kelurahan -->
          <div>
            <label class="form-label">Desa / Kelurahan</label>
            <input
              v-model="form.address_village"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_village') }"
              placeholder="Nama desa/kelurahan"
            />
            <p v-if="fieldError('address_village')" class="form-error">{{ fieldError('address_village') }}</p>
          </div>

          <!-- Kecamatan -->
          <div>
            <label class="form-label">Kecamatan</label>
            <input
              v-model="form.address_district"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_district') }"
              placeholder="Nama kecamatan"
            />
            <p v-if="fieldError('address_district')" class="form-error">{{ fieldError('address_district') }}</p>
          </div>

          <!-- Kota/Kabupaten -->
          <div>
            <label class="form-label">Kota / Kabupaten</label>
            <input
              v-model="form.address_city"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_city') }"
              placeholder="Nama kota/kabupaten"
            />
            <p v-if="fieldError('address_city')" class="form-error">{{ fieldError('address_city') }}</p>
          </div>

          <!-- Provinsi -->
          <div>
            <label class="form-label">Provinsi</label>
            <input
              v-model="form.address_province"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_province') }"
              placeholder="Nama provinsi"
            />
            <p v-if="fieldError('address_province')" class="form-error">{{ fieldError('address_province') }}</p>
          </div>

          <!-- Kode Pos -->
          <div>
            <label class="form-label">Kode Pos</label>
            <input
              v-model="form.address_postal_code"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_postal_code') }"
              maxlength="10"
              placeholder="12345"
            />
            <p v-if="fieldError('address_postal_code')" class="form-error">{{ fieldError('address_postal_code') }}</p>
          </div>

          <!-- Koordinat -->
          <div>
            <label class="form-label">Latitude</label>
            <input
              v-model="form.address_latitude"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_latitude') }"
              placeholder="-8.123456"
            />
            <p v-if="fieldError('address_latitude')" class="form-error">{{ fieldError('address_latitude') }}</p>
          </div>

          <div>
            <label class="form-label">Longitude</label>
            <input
              v-model="form.address_longitude"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address_longitude') }"
              placeholder="113.123456"
            />
            <p v-if="fieldError('address_longitude')" class="form-error">{{ fieldError('address_longitude') }}</p>
          </div>

          <div class="sm:col-span-2">
            <button
              type="button"
              class="btn-secondary flex items-center gap-2"
              @click="fillCurrentLocation"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Gunakan Lokasi Saat Ini
            </button>
          </div>
        </div>
      </div>

      <!-- ── Tab: Kontak ─────────────────────────────────────────────────────── -->
      <div v-show="activeTab === 'kontak'" class="card mt-4 p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <!-- No. HP -->
          <div>
            <label class="form-label">Nomor HP / WhatsApp <span class="text-red-500">*</span></label>
            <input
              v-model="form.phone"
              type="tel"
              class="form-input"
              :class="{ 'border-red-400': fieldError('phone') }"
              placeholder="08xxxxxxxxxx"
            />
            <p v-if="fieldError('phone')" class="form-error">{{ fieldError('phone') }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="form-label">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="form-input"
              :class="{ 'border-red-400': fieldError('email') }"
              placeholder="alumni@email.com"
            />
            <p v-if="fieldError('email')" class="form-error">{{ fieldError('email') }}</p>
          </div>

          <!-- LinkedIn -->
          <div class="sm:col-span-2">
            <label class="form-label">URL LinkedIn</label>
            <input
              v-model="form.linkedin_url"
              type="url"
              class="form-input"
              :class="{ 'border-red-400': fieldError('linkedin_url') }"
              placeholder="https://linkedin.com/in/username"
            />
            <p v-if="fieldError('linkedin_url')" class="form-error">{{ fieldError('linkedin_url') }}</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-6 flex justify-end gap-3">
        <button
          type="button"
          class="btn-secondary"
          @click="router.push({ name: 'admin.alumni.index' })"
        >
          Batal
        </button>
        <button
          type="submit"
          class="btn-primary"
          :disabled="saving"
        >
          <svg v-if="saving" class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Alumni' : 'Simpan Alumni') }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.btn-primary   { @apply inline-flex items-center bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card          { @apply bg-white rounded-xl shadow-sm border border-gray-200; }
.form-label    { @apply block text-sm font-medium text-gray-700 mb-1.5; }
.form-input    { @apply w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition-colors; }
.form-error    { @apply mt-1 text-xs text-red-500; }
</style>
