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

const form = ref({
  id:            null,
  year:          '',
  academic_year: '',
  semester:      'Ganjil',
  is_active:     true,
})
const formErrors = ref({})

const currentYear = new Date().getFullYear()

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  if (filterStatus.value === '') return years.value
  const active = filterStatus.value === '1'
  return years.value.filter((y) => y.is_active === active)
})

const sortedFiltered = computed(() =>
  [...filtered.value].sort((a, b) => b.year - a.year || a.semester.localeCompare(b.semester))
)

// ─── Auto-generate academic_year ketika year atau semester berubah ─────────────
function syncAcademicYear() {
  const y = Number(form.value.year)
  if (!y || y < 2000) return
  if (form.value.semester === 'Ganjil') {
    form.value.academic_year = `${y - 1}/${y}`
  } else {
    form.value.academic_year = `${y}/${y + 1}`
  }
}

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
      year:          Number(form.value.year),
      academic_year: form.value.academic_year.trim(),
      semester:      form.value.semester,
      is_active:     form.value.is_active,
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
  const ok = await confirm({
    title:          'Hapus Tahun Wisuda',
    message:        `Hapus tahun wisuda ${year.year} (${year.semester})? Data alumni yang terhubung tidak akan terhapus.`,
    confirmText:    'Ya, Hapus',
    confirmVariant: 'danger',
  })
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
      `Tahun wisuda ${data.data.year} ${data.data.semester} ${data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`
    )
  } catch {
    toast.error('Gagal mengubah status.')
  }
}

// ─── Modal ────────────────────────────────────────────────────────────────────
function openCreate() {
  form.value = {
    id:            null,
    year:          String(currentYear),
    academic_year: `${currentYear - 1}/${currentYear}`,
    semester:      'Ganjil',
    is_active:     true,
  }
  formErrors.value = {}
  editMode.value  = false
  showModal.value = true
}

function openEdit(year) {
  form.value = {
    id:            year.id,
    year:          String(year.year),
    academic_year: year.academic_year ?? '',
    semester:      year.semester ?? 'Ganjil',
    is_active:     year.is_active,
  }
  formErrors.value = {}
  editMode.value   = true
  showModal.value  = true
}

function closeModal() {
  showModal.value = false
  form.value = { id: null, year: '', academic_year: '', semester: 'Ganjil', is_active: true }
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
      <div v-for="i in 10" :key="i" class="h-28 animate-pulse rounded-xl bg-gray-100" />
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
        <p class="mt-0.5 text-xs font-medium" :class="year.is_active ? 'text-teal-600' : 'text-gray-400'">
          {{ year.semester }}
        </p>
        <p class="mt-0.5 text-xs text-gray-400">{{ year.academic_year }}</p>
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
          <span :class="['text-xs font-medium', year.is_active ? 'text-teal-600' : 'text-gray-400']">
            {{ year.is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>

        <!-- Hover actions -->
        <div class="absolute inset-x-0 bottom-0 flex translate-y-full items-center justify-end gap-1 bg-white/90 px-2 py-1.5 backdrop-blur-sm transition-transform duration-150 group-hover:translate-y-0">
          <button @click="openEdit(year)" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors" title="Edit">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
            </svg>
          </button>
          <button @click="destroy(year)" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Hapus">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />
          <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900">{{ editMode ? 'Edit Tahun Wisuda' : 'Tambah Tahun Wisuda' }}</h2>
              <button @click="closeModal" class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form @submit.prevent="save" class="space-y-4">
              <!-- Tahun Wisuda -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Wisuda <span class="text-red-500">*</span></label>
                <input
                  v-model="form.year"
                  type="number"
                  :min="2000"
                  :max="currentYear + 5"
                  placeholder="Contoh: 2024"
                  @input="syncAcademicYear"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.year ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.year" class="mt-1 text-xs text-red-500">{{ formErrors.year[0] }}</p>
              </div>

              <!-- Semester -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Semester <span class="text-red-500">*</span></label>
                <select
                  v-model="form.semester"
                  @change="syncAcademicYear"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.semester ? 'border-red-400' : 'border-gray-300'"
                >
                  <option value="Ganjil">Ganjil</option>
                  <option value="Genap">Genap</option>
                </select>
                <p v-if="formErrors.semester" class="mt-1 text-xs text-red-500">{{ formErrors.semester[0] }}</p>
              </div>

              <!-- Tahun Akademik -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Tahun Akademik <span class="text-red-500">*</span>
                  <span class="ml-1 text-xs font-normal text-gray-400">(otomatis terisi)</span>
                </label>
                <input
                  v-model="form.academic_year"
                  type="text"
                  placeholder="Contoh: 2023/2024"
                  maxlength="20"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.academic_year ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.academic_year" class="mt-1 text-xs text-red-500">{{ formErrors.academic_year[0] }}</p>
              </div>

              <!-- Status -->
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="form.is_active = !form.is_active"
                  :class="['relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200', form.is_active ? 'bg-teal-500' : 'bg-gray-300']"
                >
                  <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200', form.is_active ? 'translate-x-4' : 'translate-x-0']" />
                </button>
                <span class="text-sm text-gray-700">Tahun aktif (dapat dipilih saat input alumni)</span>
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="closeModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60 transition-colors">
                  <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  {{ saving ? 'Menyimpan…' : (editMode ? 'Simpan Perubahan' : 'Tambah Tahun') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 180ms ease, transform 180ms ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
