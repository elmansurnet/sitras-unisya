<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { toast }   = useToast()
const { confirm } = useConfirm()

// ─── State ────────────────────────────────────────────────────────────────────
const years        = ref([])
const loading      = ref(false)
const saving       = ref(false)
const filterStatus = ref('')
const showModal    = ref(false)
const editMode     = ref(false)

const form = ref({ id: null, year: '', is_active: true })
const formErrors = ref({})

const currentYear = new Date().getFullYear()

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  if (filterStatus.value === '') return years.value
  const active = filterStatus.value === '1'
  return years.value.filter((y) => y.is_active === active)
})

const sortedFiltered = computed(() =>
  [...filtered.value].sort((a, b) => b.year - a.year)
)

// ─── API ──────────────────────────────────────────────────────────────────────
async function fetchYears() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/graduation-years', { params: { per_page: 200 } })
    years.value = data.data
  } catch {
    toast.error('Gagal memuat data tahun wisuda.')
  } finally {
    loading.value = false
  }
}

async function save() {
  formErrors.value = {}
  saving.value = true
  try {
    const payload = {
      year:      Number(form.value.year),
      is_active: form.value.is_active,
    }
    if (editMode.value) {
      const { data } = await api.put(`/admin/graduation-years/${form.value.id}`, payload)
      const idx = years.value.findIndex((y) => y.id === form.value.id)
      if (idx !== -1) years.value[idx] = data.data
      toast.success('Tahun wisuda berhasil diperbarui.')
    } else {
      const { data } = await api.post('/admin/graduation-years', payload)
      years.value.push(data.data)
      toast.success('Tahun wisuda berhasil ditambahkan.')
    }
    closeModal()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors ?? {}
    } else {
      toast.error(err.response?.data?.message ?? 'Gagal menyimpan data.')
    }
  } finally {
    saving.value = false
  }
}

async function destroy(year) {
  const ok = await confirm(
    `Hapus tahun wisuda ${year.year}? Data alumni yang terhubung tidak akan terhapus.`
  )
  if (!ok) return
  try {
    await api.delete(`/admin/graduation-years/${year.id}`)
    years.value = years.value.filter((y) => y.id !== year.id)
    toast.success('Tahun wisuda berhasil dihapus.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus.')
  }
}

async function toggleActive(year) {
  try {
    const { data } = await api.put(`/admin/graduation-years/${year.id}`, {
      is_active: !year.is_active,
    })
    const idx = years.value.findIndex((y) => y.id === year.id)
    if (idx !== -1) years.value[idx] = data.data
    toast.success(
      `Tahun wisuda ${data.data.year} ${data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`
    )
  } catch {
    toast.error('Gagal mengubah status.')
  }
}

// ─── Modal ────────────────────────────────────────────────────────────────────
function openCreate() {
  form.value = { id: null, year: String(currentYear), is_active: true }
  formErrors.value = {}
  editMode.value  = false
  showModal.value = true
}

function openEdit(year) {
  form.value = { id: year.id, year: String(year.year), is_active: year.is_active }
  formErrors.value = {}
  editMode.value   = true
  showModal.value  = true
}

function closeModal() {
  showModal.value = false
  form.value = { id: null, year: '', is_active: true }
  formErrors.value = {}
}

onMounted(fetchYears)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Tahun Wisuda</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola daftar tahun wisuda untuk klasifikasi alumni.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Tahun
      </button>
    </div>

    <!-- Filter -->
    <div class="flex items-center gap-3">
      <select v-model="filterStatus" class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        <option value="">Semua Status</option>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
      <span class="text-sm text-gray-500">
        Menampilkan {{ sortedFiltered.length }} dari {{ years.length }} tahun wisuda
      </span>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      <div v-for="i in 10" :key="i" class="h-24 animate-pulse rounded-xl bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="sortedFiltered.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
      </svg>
      <p class="text-sm font-medium text-gray-500">{{ filterStatus ? 'Tidak ada tahun wisuda yang cocok.' : 'Belum ada data tahun wisuda.' }}</p>
      <button v-if="!filterStatus" @click="openCreate" class="mt-3 text-sm text-teal-600 hover:underline">Tambah tahun wisuda pertama</button>
    </div>

    <!-- Card Grid -->
    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      <div
        v-for="year in sortedFiltered"
        :key="year.id"
        class="group relative overflow-hidden rounded-xl border bg-white p-4 transition-shadow hover:shadow-md"
        :class="year.is_active ? 'border-teal-200' : 'border-gray-200 opacity-70'"
      >
        <p class="text-2xl font-bold tabular-nums" :class="year.is_active ? 'text-teal-700' : 'text-gray-400'">
          {{ year.year }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
          <span class="font-medium text-gray-700">{{ year.alumni_count ?? 0 }}</span> alumni
        </p>
        <div class="mt-3 flex items-center justify-between">
          <button
            @click="toggleActive(year)"
            :class="['relative inline-flex h-4 w-7 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200', year.is_active ? 'bg-teal-500' : 'bg-gray-300']"
            :title="year.is_active ? 'Nonaktifkan' : 'Aktifkan'"
          >
            <span :class="['pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200', year.is_active ? 'translate-x-3' : 'translate-x-0']" />
          </button>
          <span :class="['text-xs font-medi