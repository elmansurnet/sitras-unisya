<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useToast } from '@/composables/useToast'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router = useRouter()
const employerStore = useEmployerStore()
const { toast } = useToast()

const search = ref('')
const showDeleteModal = ref(false)
const selectedId = ref(null)
const selectedName = ref('')
const deletingId = ref(null)

onMounted(async () => {
  await employerStore.fetchList()
})

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return employerStore.list ?? []
  return (employerStore.list ?? []).filter(
    (e) =>
      e.company_name?.toLowerCase().includes(q) ||
      e.industry_sector?.toLowerCase().includes(q) ||
      e.address_city?.toLowerCase().includes(q) ||
      e.contact_person_name?.toLowerCase().includes(q)
  )
})

function openDeleteModal(employer) {
  selectedId.value = employer.id
  selectedName.value = employer.company_name
  showDeleteModal.value = true
}

async function handleDelete() {
  showDeleteModal.value = false
  deletingId.value = selectedId.value
  try {
    // FIX B4: method di store adalah 'destroy', bukan 'remove'
    await employerStore.destroy(selectedId.value)
    toast.success(`Employer "${selectedName.value}" berhasil dihapus.`)
  } catch {
    toast.error('Gagal menghapus employer.')
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Employer</h1>
        <p class="text-sm text-gray-500">Kelola data perusahaan / instansi pemberi kerja alumni</p>
      </div>
      <button
        class="btn-primary"
        @click="router.push({ name: 'admin.employer.create' })"
      >
        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Employer
      </button>
    </div>

    <!-- Filter/Search -->
    <div class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="search"
          class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
          placeholder="Cari perusahaan, sektor, kota, contact person..."
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="employerStore.loading" class="flex items-center justify-center py-24">
      <svg class="h-8 w-8 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!filtered.length"
      class="card flex flex-col items-center justify-center py-16 text-center"
    >
      <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
      </svg>
      <p class="text-sm font-medium text-gray-600">
        {{ search ? 'Tidak ada employer yang cocok' : 'Belum ada employer terdaftar' }}
      </p>
      <p class="mt-1 text-xs text-gray-400">
        {{ search ? 'Coba kata kunci lain' : 'Klik "Tambah Employer" untuk memulai' }}
      </p>
    </div>

    <!-- Table -->
    <div v-else class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50 text-left">
              <th class="px-4 py-3 font-medium text-gray-600">Perusahaan</th>
              <th class="px-4 py-3 font-medium text-gray-600">Tipe</th>
              <th class="px-4 py-3 font-medium text-gray-600">Sektor Industri</th>
              <th class="px-4 py-3 font-medium text-gray-600">Kota</th>
              <th class="px-4 py-3 font-medium text-gray-600">Contact Person</th>
              <th class="px-4 py-3 font-medium text-gray-600">Skala</th>
              <th class="px-4 py-3 font-medium text-gray-600">Status Survei</th>
              <th class="px-4 py-3 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr
              v-for="employer in filtered"
              :key="employer.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- FIX B3: gunakan nama kolom aktual dari Employer model -->
              <td class="px-4 py-3">
                <button
                  class="font-medium text-gray-900 hover:text-teal-700 text-left"
                  @click="router.push({ name: 'admin.employer.detail', params: { id: employer.id } })"
                >
                  {{ employer.company_name }}
                </button>
                <p v-if="employer.website" class="text-xs text-gray-400 truncate max-w-[180px]">
                  {{ employer.website }}
                </p>
              </td>
              <td class="px-4 py-3 text-gray-600 capitalize">
                {{ employer.company_type ?? '—' }}
              </td>
              <!-- industry_sector adalah string biasa, BUKAN object -->
              <td class="px-4 py-3 text-gray-600">
                {{ employer.industry_sector ?? '—' }}
              </td>
              <td class="px-4 py-3 text-gray-600">
                {{ employer.address_city ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <p class="text-gray-900">{{ employer.contact_person_name ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ employer.contact_person_email ?? '' }}</p>
              </td>
              <td class="px-4 py-3 text-gray-600 capitalize">
                {{ employer.company_scale ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    employer.survey_status === 'selesai'
                      ? 'bg-teal-50 text-teal-700'
                      : employer.survey_status === 'terkirim'
                        ? 'bg-blue-50 text-blue-700'
                        : 'bg-gray-100 text-gray-500',
                  ]"
                >
                  {{
                    employer.survey_status === 'selesai'
                      ? 'Selesai'
                      : employer.survey_status === 'terkirim'
                        ? 'Terkirim'
                        : 'Belum Disurvei'
                  }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    class="rounded p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-colors"
                    title="Detail"
                    @click="router.push({ name: 'admin.employer.detail', params: { id: employer.id } })"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>
                  <button
                    class="rounded p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                    title="Edit"
                    @click="router.push({ name: 'admin.employer.edit', params: { id: employer.id } })"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button
                    class="rounded p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                    title="Hapus"
                    :disabled="deletingId === employer.id"
                    @click="openDeleteModal(employer)"
                  >
                    <svg v-if="deletingId === employer.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">
          Menampilkan {{ filtered.length }} dari {{ employerStore.list?.length ?? 0 }} employer
        </p>
      </div>
    </div>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Employer"
      :message="`Hapus employer &quot;${selectedName}&quot;? Tindakan ini tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus"
      :danger="true"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-primary { @apply inline-flex items-center bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors; }
.card        { @apply bg-white rounded-xl shadow-sm border border-gray-200; }
</style>
