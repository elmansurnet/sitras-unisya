<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { showToast } = useToast()
const { confirm }   = useConfirm()

// ─── State ────────────────────────────────────────────────────────────────────
const users      = ref([])
const loading    = ref(false)
const saving     = ref(false)
const search     = ref('')
const filterRole = ref('')
const filterStatus = ref('')
const showModal  = ref(false)
const editMode   = ref(false)
const resettingId = ref(null)

const form = ref({ id: null, name: '', username: '', email: '', role: 'admin', is_active: true })
const formErrors = ref({})

const ROLES = [
  { value: 'superadmin', label: 'Superadmin', color: 'bg-purple-50 text-purple-700 ring-purple-600/20' },
  { value: 'admin',      label: 'Admin',      color: 'bg-blue-50 text-blue-700 ring-blue-600/20' },
]

// ─── Computed ─────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  let list = users.value
  if (filterRole.value)   list = list.filter(u => u.role === filterRole.value)
  if (filterStatus.value !== '') list = list.filter(u => String(u.is_active) === filterStatus.value)
  const q = search.value.toLowerCase()
  if (q) list = list.filter(u =>
    u.name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    (u.username ?? '').toLowerCase().includes(q)
  )
  return list
})

function roleStyle(role) {
  return ROLES.find(r => r.value === role)?.color ?? 'bg-gray-50 text-gray-700 ring-gray-600/20'
}
function roleLabel(role) {
  return ROLES.find(r => r.value === role)?.label ?? role
}

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/users', { params: { per_page: 100 } })
    users.value = data.data
  } catch {
    showToast('Gagal memuat data pengguna.', 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  formErrors.value = {}
  saving.value = true
  try {
    if (editMode.value) {
      const { data } = await api.put(`/admin/users/${form.value.id}`, {
        name:      form.value.name,
        username:  form.value.username,
        email:     form.value.email,
        role:      form.value.role,
        is_active: form.value.is_active,
      })
      const idx = users.value.findIndex(u => u.id === form.value.id)
      if (idx !== -1) users.value[idx] = data.data
      showToast('Pengguna berhasil diperbarui.', 'success')
    } else {
      const { data } = await api.post('/admin/users', {
        name:     form.value.name,
        username: form.value.username,
        email:    form.value.email,
        role:     form.value.role,
      })
      users.value.unshift(data.data)
      showToast('Pengguna berhasil dibuat. Kata sandi sementara dikirim via email.', 'success')
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

async function toggleActive(user) {
  const action = user.is_active ? 'nonaktifkan' : 'aktifkan'
  const ok = await confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} akun "${user.name}"?`)
  if (!ok) return
  try {
    const { data } = await api.put(`/admin/users/${user.id}`, { is_active: !user.is_active })
    const idx = users.value.findIndex(u => u.id === user.id)
    if (idx !== -1) users.value[idx] = data.data
    showToast(`Akun berhasil di${action}kan.`, 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal mengubah status.', 'error')
  }
}

async function resetPassword(user) {
  const ok = await confirm(`Reset kata sandi "${user.name}"? Kata sandi baru akan dikirim ke email terdaftar.`)
  if (!ok) return
  resettingId.value = user.id
  try {
    await api.post(`/admin/users/${user.id}/reset-password`)
    showToast('Kata sandi baru telah dikirim ke email pengguna.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal mereset kata sandi.', 'error')
  } finally {
    resettingId.value = null
  }
}

async function destroy(user) {
  const ok = await confirm(`Hapus akun "${user.name}" secara permanen? Tindakan ini tidak dapat dibatalkan.`)
  if (!ok) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    users.value = users.value.filter(u => u.id !== user.id)
    showToast('Pengguna berhasil dihapus.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menghapus pengguna.', 'error')
  }
}

// ─── Modal helpers ────────────────────────────────────────────────────────────
function openCreate() {
  form.value = { id: null, name: '', username: '', email: '', role: 'admin', is_active: true }
  formErrors.value = {}
  editMode.value  = false
  showModal.value = true
}

function openEdit(user) {
  form.value = {
    id:        user.id,
    name:      user.name,
    username:  user.username ?? '',
    email:     user.email,
    role:      user.role,
    is_active: user.is_active,
  }
  formErrors.value = {}
  editMode.value  = true
  showModal.value = true
}

function closeModal() {
  showModal.value  = false
  editMode.value   = false
  form.value       = { id: null, name: '', username: '', email: '', role: 'admin', is_active: true }
  formErrors.value = {}
}

onMounted(fetchUsers)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Manajemen Pengguna</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola akun admin dan superadmin sistem.</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Pengguna
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-52">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Cari nama, email, atau username…"
          class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        />
      </div>
      <select
        v-model="filterRole"
        class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
      >
        <option value="">Semua Role</option>
        <option value="superadmin">Superadmin</option>
        <option value="admin">Admin</option>
      </select>
      <select
        v-model="filterStatus"
        class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
      >
        <option value="">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-16 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
      </svg>
      <p class="text-sm font-medium text-gray-500">{{ search || filterRole || filterStatus !== '' ? 'Tidak ada pengguna yang cocok.' : 'Belum ada pengguna.' }}</p>
      <button v-if="!search && !filterRole && filterStatus === ''" @click="openCreate" class="mt-3 text-sm text-teal-600 hover:underline">Tambah pengguna pertama</button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Pengguna</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Username</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Role</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Status</th>
            <th class="px-5 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Terakhir Login</th>
            <th class="px-5 py-3 text-right font-medium text-gray-500 uppercase tracking-wider text-xs">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="user in filtered" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700 text-xs font-semibold">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ user.name }}</p>
                  <p class="text-xs text-gray-400">{{ user.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3 text-gray-600 font-mono text-xs">{{ user.username || '—' }}</td>
            <td class="px-5 py-3">
              <span
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                :class="roleStyle(user.role)"
              >{{ roleLabel(user.role) }}</span>
            </td>
            <td class="px-5 py-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="user.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                <span class="mr-1.5 h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-gray-400'" />
                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 text-xs">
              {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—' }}
            </td>
            <td class="px-5 py-3">
              <div class="flex items-center justify-end gap-1">
                <!-- Edit -->
                <button
                  @click="openEdit(user)"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                  title="Edit"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                  </svg>
                </button>
                <!-- Toggle Aktif -->
                <button
                  @click="toggleActive(user)"
                  class="rounded-md p-1.5 transition-colors"
                  :class="user.is_active ? 'text-gray-400 hover:bg-orange-50 hover:text-orange-500' : 'text-gray-400 hover:bg-green-50 hover:text-green-600'"
                  :title="user.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                >
                  <svg v-if="user.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                </button>
                <!-- Reset Password -->
                <button
                  @click="resetPassword(user)"
                  :disabled="resettingId === user.id"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-colors disabled:opacity-40"
                  title="Reset Kata Sandi"
                >
                  <svg v-if="resettingId === user.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                  </svg>
                </button>
                <!-- Hapus (superadmin only guard di UI) -->
                <button
                  @click="destroy(user)"
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
                {{ editMode ? 'Edit Pengguna' : 'Tambah Pengguna' }}
              </h2>
              <button @click="closeModal" class="rounded-full p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="save" class="space-y-4">
              <!-- Nama -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Nama lengkap pengguna"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.name ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.name" class="mt-1 text-xs text-red-500">{{ formErrors.name[0] }}</p>
              </div>
              <!-- Username -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input
                  v-model="form.username"
                  type="text"
                  placeholder="username (tanpa spasi)"
                  class="w-full rounded-lg border px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.username ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.username" class="mt-1 text-xs text-red-500">{{ formErrors.username[0] }}</p>
              </div>
              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="email@unisya.ac.id"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.email ? 'border-red-400' : 'border-gray-300'"
                />
                <p v-if="formErrors.email" class="mt-1 text-xs text-red-500">{{ formErrors.email[0] }}</p>
                <p v-if="!editMode" class="mt-1 text-xs text-gray-400">Kata sandi sementara akan dikirim ke email ini.</p>
              </div>
              <!-- Role -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select
                  v-model="form.role"
                  class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  :class="formErrors.role ? 'border-red-400' : 'border-gray-300'"
                >
                  <option value="admin">Admin</option>
                  <option value="superadmin">Superadmin</option>
                </select>
                <p v-if="formErrors.role" class="mt-1 text-xs text-red-500">{{ formErrors.role[0] }}</p>
              </div>
              <!-- Status (edit mode only) -->
              <div v-if="editMode" class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Status Akun</label>
                <button
                  type="button"
                  @click="form.is_active = !form.is_active"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                  :class="form.is_active ? 'bg-teal-600' : 'bg-gray-300'"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                    :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
                <span class="text-sm" :class="form.is_active ? 'text-teal-700' : 'text-gray-400'">{{ form.is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </div>
              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  @click="closeModal"
                  class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >Batal</button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60 transition-colors"
                >
                  <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ saving ? 'Menyimpan…' : (editMode ? 'Simpan Perubahan' : 'Buat Pengguna') }}
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
