<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const alumniStore = useAlumniStore()
const authStore = useAuthStore()
const { showToast } = useToast()

const saving = ref(false)
const photoFile = ref(null)
const photoPreview = ref(null)
const errors = ref({})

const alumni = computed(() => authStore.user?.alumni)

const form = ref({
  birthplace: '',
  birthdate: '',
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

onMounted(async () => {
  await alumniStore.fetchMyProfile()
  const a = authStore.user?.alumni
  if (a) {
    Object.keys(form.value).forEach(key => {
      if (a[key] !== undefined) form.value[key] = a[key] ?? ''
    })
  }
})

function handlePhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

async function handlePhotoUpload() {
  if (!photoFile.value) return
  try {
    await alumniStore.uploadPhoto(photoFile.value)
    showToast('Foto profil berhasil diperbarui.', 'success')
    photoFile.value = null
  } catch {
    showToast('Gagal mengunggah foto.', 'error')
  }
}

async function handleSubmit() {
  errors.value = {}
  saving.value = true
  try {
    await alumniStore.updateMyProfile(form.value)
    if (photoFile.value) await handlePhotoUpload()
    showToast('Profil berhasil diperbarui.', 'success')
    router.push({ name: 'alumni.profile' })
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
</script>

<template>
  <div class="max-w-3xl mx-auto py-6 px-4">
    <div class="flex items-center gap-3 mb-6">
      <button class="text-gray-500 hover:text-gray-700 p-1 rounded" @click="router.push({ name: 'alumni.profile' })">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-2xl font-bold text-gray-900">Edit Profil</h1>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Foto Profil -->
      <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Foto Profil</h3>
        <div class="flex items-center gap-4">
          <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
            <img
              v-if="photoPreview"
              :src="photoPreview"
              alt="Preview"
              class="w-full h-full object-cover"
            />
            <img
              v-else-if="alumni?.photo_url"
              :src="alumni.photo_url"
              alt="Foto"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-2xl font-bold text-primary-700 bg-primary-100">
              {{ alumni?.full_name?.charAt(0)?.toUpperCase() }}
            </div>
          </div>
          <label class="btn-secondary cursor-pointer">
            <input type="file" accept="image/jpeg,image/png" class="hidden" @change="handlePhotoChange" />
            Ganti Foto
          </label>
          <p class="text-xs text-gray-400">JPEG/PNG, maks. 2MB</p>
        </div>
      </div>

      <!-- Data Diri -->
      <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Data Diri</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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

      <!-- Alamat -->
      <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Alamat</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="md:col-span-2">
            <label class="form-label">Jalan / Alamat</label>
            <textarea v-model="form.address_street" rows="2" class="form-input" />
          </div>
          <div>
            <label class="form-label">Kelurahan/Desa</label>
            <input v-model="form.address_village" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Kecamatan</label>
            <input v-model="form.address_district" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Kota/Kabupaten</label>
            <input v-model="form.address_city" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Provinsi</label>
            <input v-model="form.address_province" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Kode Pos</label>
            <input v-model="form.address_postal_code" type="text" class="form-input" maxlength="5" />
          </div>
        </div>
      </div>

      <!-- Kontak -->
      <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Kontak</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="form-label">No. WhatsApp</label>
            <input v-model="form.phone" type="text" class="form-input" placeholder="628xxxxxxxxxx" />
            <p v-if="errors.phone" class="form-error">{{ errors.phone[0] }}</p>
          </div>
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" class="form-input" />
            <p v-if="errors.email" class="form-error">{{ errors.email[0] }}</p>
          </div>
          <div class="md:col-span-2">
            <label class="form-label">LinkedIn URL</label>
            <input v-model="form.linkedin_url" type="url" class="form-input" placeholder="https://linkedin.com/in/..." />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <button type="button" class="btn-secondary" @click="router.push({ name: 'alumni.profile' })">Batal</button>
        <button type="submit" class="btn-primary flex items-center gap-2" :disabled="saving">
          <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
      </div>
    </form>
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
