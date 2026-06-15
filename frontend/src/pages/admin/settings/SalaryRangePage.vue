<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { showToast } = useToast()
const { confirm }   = useConfirm()

// ─── State ────────────────────────────────────────────────────────────────────
const ranges      = ref([])
const loading     = ref(false)
const saving      = ref(false)
const search      = ref('')
const filterStatus = ref('')
const showModal   = ref(false)
const editMode    = ref(false)

// Kolom DB: label, min_value, max_value, order_number, is_active
const form = ref({ id: null, label: '', min_value: '', max_value: '', order_number: '', is_active: true })
const formErrors = ref({})

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  let result = ranges.value ?? []
  if (filterStatus.value !== '') {
    const active = filterStatus.value === '1'
    result = result.filter((r) => r.is_active === active)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    result = result.filter((r) => r.label.toLowerCase().includes(q))
  }
  return [...result].sort((a, b) => (a.order_number ?? 0) - (b.order_number ?? 0) || (a.min_value ?? 0) - (b.min_value ?? 0))
})

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatRupiah(amount) {
  if (amount === null || amount === undefined || amount === '') return '—'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// ─── API ──────────────────────────────────────────────────────────────────────
async function fetchRanges() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/salary-ranges', { params: { per_page: 200 } })
    ranges.value = Array.isArray(data.data) ? data.data : []
  } catch {
    showToast('Gagal memuat data rentang gaji.', 'error')
    ranges.value = []
  } finally {
    loading.value = false
  }
}

async function save() {
  formErrors.value = {}
  saving.value = true
  try {
    const payload = {
      label:        form.value.label.trim(),
      min_value:    form.value.min_value !== '' ? Number(form.value.min_value) : null,
      max_value:    form.value.max_value !== '' ? Number(form.value.max_value) : null,
      order_number: form.value.order_number !== '' ? Number(form.value.order_number) : null,
      is_active:    form.value.is_active,
    }
    if (editMode.value) {
      const { data } = await api.put(`/admin/salary-ranges/${form.value.id}`, payload)
      const idx = ranges.value.findIndex((r) => r.id === form.value.id)
      if (idx !== -1) ranges.value[idx] = data.data
      showToast('Rentang gaji berhasil diperbarui.', 'success')
    } else {
      const { data } = await api.post('/admin/salary-ranges', payload)
      ranges.value.push(data.data)
      showToast('Rentang gaji berhasil ditambahkan.', 'success')
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

async function destroy(range) {
  const ok = await confirm(
    `Hapus rentang gaji "${range.label}"? Data alumni yang menggunakan rentang ini mungkin terpengaruh.`
  )
  if (!ok) return
  try {
    await api.delete(`/admin/salary-ranges/${range.id}`)
    ranges.value = ranges.value.filter((r) => r.id !== range.id)
    showToast('Rentang gaji berhasil dihapus.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menghapus rentang gaji.', 'error')
  }
}

async function toggleActive(range) {
  try {
    const { data } = await api.put(`/admin/salary-ranges/${range.id}`, {
      is_active: !range.is_active,
    })
    const idx = ranges.value.findIndex((r) => r.id === range.id)
    if (idx !== -1) ranges.value[idx] = data.data
    showToast(
      `"${data.data.label}" ${data.data.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`,
      'success'
    )
  } catch {
    showToast('Gagal mengubah status rentang gaji.', 'error')
  }
}

// ─── Modal ────────────────────────────────────────────────────────────────────
function openCreate() {
  form.value       = { id: null, label: '', min_value: '', max_value: '', order_number: '', is_active: true }
  formErrors.value = {}
  editMode.value   = false
  showModal.value  = true
}

function openEdit(range) {
  form.value = {
    id:           range.id,
    label:        range.label,
    min_value:    range.min_value ?? '',
    max_value:    range.max_value ?? '',
    order_number: range.order_number ?? '',
    is_active:    range.is_active,
  }
  formErrors.value = {}
  editMode.value   = true
  showModal.value  = true
}

function closeModal() {
  showModal.value  = false
  editMode.value   = false
  form.value       = { id: null, label: '', min_value: '', max_value: '', order_number: '', is_active: true }
  formErrors.value = {}
}

onMounted(fetchRanges)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Rentang Gaji</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola daftar rentang gaji sebagai pilihan dalam form survei alumni.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Rentang
      </button>
    </div>

    <!-- Filter & Search -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Cari label rentang gaji…"
          class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        />
      </div>
      <select
        v-model="filterStatus"
        class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
      >
        <option value="">Semua Status</option>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
      <span class="self-center text-sm text-gray-500 whitespace-nowrap">
        {{ filtered.length }} / {{ ranges.length }} rentang
      </span>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 6" :key="i" class="h-16 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
      </svg>
      <p class="text-sm font-medium text-gray-500">
        {{ search || filterStatus ? 'Tidak ada rentang gaji yang cocok dengan filter.' : 'Belum ada data rentang gaji.' }}
      </p>
      <button v-if="!search && !filterStatus" @click="openCreate" class="mt-3 text-sm text-teal-600 hover:underline">
        Tambah rentang gaji pertama
      </button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urutan</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min. Gaji</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maks. Gaji</th>
            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="range in filtered"
            :key="range.id"
            class="hover:bg-gray-50 transition-colors"
            :class="!range.is_active ? 'opacity-60' : ''"
          >
            <td class="px-5 py-3 tabular-nums text-gray-400 text-center">{{ range.order_number ?? '—' }}</td>
            <td class="px-5 py-3 font-medium text-gray-900">{{ range.label }}</td>
            <td class="px-5 py-3 tabular-nums text-gray-600">
              {{ range.min_value !== null && range.min_value !== undefined ? formatRupiah(range.min_value) : '—' }}
            </td>
            <td class="px-5 py-3 tabular-nums text-gray-600">
              {{ range.max_value !== null && range.max_value !== undefined ? formatRupiah(range.max_value) : 'Tidak terbatas' }}
            </td>
            <td class="px-5 py-3">
              <button
                @click="toggleActive(range)"
                :title="range.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                :class="[
                  'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium transition-colors',
                  range.is_active
                    ? 'bg-teal-50 text-teal-700 hover:bg-teal-100'
                    : 'bg-gray-100 text-gray-500 hover:bg-gray-200',
                ]"
              >
                <span :class="['h-1.5 w-1.5 rounded-full', range.is_active ? 'bg-teal-500' : 'bg-gray-400']" />
                {{ range.is_active ? 'Aktif' : 'Nonaktif' }}
              </button>
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openEdit(range)"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                  title="Edit"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                  </svg>
                </button>
                <button
                  @click="destroy(range)"
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
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />
          <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900">
                {{ editMode ? 'Edit Rentang Gaji' : 'Tambah Rentang Gaji' }}
              </h2>
              <button
                @click="closeModal"
                class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="save" class="space-y-4">
              <!-- Label -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Label <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.label"
                  type="text"
                  placeholder="Contoh: Rp 3.000.000 – Rp 5.000.000"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.label ? 'border-red-400' : 'border-gray-300'"
                  autofocus
                />
                <p v-if="formErrors.label" class="mt-1 text-xs text-red-500">{{ formErrors.label[0] }}</p>
              </div>

              <!-- Min & Max Value -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Min. Gaji (Rp)</label>
                  <input
                    v-model="form.min_value"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                    :class="formErrors.min_value ? 'border-red-400' : 'border-gray-300'"
                  />
                  <p v-if="formErrors.min_value" class="mt-1 text-xs text-red-500">{{ formErrors.min_value[0] }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Maks. Gaji (Rp)</label>
                  <input
                    v-model="form.max_value"
                    type="number"
                    min="0"
                    placeholder="Kosongkan jika tak terbatas"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                    :class="formErrors.max_value ? 'border-red-400' : 'border-gray-300'"
                  />
                  <p v-if="formErrors.max_value" class="mt-1 text-xs text-red-500">{{ formErrors.max_value[0] }}</p>
                </div>
              </div>

              <!-- Urutan -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urutan Tampil</label>
                <input
                  v-model="form.order_number"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                />
                <p class="mt-1 text-xs text-gray-400">Angka lebih kecil tampil lebih awal.</p>
              </div>

              <!-- Status aktif -->
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="form.is_active = !form.is_active"
                  :class="[
                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200',
                    form.is_active ? 'bg-teal-500' : 'bg-gray-300',
                  ]"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200',
                      form.is_active ? 'translate-x-4' : 'translate-x-0',
                    ]"
                  />
                </button>
                <span class="text-sm text-gray-700">Aktif (dapat dipilih saat input data alumni)</span>
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
                  {{ saving ? 'Menyimpan…' : (editMode ? 'Simpan Perubahan' : 'Tambah Rentang') }}
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
