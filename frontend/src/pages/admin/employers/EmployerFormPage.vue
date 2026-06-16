<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useMasterDataStore } from '@/stores/masterData'
// BUG #4c FIX: destructure { toast } bukan langsung const toast = useToast()
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const employerStore = useEmployerStore()
const masterDataStore = useMasterDataStore()
// BUG #4c FIX: { toast } — toast adalah object dengan .success() .error()
const { toast } = useToast()

const isEdit = computed(() => !!route.params.id)
const employerId = computed(() => route.params.id)
const saving = ref(false)
const errors = ref({})

// BUG #4b FIX: phone picker — prefix terpisah, digabung saat submit
const PHONE_PREFIX = '+62'
const phoneLocal = ref('') // input user: 8xxxxxxxxx (tanpa 62)

const form = ref({
  company_name:      '',
  industry_sector_id: '',   // BUG #4a FIX: pakai FK ke industry_sectors
  company_size:      '',
  website_url:       '',
  address:           '',
  city:              '',
  province:          '',
  // PIC
  pic_name:          '',
  pic_position:      '',
  pic_phone:         '',    // disimpan: 62XXXXXXXX (tanpa +)
  pic_email:         '',
})

const COMPANY_SIZE_OPTIONS = [
  { value: 'micro',  label: 'Mikro (< 10 karyawan)' },
  { value: 'small',  label: 'Kecil (10–49 karyawan)' },
  { value: 'medium', label: 'Menengah (50–249 karyawan)' },
  { value: 'large',  label: 'Besar (≥ 250 karyawan)' },
]

// BUG #4b FIX: normalise 08xx → 628xx untuk WA Gateway UNISYA
function normalizePhone(localNumber) {
  const digits = localNumber.replace(/\D/g, '')
  if (!digits) return ''
  // Jika user input dimulai 0 (misal 08xxx), hapus 0 terdepan lalu prepend 62
  if (digits.startsWith('0')) return '62' + digits.slice(1)
  // Jika sudah dimulai 8 (tanpa 0), langsung prepend 62
  if (digits.startsWith('8')) return '62' + digits
  // Jika sudah ada 62, biarkan
  if (digits.startsWith('62')) return digits
  return '62' + digits
}

onMounted(async () => {
  // BUG #4a FIX: fetch industry sectors dari API
  await masterDataStore.fetchIndustrySectors()

  if (isEdit.value) {
    await employerStore.fetchDetail(employerId.value)
    if (employerStore.current) {
      const e = employerStore.current
      form.value.company_name       = e.company_name ?? ''
      form.value.industry_sector_id = e.industry_sector_id ?? ''
      form.value.company_size       = e.company_size ?? ''
      form.value.website_url        = e.website_url ?? ''
      form.value.address            = e.address ?? ''
      form.value.city               = e.city ?? ''
      form.value.province           = e.province ?? ''
      form.value.pic_name           = e.pic_name ?? ''
      form.value.pic_position       = e.pic_position ?? ''
      form.value.pic_email          = e.pic_email ?? ''
      // BUG #4b FIX: pisahkan 62xxx → local (8xxx) untuk ditampilkan di input
      if (e.pic_phone) {
        const digits = e.pic_phone.replace(/\D/g, '')
        phoneLocal.value = digits.startsWith('62') ? digits.slice(2) : digits
      }
    }
  }
})

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  // BUG #4b FIX: gabungkan prefix + local → format 62XXXXXXXX
  form.value.pic_phone = normalizePhone(phoneLocal.value)

  if (!form.value.pic_phone) {
    errors.value.pic_phone = ['Nomor HP PIC wajib diisi']
    saving.value = false
    return
  }

  try {
    if (isEdit.value) {
      await employerStore.update(employerId.value, form.value)
      // BUG #4c FIX: toast.success() bukan toast()
      toast.success('Data employer berhasil diperbarui.')
    } else {
      await employerStore.create(form.value)
      toast.success('Employer berhasil ditambahkan.')
    }
    router.push({ name: 'admin.employers.index' })
  } catch (err) {
    errors.value = err.response?.data?.errors ?? {}
    // BUG #4c FIX: toast.error() bukan toast()
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan data employer.')
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
        aria-label="Kembali"
        @click="router.push({ name: 'admin.employers.index' })"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <h1 class="text-xl font-semibold text-gray-900">
        {{ isEdit ? 'Edit Employer' : 'Tambah Employer Baru' }}
      </h1>
    </div>

    <form @submit.prevent="handleSubmit" novalidate>

      <!-- ── BUG #4b FIX: Section label "Data Perusahaan" ─────────────────── -->
      <div class="card p-6 mb-5">
        <h2 class="section-heading">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          Data Perusahaan
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <!-- Nama Perusahaan -->
          <div class="sm:col-span-2">
            <label class="form-label">Nama Perusahaan / Instansi <span class="text-red-500">*</span></label>
            <input
              v-model="form.company_name"
              class="form-input"
              :class="{ 'border-red-400': fieldError('company_name') }"
              placeholder="PT. Contoh Indonesia"
            />
            <p v-if="fieldError('company_name')" class="form-error">{{ fieldError('company_name') }}</p>
          </div>

          <!-- BUG #4a FIX: Sektor Industri → dropdown dari API, bukan input manual -->
          <div>
            <label class="form-label">Sektor Industri <span class="text-red-500">*</span></label>
            <select
              v-model="form.industry_sector_id"
              class="form-input"
              :class="{ 'border-red-400': fieldError('industry_sector_id') }"
              :disabled="masterDataStore.loadingIndustrySectors"
            >
              <option value="">
                {{ masterDataStore.loadingIndustrySectors ? 'Memuat...' : 'Pilih Sektor Industri' }}
              </option>
              <option
                v-for="opt in masterDataStore.industrySectorOptions"
                :key="opt.value"
                :value="opt.value"
              >{{ opt.label }}</option>
            </select>
            <p v-if="fieldError('industry_sector_id')" class="form-error">{{ fieldError('industry_sector_id') }}</p>
          </div>

          <!-- Ukuran Perusahaan -->
          <div>
            <label class="form-label">Ukuran Perusahaan</label>
            <select
              v-model="form.company_size"
              class="form-input"
              :class="{ 'border-red-400': fieldError('company_size') }"
            >
              <option value="">Pilih Ukuran</option>
              <option v-for="opt in COMPANY_SIZE_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
            <p v-if="fieldError('company_size')" class="form-error">{{ fieldError('company_size') }}</p>
          </div>

          <!-- Website -->
          <div>
            <label class="form-label">Website</label>
            <input
              v-model="form.website_url"
              type="url"
              class="form-input"
              :class="{ 'border-red-400': fieldError('website_url') }"
              placeholder="https://perusahaan.com"
            />
            <p v-if="fieldError('website_url')" class="form-error">{{ fieldError('website_url') }}</p>
          </div>

          <!-- Alamat -->
          <div class="sm:col-span-2">
            <label class="form-label">Alamat</label>
            <input
              v-model="form.address"
              class="form-input"
              :class="{ 'border-red-400': fieldError('address') }"
              placeholder="Jl. Contoh No. 1"
            />
            <p v-if="fieldError('address')" class="form-error">{{ fieldError('address') }}</p>
          </div>

          <!-- Kota -->
          <div>
            <label class="form-label">Kota / Kabupaten</label>
            <input
              v-model="form.city"
              class="form-input"
              :class="{ 'border-red-400': fieldError('city') }"
              placeholder="Lumajang"
            />
            <p v-if="fieldError('city')" class="form-error">{{ fieldError('city') }}</p>
          </div>

          <!-- Provinsi -->
          <div>
            <label class="form-label">Provinsi</label>
            <input
              v-model="form.province"
              class="form-input"
              :class="{ 'border-red-400': fieldError('province') }"
              placeholder="Jawa Timur"
            />
            <p v-if="fieldError('province')" class="form-error">{{ fieldError('province') }}</p>
          </div>
        </div>
      </div>

      <!-- ── BUG #4b FIX: Section label "Data PIC" + pemisah visual ─────────── -->
      <div class="card p-6 mb-5">
        <h2 class="section-heading">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Data PIC (Person In Charge)
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <!-- Nama PIC -->
          <div>
            <label class="form-label">Nama PIC <span class="text-red-500">*</span></label>
            <input
              v-model="form.pic_name"
              class="form-input"
              :class="{ 'border-red-400': fieldError('pic_name') }"
              placeholder="Nama lengkap PIC"
            />
            <p v-if="fieldError('pic_name')" class="form-error">{{ fieldError('pic_name') }}</p>
          </div>

          <!-- Jabatan PIC -->
          <div>
            <label class="form-label">Jabatan PIC</label>
            <input
              v-model="form.pic_position"
              class="form-input"
              :class="{ 'border-red-400': fieldError('pic_position') }"
              placeholder="HRD Manager"
            />
            <p v-if="fieldError('pic_position')" class="form-error">{{ fieldError('pic_position') }}</p>
          </div>

          <!-- BUG #4b FIX: Phone picker dengan prefix +62 -->
          <div>
            <label class="form-label">No. HP / WhatsApp PIC <span class="text-red-500">*</span></label>
            <div class="flex">
              <!-- Prefix tidak bisa diubah — WA Gateway selalu 62xxx -->
              <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 select-none">
                +62
              </span>
              <input
                v-model="phoneLocal"
                type="tel"
                class="form-input rounded-l-none"
                :class="{ 'border-red-400': fieldError('pic_phone') }"
                placeholder="8xxxxxxxxxx"
                maxlength="13"
                @input="phoneLocal = phoneLocal.replace(/\D/g, '').replace(/^0+/, '')"
              />
            </div>
            <p class="mt-1 text-xs text-gray-400">Contoh: 81234567890 → disimpan sebagai 6281234567890</p>
            <p v-if="fieldError('pic_phone')" class="form-error">{{ fieldError('pic_phone') }}</p>
          </div>

          <!-- Email PIC -->
          <div>
            <label class="form-label">Email PIC</label>
            <input
              v-model="form.pic_email"
              type="email"
              class="form-input"
              :class="{ 'border-red-400': fieldError('pic_email') }"
              placeholder="pic@perusahaan.com"
            />
            <p v-if="fieldError('pic_email')" class="form-error">{{ fieldError('pic_email') }}</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="btn-secondary"
          @click="router.push({ name: 'admin.employers.index' })"
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
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Employer' : 'Simpan Employer') }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.section-heading { @apply flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4; }
.btn-primary     { @apply inline-flex items-center bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
.btn-secondary   { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card            { @apply bg-white rounded-xl shadow-sm border border-gray-200; }
.form-label      { @apply block text-sm font-medium text-gray-700 mb-1.5; }
.form-input      { @apply w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition-colors; }
.form-error      { @apply mt-1 text-xs text-red-500; }
</style>