<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useMasterDataStore } from '@/stores/masterData'
import { useToast } from '@/composables/useToast'
import DataTable from '@/components/common/DataTable.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import Pagination from '@/components/common/Pagination.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router          = useRouter()
const alumniStore     = useAlumniStore()
const masterDataStore = useMasterDataStore()
const { showToast }   = useToast()

const showDeleteModal = ref(false)
const deleteTarget    = ref(null)
const selectedIds     = ref([])

const columns = [
  { key: 'nim',            label: 'NIM',           sortable: true  },
  { key: 'full_name',      label: 'Nama',          sortable: true  },
  { key: 'study_program',  label: 'Program Studi', sortable: false },
  { key: 'graduation_year',label: 'Angkatan',      sortable: true  },
  { key: 'gpa',            label: 'IPK',           sortable: true  },
  { key: 'survey_status',  label: 'Status Survei', sortable: false },
]

const filterConfig = [
  { key: 'search', type: 'text', placeholder: 'Cari NIM, nama, email...' },
  {
    key: 'study_program_id',
    type: 'select',
    label: 'Program Studi',
    options: computed(() => masterDataStore.studyProgramOptions),
  },
  {
    key: 'graduation_year_id',
    type: 'select',
    label: 'Angkatan',
    options: computed(() => masterDataStore.graduationYearOptions),
  },
  {
    key: 'survey_status',
    type: 'select',
    label: 'Status Survei',
    options: [
      { value: '',                label: 'Semua Status'    },
      { value: 'belum_disurvei',  label: 'Belum Disurvei'  },
      { value: 'terkirim',        label: 'Terkirim'        },
      { value: 'sedang_mengisi',  label: 'Sedang Mengisi'  },
      { value: 'selesai',         label: 'Selesai'         },
    ],
  },
]

onMounted(() => {
  alumniStore.fetchList()
  masterDataStore.fetchPublicAll()
})

function handleSort(key) {
  const newDir = alumniStore.filters.sort_by === key && alumniStore.filters.sort_dir === 'asc'
    ? 'desc'
    : 'asc'
  alumniStore.setFilter('sort_by',  key)
  alumniStore.setFilter('sort_dir', newDir)
  alumniStore.fetchList()
}

function handlePageChange(page) {
  alumniStore.fetchList(page)
}

function handleRowSelect(ids) {
  selectedIds.value = ids
}

function applyFilter(filters) {
  Object.entries(filters).forEach(([k, v]) => alumniStore.setFilter(k, v))
  alumniStore.fetchList()
}

function resetFilter() {
  alumniStore.resetFilters()
  alumniStore.fetchList()
}

function goToCreate() {
  router.push({ name: 'admin.alumni.create' })
}

function goToDetail(row) {
  router.push({ name: 'admin.alumni.detail', params: { id: row.id } })
}

function goToEdit(row) {
  router.push({ name: 'admin.alumni.edit', params: { id: row.id } })
}

function goToImport() {
  router.push({ name: 'admin.alumni.import' })
}

async function handleExport() {
  try {
    await alumniStore.exportAlumni()
    showToast('Export berhasil diproses.', 'success')
  } catch {
    showToast('Export gagal.', 'error')
  }
}

function confirmDelete(row) {
  deleteTarget.value    = row
  showDeleteModal.value = true
}

async function handleDelete() {
  try {
    await alumniStore.remove(deleteTarget.value.id)
    showToast('Alumni berhasil dihapus.', 'success')
    showDeleteModal.value = false
    deleteTarget.value    = null
  } catch {
    showToast('Gagal menghapus alumni.', 'error')
  }
}

async function handleSendInvitation(row) {
  try {
    await alumniStore.sendInvitation(row.id)
    showToast(`Undangan survei berhasil dikirim ke ${row.full_name}.`, 'success')
  } catch {
    showToast('Gagal mengirim undangan.', 'error')
  }
}
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Alumni</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola seluruh data alumni universitas</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="btn-secondary flex items-center gap-2" @click="goToImport">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Import Excel
        </button>
        <button class="btn-secondary flex items-center gap-2" @click="handleExport">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export Excel
        </button>
        <button class="btn-primary flex items-center gap-2" @click="goToCreate">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Alumni
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <FilterBar
      :filters="filterConfig"
      :model-value="alumniStore.filters"
      @filter="applyFilter"
      @reset="resetFilter"
    />

    <!-- Table -->
    <div class="card mt-4">
      <DataTable
        :columns="columns"
        :data="alumniStore.list"
        :loading="alumniStore.loading"
        :selectable="true"
        empty-text="Belum ada data alumni. Mulai dengan menambahkan atau mengimpor data alumni."
        @sort="handleSort"
        @page-change="handlePageChange"
        @row-select="handleRowSelect"
      >
        <!-- Custom cell: NIM -->
        <template #cell-nim="{ row }">
          <button
            class="font-mono text-primary-600 hover:text-primary-700 hover:underline font-medium"
            @click="goToDetail(row)"
          >
            {{ row.nim }}
          </button>
        </template>

        <!-- Custom cell: Nama -->
        <template #cell-full_name="{ row }">
          <div class="flex items-center gap-3">
            <img
              v-if="row.photo_url"
              :src="row.photo_url"
              :alt="row.full_name"
              class="w-8 h-8 rounded-full object-cover flex-shrink-0"
            />
            <div
              v-else
              class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center
                     justify-center text-xs font-bold flex-shrink-0"
            >
              {{ row.full_name?.charAt(0)?.toUpperCase() }}
            </div>
            <span class="font-medium text-gray-900">{{ row.full_name }}</span>
          </div>
        </template>

        <!-- Custom cell: IPK -->
        <template #cell-gpa="{ row }">
          <span class="font-mono">{{ Number(row.gpa).toFixed(2) }}</span>
        </template>

        <!-- Custom cell: Status Survei -->
        <template #cell-survey_status="{ row }">
          <Badge :status="row.survey_status" />
        </template>

        <!-- Custom cell: Aksi -->
        <template #actions="{ row }">
          <div class="flex items-center gap-2">
            <button
              class="text-gray-500 hover:text-primary-600 p-1 rounded"
              title="Lihat Detail"
              @click="goToDetail(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button
              class="text-gray-500 hover:text-blue-600 p-1 rounded"
              title="Edit"
              @click="goToEdit(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              v-if="row.survey_status !== 'selesai'"
              class="text-gray-500 hover:text-green-600 p-1 rounded"
              title="Kirim Undangan Survei"
              @click="handleSendInvitation(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </button>
            <button
              class="text-gray-500 hover:text-red-600 p-1 rounded"
              title="Hapus"
              @click="confirmDelete(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Pagination — gunakan alumniStore.meta (bukan .pagination) -->
      <Pagination
        v-if="alumniStore.meta.last_page > 1"
        :current-page="alumniStore.meta.current_page"
        :last-page="alumniStore.meta.last_page"
        :total="alumniStore.meta.total"
        :from="alumniStore.meta.from"
        :to="alumniStore.meta.to"
        class="mt-4 px-6 pb-4"
        @change="handlePageChange"
      />
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Alumni"
      :message="`Apakah Anda yakin ingin menghapus data alumni ${deleteTarget?.full_name}? Tindakan ini tidak dapat dibatalkan.`"
      confirm-text="Ya, Hapus"
      confirm-variant="danger"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-primary {
  @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors;
}
.btn-secondary {
  @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors;
}
.card {
  @apply bg-white rounded-xl shadow-card border border-gray-100;
}
</style>
