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
const errors = ref({})

const form = ref({
  nik: '', full_name: '', gender: '', birthplace: '', birthdate: '', nim: '',
  study_program_id: '', graduation_year_id: '', gpa: '', thesis_title: '', graduation_predicate: '',
  address_street: '', address_village: '', address_district: '', address_city: '', address_province: '', address_postal_code: '',
  address_latitude: '', address_longitude: '',
  phone: '', email: '', linkedin_url: '',
})

onMounted(async () => {
  try {
    await alumniStore.fetchMasterData()
    if (isEdit.value) {
      await alumniStore.fetchDetail(alumniId.value)
      if (alumniStore.current) {
        Object.keys(form.value).forEach((key) => {
          if (alumniStore.current[key] !== undefined) form.value[key] = alumniStore.current[key] ?? ''
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
      form.value.address_latitude = String(position.coords.latitude)
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
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Alumni' : 'Tambah Alumni Baru' }}</h1>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div class="card p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="form-label">Nama Lengkap</label>
          <input v-model="form.full_name" class="form-input" name="full_name" />
        </div>
        <div>
          <label class="form-label">NIM</label>
          <input v-model="form.nim" class="form-input" name="nim" />
        </div>
        <div>
          <label class="form-label">Program Studi</label>
          <select v-model="form.study_program_id" class="form-input" name="study_program_id">
            <option value="">Pilih Program Studi</option>
            <option v-for="opt in alumniStore.studyProgramOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Angkatan</label>
          <select v-model="form.graduation_year_id" class="form-input" name="graduation_year_id">
            <option value="">Pilih Angkatan</option>
            <option v-for="opt in alumniStore.graduationYearOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Latitude</label>
          <input v-model="form.address_latitude" class="form-input" name="address_latitude" placeholder="-8.123456" />
        </div>
        <div>
          <label class="form-label">Longitude</label>
          <input v-model="form.address_longitude" class="form-input" name="address_longitude" placeholder="113.123456" />
        </div>
        <div class="md:col-span-2">
          <button type="button" class="btn-secondary" @click="fillCurrentLocation">Gunakan Lokasi Saat Ini</button>
        </div>
      </div>
      <div class="flex justify-end gap-3">
        <button type="button" class="btn-secondary" @click="router.push({ name: 'admin.alumni.index' })">Batal</button>
        <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
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
</style>
