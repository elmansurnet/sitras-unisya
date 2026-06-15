<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import DataTable from '@/components/common/DataTable.vue'
import Pagination from '@/components/common/Pagination.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router      = useRouter()
const alumniStore = useAlumniStore()
const { toast }   = useToast()

const showDeleteModal = ref(false)
const deleteTarget    = ref(null)
const deletingId      = ref(null)

const columns = [
  { key: 'nim',             label: 'NIM',           sortable: true  },
  { key: 'full_name',       label: 'Nama',          sortable: true  },
  { key: 'study_program',   label: 'Program Studi', sortable: false },
  { key: 'graduation_year', label: 'Angkatan',      sortable: true  },
  { key: 'gpa',             label: 'IPK',           sortable: true  },
  { key: 'survey_status',   label: 'Status Survei', sortable: false },
  { key: 'actions',         label: 'Aksi',          sortable: false },
]

// Filter state lokal
const search           = ref('')
const studyProgramId   = ref('')
const graduationYearId = ref('')
const surveyStatus     = ref('')

onMounted(async () => {
  try {
    await alumniStore.fetchMasterData()
  } catch { /* optional */ }
  fetchPage()
})

async function fetchPage(page = 1) {
  alumniStore.filters.search             = search.value
  alumniStore.filters.study_program_id   = studyProgramId.value || null
  alumniStore.filters.graduation_year_id = graduationYearId.value || null
  alumniStore.filters.survey_status      = surveyStatus.value || null
  alumniStore.meta.current_page          = page
  try {
    await alumniStore.fetchAlumni(page)
  } catch {
    toast.error('Gagal memuat data alumni.')
  }
}

function applyFilter() {
  fetchPage(1)
}

function resetFilter() {
  search.value           = ''
  studyProgramId.value   = ''
  graduationYearId.value = ''
  surveyStatus.value     = ''
  alumniStore.filters    = { search: '', study_program_id: null, graduation_year_id: null, survey_status: null, gender: null, sort_by: 'created_at', sort_dir: 'desc' }
  fetchPage(1)
}

async function handleExport() {
  try {
    await alumniStore.exportAlumni()
    toast.success('Export berhasil diproses.')
  } catch {
    toast.error('Export gagal.')
  }
}

function confirmDelete(row) {
  deleteTarget.value    = row
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deletingId.value = deleteTarget.value.id
  try {
    await alumniStore.remove(deleteTarget.value.id)
    toast.success('Alumni berhasil dihapus.')
    showDeleteModal.value = false
    deleteTarget.value    = null
    fetchPage(alumniStore.meta.current_page)
  } catch {
    toast.error('Gagal menghapus alumni.')
  } finally {
    deletingId.value = null
  }
}

async function handleSendInvitation(row) {
  try {
    await alumniStore.sendInvitation(row.id)
    toast.success(`Undangan survei berhasil dikirim ke ${row.full_name}.`)
  } catch {
    toast.error('Gagal mengirim undangan.')
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Alumni</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola seluruh data alumni universitas</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="btn-secondary flex items-center gap-2" @click="$router.push({ name: 'admin.alumni.import' })">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
          Import Excel
        </button>
        <button class="btn-secondary flex items-center gap-2" @click="handleExport">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
          Export Excel
        </button>
        <button class="btn-primary flex items-center gap-2" @click="$router.push({ name: 'admin.alumni.create' })">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Tambah Alumni
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card p-4 mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="search" type="text" placeholder="Cari NIM, nama, email..." class="form-input" />
        <select v-model="studyProgramId" class="form-input">
          <option value="">Semua Program Studi</option>
          <option v-for="opt in alumniStore.studyProgramOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select v-model="graduationYearId" class="form-input">
          <option value="">Semua Angkatan</option>
          <option v-for="opt in alumniStore.graduationYearOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select v-model="surveyStatus" class="form-input">
          <option value="">Semua Status</option>
          <option value="belum_disurvei">Belum Disurvei</option>
          <option value="terkirim">Terkirim</option>
          <option value="sedang_mengisi">Sedang Mengisi</option>
          <option value="selesai">Selesai</option>
        </select>
      </div>
      <div class="flex gap-2 mt-3">
        <button class="btn-primary text-sm" @click="applyFilter">Terapkan Filter</button>
        <button class="btn-secondary text-sm" @click="resetFilter">Reset</button>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="alumniStore.loading">
              <td :colspan="columns.length" class="px-4 py-8 text-center text-gray-400">
                <div class="flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                  Memuat data...
                </div>
              </td>
            </tr>
            <tr v-else-if="!alumniStore.list.length">
              <td :colspan="columns.length" class="px-4 py-12 text-center text-gray-400">
                Belum ada data alumni. Mulai dengan menambahkan atau mengimpor data alumni.
              </td>
            </tr>
            <tr
              v-for="row in alumniStore.list"
              :key="row.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3">
                <button class="font-mono text-emerald-700 hover:underline font-medium" @click="$router.push({ name: 'admin.alumni.detail', params: { id: row.id } })">
                  {{ row.nim }}
                </button>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ row.full_name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <span class="font-medium text-gray-900">{{ row.full_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ row.study_program?.name ?? '-' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ row.graduation_year?.year ?? '-' }}</td>
              <td class="px-4 py-3 font-mono">{{ row.gpa ? Number(row.gpa).toFixed(2) : '-' }}</td>
              <td class="px-4 py-3">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  row.survey_status === 'selesai'        ? 'bg-green-100 text-green-800'   :
                  row.survey_status === 'sedang_mengisi' ? 'bg-blue-100 text-blue-800'     :
                  row.survey_status === 'terkirim'       ? 'bg-yellow-100 text-yellow-800' :
                                                           'bg-gray-100 text-gray-600'
                ]">
                  {{ row.survey_status === 'selesai' ? 'Selesai' : row.survey_status === 'sedang_mengisi' ? 'Sedang Mengisi' : row.survey_status === 'terkirim' ? 'Terkirim' : 'Belum Disurvei' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                  <button title="Detail" class="p-1 text-gray-400 hover:text-emerald-600 rounded" @click="$router.push({ name: 'admin.alumni.detail', params: { id: row.id } })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </button>
                  <button title="Edit" class="p-1 text-gray-400 hover:text-blue-600 rounded" @click="$router.push({ name: 'admin.alumni.edit', params: { id: row.id } })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </button>
                  <button
                    v-if="row.survey_status !== 'selesai'"
                    title="Kirim Undangan Survei"
                    class="p-1 text-gray-400 hover:text-green-600 rounded"
                    @click="handleSendInvitation(row)"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                  </button>
                  <button title="Hapus" class="p-1 text-gray-400 hover:text-red-600 rounded" @click="confirmDelete(row)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="alumniStore.meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          Halaman {{ alumniStore.meta.current_page }} dari {{ alumniStore.meta.last_page }} ({{ alumniStore.meta.total }} total)
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in alumniStore.meta.last_page" :key="p"
            :class="['px-3 py-1 rounded text-sm border', p === alumniStore.meta.current_page ? 'bg-emerald-600 text-white border-emerald-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50']"
            @click="fetchPage(p)"
          >{{ p }}</button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Alumni"
      :message="`Apakah Anda yakin ingin menghapus ${deleteTarget?.full_name ?? 'alumni ini'}? Tindakan ini tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus"
      :danger="true"
      :loading="!!deletingId"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-primary   { @apply bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card          { @apply bg-white rounded-xl shadow-sm border border-gray-100; }
.form-input    { @apply w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none; }
</style>
