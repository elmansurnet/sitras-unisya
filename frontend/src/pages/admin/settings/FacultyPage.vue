<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { showToast } = useToast()
const { confirm }   = useConfirm()

// ─── State ────────────────────────────────────────────────────────────────────
const faculties   = ref([])
const loading     = ref(false)
const saving      = ref(false)
const search      = ref('')
const showModal   = ref(false)
const editMode    = ref(false)

const form = ref({ id: null, name: '', code: '', description: '' })
const formErrors = ref({})

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return faculties.value
  return faculties.value.filter(
    (f) => f.name.toLowerCase().includes(q) || f.code.toLowerCase().includes(q)
  )
})

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchFaculties() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/faculties', { params: { per_page: 100 } })
    faculties.value = data.data
  } catch {
    showToast('Gagal memuat data fakultas.', 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  formErrors.value = {}
  saving.value = true
  try {
    if (editMode.value) {
      const { data } = await api.put(`/admin/faculties/${form.value.id}`, {
        name: form.value.name,
        code: form.value.code,
        description: form.value.description,
      })
      const idx = faculties.value.findIndex((f) => f.id === form.value.id)
      if (idx !== -1) faculties.value[idx] = data.data
      showToast('Fakultas berhasil diperbarui.', 'success')
    } else {
      const { data } = await api.post('/admin/faculties', {
        name: form.value.name,
        code: form.value.code,
        description: form.value.description,
      })
      faculties.value.unshift(data.data)
      showToast('Fakultas berhasil ditambahkan.', 'success')
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

async function destroy(faculty) {
  const ok = await confirm(
    `Hapus fakultas "${faculty.name}"? Semua program studi terkait juga akan terpengaruh.`
  )
  if (!ok) return
  try {
    await api.delete(`/admin/faculties/${faculty.id}`)
    faculties.value = faculties.value.filter((f) => f.id !== faculty.id)
    showToast('Fakultas berhasil dihapus.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menghapus fakultas.', 'error')
  }
}

// ─── Modal helpers ────────────────────────────────────────────────────────────
function openCreate() {
  form.value     = { id: null, name: '', code: '', description: '' }
  formErrors.value = {}
  editMode.value = false
  showModal.value = true
}

function openEdit(faculty) {
  form.value = {
    id:          faculty.id,
    name:        faculty.name,
    code:        faculty.code,
    description: faculty.description ?? '',
  }
  formErrors.value = {}
  editMode.value   = true
  showModal.value  = true
}

function closeModal() {
  showModal.value  = false
  editMode.value   = false
  form.value       = { id: null, name: '', code: '', description: '' }
  formErrors.value = {}
}

onMounted(fetchFaculties)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Manajemen Fakultas</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola data fakultas untuk program studi alumni.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Fakultas
      </button>
    </div>

    <!-- Search -->
    <div class="relative">
      <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
      </svg>
      <input
        v-model="search"
        type="text"
        placeholder="Cari nama atau kode fakultas…"
        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
      />
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-14 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
      </svg>
      <p class="text-sm font-medium text-gray-500">{{ search ? 'Tidak ada fakultas yang cocok.' : 'Belum ada data fakultas.' }}</p>
      <button v-if="!search" @click="openCreate" class="mt-3 text-sm text-teal-600 hover:underline">Tambah fakultas pertama</button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Kode</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Nama Fakultas</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Jml Prodi</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Deskripsi</th>
            <th class="px-5 py-3 text-right font-medium text-gray-500 uppercase tracking-wider text-xs">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="faculty in filtered" :key="faculty.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">
                {{ faculty.code }}
              </span>
            </td>
            <td class="px-5 py-3 font-medium text-gray-900">{{ faculty.name }}</td>
            <td class="px-5 py-3">
              <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                {{ faculty.study_programs_count ?? faculty.study_programs?.length ?? '—' }} prodi
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 max-w-xs truncate">{{ faculty.description || '—' }}</td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openEdit(faculty)"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                  title="Edit"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                  </svg>
                </button>
                <button
                  @click="destroy(faculty)"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                  title="Hapus"
                >
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
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />

          <!-- Panel -->
          <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900">
                {{ editMode ? 'Edit Fakultas' : 'Tambah Fakultas' }}
              </h2>
              <button @click="closeModal" class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="save" class="space-y-4">
              <!-- Kode -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Fakultas <span class="text-red-500">*</span></label>
                <input
                  v-model="form.code"
                  type="text"
                  placeholder="Contoh: FT, FAI, FEB"
                  maxlength="10"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.code ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.code" class="mt-1 text-xs text-red-500">{{ formErrors.code[0] }}</p>
              </div>

              <!-- Nama -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Fakultas <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Contoh: Fakultas Teknik"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.name ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.name" class="mt-1 text-xs text-red-500">{{ formErrors.name[0] }}</p>
              </div>

              <!-- Deskripsi -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  placeholder="Deskripsi singkat fakultas (opsional)"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"
                />
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  @click="closeModal"
                  class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60 transition-colors"
                >
                  <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ saving ? 'Menyimpan…' : (editMode ? 'Simpan Perubahan' : 'Tambah Fakultas') }}
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
