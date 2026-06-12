<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <button
          @click="$router.back()"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-1"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
          Kembali
        </button>
        <h1 class="text-xl font-semibold text-gray-900">{{ isEdit ? 'Edit Employer' : 'Tambah Employer' }}</h1>
        <p class="mt-1 text-sm text-gray-500">
          {{ isEdit ? 'Perbarui informasi data employer.' : 'Tambahkan data perusahaan pemberi kerja alumni baru.' }}
        </p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Informasi Perusahaan -->
      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Informasi Perusahaan</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Nama Perusahaan <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.company_name"
              type="text"
              required
              placeholder="PT. Contoh Perusahaan"
              :class="['w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1', errors.company_name ? 'border-red-400 focus:border-red-400 focus:ring-red-400' : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500']"
            />
            <p v-if="errors.company_name" class="mt-1 text-xs text-red-500">{{ errors.company_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Perusahaan</label>
            <select v-model="form.company_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
              <option value="">Pilih jenis...</option>
              <option value="swasta">Swasta</option>
              <option value="bumn">BUMN</option>
              <option value="pemerintah">Pemerintah</option>
              <option value="ngo">NGO</option>
              <option value="startup">Startup</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Skala Perusahaan</label>
            <select v-model="form.company_scale" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
              <option value="">Pilih skala...</option>
              <option value="mikro">Mikro</option>
              <option value="kecil">Kecil</option>
              <option value="menengah">Menengah</option>
              <option value="besar">Besar</option>
              <option value="multinasional">Multinasional</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sektor Industri</label>
            <input
              v-model="form.industry_sector"
              type="text"
              placeholder="Teknologi Informasi, Manufaktur, Perbankan..."
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
            <input v-model="form.phone" type="text" placeholder="021-XXXXXXXX" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Perusahaan</label>
            <input v-model="form.email" type="email" placeholder="info@perusahaan.com" :class="['w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1', errors.email ? 'border-red-400 focus:border-red-400 focus:ring-red-400' : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500']" />
            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
            <input v-model="form.website" type="url" placeholder="https://www.perusahaan.com" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
        </div>
      </section>

      <!-- Alamat -->
      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Alamat Perusahaan</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
            <textarea v-model="form.address_street" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" placeholder="Jl. Nama Jalan No. XX..." />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
            <input v-model="form.address_city" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
            <input v-model="form.address_province" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Negara</label>
            <input v-model="form.address_country" type="text" value="Indonesia" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
        </div>
      </section>

      <!-- PIC -->
      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Person in Charge (PIC)</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama PIC</label>
            <input v-model="form.contact_person_name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan PIC</label>
            <input v-model="form.contact_person_position" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC</label>
            <input v-model="form.contact_person_email" type="email" :class="['w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1', errors.contact_person_email ? 'border-red-400 focus:border-red-400 focus:ring-red-400' : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500']" />
            <p v-if="errors.contact_person_email" class="mt-1 text-xs text-red-500">{{ errors.contact_person_email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">WA PIC</label>
            <input v-model="form.contact_person_phone" type="text" placeholder="08XXXXXXXXXX" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
          </div>
        </div>
      </section>

      <!-- Catatan -->
      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Catatan Internal</h2>
        <textarea
          v-model="form.notes"
          rows="3"
          placeholder="Catatan tambahan untuk admin..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        />
      </section>

      <!-- Submit -->
      <div class="flex justify-end gap-3">
        <button type="button" @click="$router.back()" class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Batal
        </button>
        <button
          type="submit"
          :disabled="store.loading"
          class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
        >
          <svg v-if="store.loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ store.loading ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Employer') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useToast } from '@/composables/useToast'

const route   = useRoute()
const router  = useRouter()
const store   = useEmployerStore()
const { toast } = useToast()

const isEdit  = computed(() => !!route.params.id)
const errors  = reactive({})

const form = reactive({
  company_name:            '',
  company_type:            '',
  company_scale:           '',
  industry_sector:         '',
  phone:                   '',
  email:                   '',
  website:                 '',
  address_street:          '',
  address_city:            '',
  address_province:        '',
  address_country:         'Indonesia',
  contact_person_name:     '',
  contact_person_position: '',
  contact_person_email:    '',
  contact_person_phone:    '',
  notes:                   '',
})

function populateForm(employer) {
  Object.keys(form).forEach((key) => {
    if (employer[key] !== undefined && employer[key] !== null) {
      form[key] = employer[key]
    }
  })
}

function clearErrors() { Object.keys(errors).forEach((k) => delete errors[k]) }

function handleApiErrors(err) {
  const apiErrors = err.response?.data?.errors
  if (apiErrors) {
    Object.assign(errors, apiErrors)
  } else {
    toast(err.response?.data?.message ?? 'Terjadi kesalahan.', 'error')
  }
}

async function handleSubmit() {
  clearErrors()
  try {
    const payload = { ...form }
    // Hapus string kosong supaya tidak override existing nullable values
    Object.keys(payload).forEach((k) => { if (payload[k] === '') payload[k] = null })

    if (isEdit.value) {
      await store.update(route.params.id, payload)
      toast('Data employer berhasil diperbarui.', 'success')
      router.push({ name: 'admin.employers.show', params: { id: route.params.id } })
    } else {
      const created = await store.create(payload)
      toast('Employer berhasil ditambahkan.', 'success')
      router.push({ name: 'admin.employers.show', params: { id: created.id } })
    }
  } catch (err) {
    handleApiErrors(err)
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const employer = await store.fetchById(route.params.id)
    if (employer) populateForm(employer)
  }
})
</script>
