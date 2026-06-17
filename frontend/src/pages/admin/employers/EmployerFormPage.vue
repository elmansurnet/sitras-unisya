<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const employerStore = useEmployerStore()
const { toast } = useToast()

const isEdit = computed(() => !!route.params.id)
const employerId = computed(() => route.params.id)
const saving = ref(false)
const errors = ref({})

const phoneLocal = ref('')

const form = ref({
  company_name:             '',
  company_type:             '',
  industry_sector:          '',
  company_scale:            '',
  website:                  '',
  address_street:           '',
  address_city:             '',
  address_province:         '',
  address_country:          '',
  phone:                    '',
  email:                    '',
  contact_person_name:      '',
  contact_person_position:  '',
  contact_person_phone:     '',
  contact_person_email:     '',
  notes:                    '',
})

const COMPANY_TYPE_OPTIONS = [
  { value: 'swasta',     label: 'Swasta' },
  { value: 'bumn',       label: 'BUMN' },
  { value: 'pemerintah', label: 'Pemerintah' },
  { value: 'ngo',        label: 'NGO / Yayasan' },
  { value: 'startup',    label: 'Startup' },
  { value: 'lainnya',    label: 'Lainnya' },
]

const COMPANY_SCALE_OPTIONS = [
  { value: 'micro',  label: 'Mikro (< 10 karyawan)' },
  { value: 'small',  label: 'Kecil (10–49 karyawan)' },
  { value: 'medium', label: 'Menengah (50–249 karyawan)' },
  { value: 'large',  label: 'Besar (≥ 250 karyawan)' },
]

function normalizePhone(localNumber) {
  const digits = localNumber.replace(/\D/g, '')
  if (!digits) return ''
  if (digits.startsWith('0')) return '62' + digits.slice(1)
  if (digits.startsWith('8')) return '62' + digits
  if (digits.startsWith('62')) return digits
  return '62' + digits
}

onMounted(async () => {
  if (isEdit.value) {
    await employerStore.fetchById(employerId.value)
    if (employerStore.current) {
      const e = employerStore.current
      form.value.company_name            = e.company_name            ?? ''
      form.value.company_type            = e.company_type            ?? ''
      form.value.industry_sector         = e.industry_sector         ?? ''
      form.value.company_scale           = e.company_scale           ?? ''
      form.value.website                 = e.website                 ?? ''
      form.value.address_street          = e.address_street          ?? ''
      form.value.address_city            = e.address_city            ?? ''
      form.value.address_province        = e.address_province        ?? ''
      form.value.address_country         = e.address_country         ?? ''
      form.value.phone                   = e.phone                   ?? ''
      form.value.email                   = e.email                   ?? ''
      form.value.contact_person_name     = e.contact_person_name     ?? ''
      form.value.contact_person_position = e.contact_person_position ?? ''
      form.value.contact_person_email    = e.contact_person_email    ?? ''
      form.value.notes                   = e.notes                   ?? ''
      if (e.contact_person_phone) {
        const digits = e.contact_person_phone.replace(/\D/g, '')
        phoneLocal.value = digits.startsWith('62') ? digits.slice(2) : digits
      }
    }
  }
})

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  form.value.contact_person_phone = normalizePhone(phoneLocal.value)

  if (!form.value.contact_person_phone) {
    errors.value.contact_person_phone = ['Nomor HP PIC wajib diisi']
    saving.value = false
    return
  }

  try {
    if (isEdit.value) {
      await employerStore.update(employerId.value, form.value)
      toast.success('Data employer berhasil diperbarui.')
    } else {
      await employerStore.create(form.value)
      toast.success('Employer berhasil ditambahkan.')
    }
    router.push({ name: 'admin.employer.index' })
  } catch (err) {
    errors.value = err.response?.data?.errors ?? {}
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
        @click="router.push({ name: 'admin.employer.index' })"
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

      <!-- Data Perusahaan -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          Data Perusahaan
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Perusahaan / Instansi <span class="text-red-500">*</span></label>
            <input
              v-model="form.company_name"
              type="text"
              class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :class="fieldError('company_name') ? 'border-red-400' : 'border-gray-300'"
              placeholder="PT. Contoh Maju Bersama"
            />
            <p v-if="fieldError('company_name')" class="mt-1 text-xs text-red-500">{{ fieldError('company_name') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Tipe Perusahaan</label>
            <select
              v-model="form.company_type"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
              <option value="">-- Pilih tipe --</option>
              <option v-for="opt in COMPANY_TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <p v-if="fieldError('company_type')" class="mt-1 text-xs text-red-500">{{ fieldError('company_type') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Sektor Industri</label>
            <input
              v-model="form.industry_sector"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="Teknologi, Pendidikan, Kesehatan, dst."
            />
            <p v-if="fieldError('industry_sector')" class="mt-1 text-xs text-red-500">{{ fieldError('industry_sector') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Skala Perusahaan</label>
            <select
              v-model="form.company_scale"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
              <option value="">-- Pilih skala --</option>
              <option v-for="opt in COMPANY_SCALE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <p v-if="fieldError('company_scale')" class="mt-1 text-xs text-red-500">{{ fieldError('company_scale') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Website</label>
            <input
              v-model="form.website"
              type="url"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="https://perusahaan.co.id"
            />
            <p v-if="fieldError('website')" class="mt-1 text-xs text-red-500">{{ fieldError('website') }}</p>
          </div>

        </div>
      </div>

      <!-- Alamat -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Alamat
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Jalan / Alamat Lengkap</label>
            <input
              v-model="form.address_street"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="Jl. Contoh No. 1"
            />
            <p v-if="fieldError('address_street')" class="mt-1 text-xs text-red-500">{{ fieldError('address_street') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Kota</label>
            <input
              v-model="form.address_city"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="Surabaya"
            />
            <p v-if="fieldError('address_city')" class="mt-1 text-xs text-red-500">{{ fieldError('address_city') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Provinsi</label>
            <input
              v-model="form.address_province"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="Jawa Timur"
            />
            <p v-if="fieldError('address_province')" class="mt-1 text-xs text-red-500">{{ fieldError('address_province') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Negara</label>
            <input
              v-model="form.address_country"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="Indonesia"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Telepon Perusahaan</label>
            <input
              v-model="form.phone"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="031xxxxxxx"
            />
            <p v-if="fieldError('phone')" class="mt-1 text-xs text-red-500">{{ fieldError('phone') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email Perusahaan</label>
            <input
              v-model="form.email"
              type="email"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="info@perusahaan.co.id"
            />
            <p v-if="fieldError('email')" class="mt-1 text-xs text-red-500">{{ fieldError('email') }}</p>
          </div>

        </div>
      </div>

      <!-- PIC / Kontak Person -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Kontak Person (PIC)
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Nama PIC <span class="text-red-500">*</span></label>
            <input
              v-model="form.contact_person_name"
              type="text"
              class="block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :class="fieldError('contact_person_name') ? 'border-red-400' : 'border-gray-300'"
              placeholder="Budi Santoso"
            />
            <p v-if="fieldError('contact_person_name')" class="mt-1 text-xs text-red-500">{{ fieldError('contact_person_name') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Jabatan PIC</label>
            <input
              v-model="form.contact_person_position"
              type="text"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="HRD Manager"
            />
            <p v-if="fieldError('contact_person_position')" class="mt-1 text-xs text-red-500">{{ fieldError('contact_person_position') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">WhatsApp PIC <span class="text-red-500">*</span></label>
            <div class="flex">
              <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">+62</span>
              <input
                v-model="phoneLocal"
                type="tel"
                class="block w-full rounded-r-lg border px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                :class="fieldError('contact_person_phone') ? 'border-red-400' : 'border-gray-300'"
                placeholder="81234567890"
              />
            </div>
            <p v-if="fieldError('contact_person_phone')" class="mt-1 text-xs text-red-500">{{ fieldError('contact_person_phone') }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Email PIC</label>
            <input
              v-model="form.contact_person_email"
              type="email"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              placeholder="pic@perusahaan.co.id"
            />
            <p v-if="fieldError('contact_person_email')" class="mt-1 text-xs text-red-500">{{ fieldError('contact_person_email') }}</p>
          </div>

        </div>
      </div>

      <!-- Catatan Internal -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
          Catatan Internal
        </h2>
        <textarea
          v-model="form.notes"
          rows="3"
          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          placeholder="Catatan internal mengenai employer ini (tidak tampil ke publik)"
        />
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <button
          type="button"
          @click="router.push({ name: 'admin.employer.index' })"
          class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Batal
        </button>
        <button
          type="submit"
          :disabled="saving"
          class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60"
        >
          <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Employer') }}
        </button>
      </div>

    </form>
  </div>
</template>
