<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { showToast } = useToast()
const { confirm }   = useConfirm()

// ─── State ────────────────────────────────────────────────────────────────────
const programs    = ref([])
const faculties   = ref([])
const loading     = ref(false)
const saving      = ref(false)
const search      = ref('')
const filterFaculty = ref('')
const filterStatus  = ref('')
const showModal   = ref(false)
const editMode    = ref(false)

const form = ref({
  id: null, name: '', code: '', faculty_id: '', degree_level: 'S1',
  accreditation: '', is_active: true,
})
const formErrors = ref({})

const degreeLevels = ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi']
const accreditationOptions = ['Unggul', 'A', 'B', 'C', 'Baik Sekali', 'Baik', 'Belum Terakreditasi']

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  let result = programs.value
  if (filterFaculty.value) {
    result = result.filter((p) => p.faculty_id === Number(filterFaculty.value))
  }
  if (filterStatus.value !== '') {
    const active = filterStatus.value === '1'
    result = result.filter((p) => p.is_active === active)
  }
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(
      (p) => p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q)
    )
  }
  return result
})

// ─── API ──────────────────────────────────────────────────────────────────────
async function fetchData() {
  loading.value = true
  try {
    const [sp, fac] = await Promise.all([
      api.get('/admin/study-programs', { params: { per_page: 200 } }),
      api.get('/admin/faculties',      { params: { per_page: 100 } }),
    ])
    programs.value  = sp.data.data
    faculties.value = fac.data.data
  } catch {
    showToast('Gagal memuat data.', 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  formErrors.value = {}
  saving.value = true
  try {
    const payload = {
      name:            form.value.name,
      code:            form.value.code,
      faculty_id:      form.value.faculty_id,
      degree_level:    form.value.degree_level,
      accreditation:   form.value.accreditation || null,
      is_active:       form.value.is_active,
    }
    if (editMode.value) {
      const { data } = await api.put(`/admin/study-programs/${form.value.id}`, payload)
      const idx = programs.value.findIndex((p) => p.id === form.value.id)
      if (idx !== -1) programs.value[idx] = data.data
      showToast('Program studi berhasil diperbarui.', 'success')
    } else {
      const { data } = await api.post('/admin/study-programs', payload)
      programs.value.unshift(data.data)
      showToast('Program studi berhasil ditambahkan.', 'success')
    }
    closeModal()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors ?? {}
    } else {
      showToast(err.response?.data?.message ?? 'Gagal menyimpan data.', 'error')
    }
  } finally {
    saving.value = false
  }
}

async function destroy(prodi) {
  const ok = await confirm(`Hapus program studi "${prodi.name}"?`)
  if (!ok) return
  try {
    await api.delete(`/admin/study-programs/${prodi.id}`)
    programs.value = programs.value.filter((p) => p.id !== prodi.id)
    showToast('Program studi berhasil dihapus.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menghapus.', 'error')
  }
}

async function toggleActive(prodi) {
  try {
    const { data } = await api.put(`/admin/study-programs/${prodi.id}`, {
      is_active: !prodi.is_active,
    })
    const idx = programs.value.findIndex((p) => p.id === prodi.id)
    if (idx !== -1) programs.value[idx] = data.data
    showToast(
      `Program studi ${data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`,
      'success'
    )
  } catch {
    showToast('Gagal mengubah status.', 'error')
  }
}

// ─── Modal ────────────────────────────────────────────────────────────────────
function openCreate() {
  form.value = { id: null, name: '', code: '', faculty_id: '', degree_level: 'S1', accreditation: '', is_active: true }
  formErrors.value = {}
  editMode.value = false
  showModal.value = true
}

function openEdit(prodi) {
  form.value = {
    id:            prodi.id,
    name:          prodi.name,
    code:          prodi.code,
    faculty_id:    prodi.faculty_id,
    degree_level:  prodi.degree_level,
    accreditation: prodi.accreditation ?? '',
    is_active:     prodi.is_active,
  }
  formErrors.value = {}
  editMode.value   = true
  showModal.value  = true
}

function closeModal() {
  showModal.value = false
  form.value = { id: null, name: '', code: '', faculty_id: '', degree_level: 'S1', accreditation: '', is_active: true }
  formErrors.value = {}
}

function accreditationClass(val) {
  if (!val) return 'bg-gray-100 text-gray-500'
  if (['Unggul', 'A'].includes(val)) return 'bg-emerald-50 text-emerald-700'
  if (['Baik Sekali', 'B'].includes(val)) return 'bg-blue-50 text-blue-700'
  if (['Baik', 'C'].includes(val)) return 'bg-yellow-50 text-yellow-700'
  return 'bg-gray-100 text-gray-500'
}

onMounted(fetchData)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Program Studi</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola program studi beserta akreditasi dan statusnya.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Prodi
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
      <div class="relative flex-1 min-w-48">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
        </svg>
        <input v-model="search" type="text" placeholder="Cari nama atau kode prodi…" class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
      </div>
      <select v-model="filterFaculty" class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        <option value="">Semua Fakultas</option>
        <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
      </select>
      <select v-model="filterStatus" class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        <option value="">Semua Status</option>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 6" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-1.342m-7.482 0a50.715 50.715 0 0 0-3.741-1.341" />
      </svg>
      <p class="text-sm font-medium text-gray-500">{{ search || filterFaculty || filterStatus ? 'Tidak ada prodi yang cocok.' : 'Belum ada data program studi.' }}</p>
      <button v-if="!search && !filterFaculty && !filterStatus" @click="openCreate" class="mt-3 text-sm text-teal-600 hover:underline">Tambah prodi pertama</button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Prodi</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fakultas</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenjang</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akreditasi</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="prodi in filtered" :key="prodi.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <span class="rounded-md bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">
                {{ prodi.code }}
              </span>
            </td>
            <td class="px-5 py-3 font-medium text-gray-900">{{ prodi.name }}</td>
            <td class="px-5 py-3 text-gray-500">{{ prodi.faculty?.name ?? '—' }}</td>
            <td class="px-5 py-3">
              <span class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                {{ prodi.degree_level }}
              </span>
            </td>
            <td class="px-5 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', accreditationClass(prodi.accreditation)]">
                {{ prodi.accreditation || '—' }}
              </span>
            </td>
            <td class="px-5 py-3">
              <button
                @click="toggleActive(prodi)"
                :class="[
                  'relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200',
                  prodi.is_active ? 'bg-teal-500' : 'bg-gray-300',
                ]"
                :title="prodi.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200',
                    prodi.is_active ? 'translate-x-4' : 'translate-x-0',
                  ]"
                />
              </button>
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(prodi)" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors" title="Edit">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                  </svg>
                </button>
                <button @click="destroy(prodi)" class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Hapus">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />
          <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900">{{ editMode ? 'Edit Program Studi' : 'Tambah Program Studi' }}</h2>
              <button @click="closeModal" class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form @submit.prevent="save" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Kode Prodi <span class="text-red-500">*</span></label>
                  <input v-model="form.code" type="text" maxlength="10" placeholder="Cth: S1-TI" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" :class="formErrors.code ? 'border-red-400' : 'border-gray-300'" />
                  <p v-if="formErrors.code" class="mt-1 text-xs text-red-500">{{ formErrors.code[0] }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang <span class="text-red-500">*</span></label>
                  <select v-model="form.degree_level" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <option v-for="d in degreeLevels" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Program Studi <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" placeholder="Cth: Teknik Informatika" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" :class="formErrors.name ? 'border-red-400' : 'border-gray-300'" />
                <p v-if="formErrors.name" class="mt-1 text-xs text-red-500">{{ formErrors.name[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fakultas <span class="text-red-500">*</span></label>
                <select v-model="form.faculty_id" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" :class="formErrors.faculty_id ? 'border-red-400' : 'border-gray-300'">
                  <option value="">Pilih Fakultas</option>
                  <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>
                <p v-if="formErrors.faculty_id" class="mt-1 text-xs text-red-500">{{ formErrors.faculty_id[0] }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Akreditasi</label>
                <select v-model="form.accreditation" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                  <option value="">Belum Terakreditasi</option>
                  <option v-for="a in accreditationOptions" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="form.is_active = !form.is_active"
                  :class="['relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200', form.is_active ? 'bg-teal-500' : 'bg-gray-300']"
                >
                  <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200', form.is_active ? 'translate-x-4' : 'translate-x-0']" />
                </button>
                <span class="text-sm text-gray-700">Status Aktif</span>
              </div>

              <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="closeModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60 transition-colors">
                  <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  {{ saving ? 'Menyimpan…' : (editMode ? 'Simpan Perubahan' : 'Tambah Prodi') }}
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
