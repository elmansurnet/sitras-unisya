<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useMasterDataStore } from '@/stores/masterData'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const employerStore = useEmployerStore()
const masterDataStore = useMasterDataStore()
const { toast } = useToast()

const isEdit = computed(() => !!route.params.id)
const employerId = computed(() => route.params.id)
const saving = ref(false)
const errors = ref({})

const PHONE_PREFIX = '+62'
const phoneLocal = ref('')

const form = ref({
  company_name:      '',
  industry_sector_id: '',
  company_size:      '',
  website_url:       '',
  address:           '',
  city:              '',
  province:          '',
  pic_name:          '',
  pic_position:      '',
  pic_phone:         '',
  pic_email:         '',
})

const COMPANY_SIZE_OPTIONS = [
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

  form.value.pic_phone = normalizePhone(phoneLocal.value)

  if (!form.value.pic_phone) {
    errors.value.pic_phone = ['Nomor HP PIC wajib diisi']
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

      <div class="card p-6 mb-5">
        <h2 class="section-heading">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          Data Perusahaan
        </h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

          <div class="sm:col-span-2">
            <label class="form-label">Nama Perusahaan / Instansi <span class="text-red-500">*</span></label>
            <inp