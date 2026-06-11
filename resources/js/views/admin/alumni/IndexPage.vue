<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import DataTable from '@/components/common/DataTable.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import Pagination from '@/components/common/Pagination.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router = useRouter()
const alumniStore = useAlumniStore()
const toast = useToast()

const selected = ref([])
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deletingId = ref(null)

const columns = [
  { key: 'nim', label: 'NIM', sortable: true },
  { key: 'full_name', label: 'Nama Lengkap', sortable: true },
  { key: 'study_program', label: 'Program Studi' },
  { key: 'graduation_year', label: 'Angkatan' },
  { key: 'survey_status', label: 'Status Survei' },
  { key: 'actions', label: '' },
]

const filterDefs = [
  { key: 'search', label: 'Cari', type: 'text', placeholder: 'NIM / Nama...' },
  {
    key: 'survey_status',
    label: 'Status Survei',
    type: 'select',
    options: [
      { value: 'belum_disurvei', label: 'Belum Disurvei' },
      { value: 'terkirim', label: 'Terkirim' },
      { value: 'sedang_mengisi', label: 'Sedang Mengisi' },
      { value: 'selesai', label: 'Selesai' },
    ],
  },
  { key: 'gender', label: 'Jenis Kelamin', type: 'select', options: [{ value: 'L', label: 'Laki-laki' }, { value: 'P', label: 'Perempuan' }] },
]

const surveyBadge = {
  belum_disurvei: { variant: 'muted', label: 'Belum Disurvei' },
  terkirim: { variant: 'info', label: 'Terkirim' },
  sedang_mengisi: { variant: 'warning', label: 'Sedang Mengisi' },
  selesai: { variant: 'success', label: 'Selesai' },
}

const filterValues = ref({ ...alumniStore.filters })

onMounted(() => alumniStore.fetchList())

function onSearch(filters) {
  Object.keys(filters).forEach((k) => alumniStore.setFilter(k, filters[k]))
  alumniStore.fetchList(1)
}

function onReset() {
  alumniStore.resetFilters()
  filterValues.value = { ...alumniStore.filters }
  alumniStore.fetchList(1)
}

function onPageChange(page) {
  alumniStore.fetchList(page)
}

function onSort({ key, dir }) {
  alumniStore.setFilter('sort_by', key)
  alumniStore.setFilter('sort_dir', dir)
  alumniStore.fetchList(1)
}

function openDelete(row) {
  deleteTarget.value = row
  showDeleteModal.value = true
}

async function confirmDelete() {
  deletingId.value = deleteTarget.value.id
  try {
    await alumniStore.destroy(deleteTarget.value.id)
    toast.success(`Alumni "${deleteTarget.value.full_name}" berhasil dihapus.`)
  } catch {
    toast.error('Gagal menghapus alumni.')
  } finally {
    deletingId.value = null
    showDeleteModal.value = false
    deleteTarget.value = null
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-[var(--color-text)]">Manajemen Alumni</h1>
        <p class="text-sm text-[var(--color-text-muted)]">Total {{ alumniStore.pagination.total }} alumni terdaftar</p>
      </div>
      <div class="flex gap-2">
        <router-link
          to="/admin/alumni/import"
          class="h-9 px-4 inline-flex items-center gap-2 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
          Import
        </router-link>
        <router-link
          to="/admin/alumni/create"
          class="h-9 px-4 inline-flex items-center gap-2 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
          Tambah Alumni
        </router-link>
      </div>
    </div>

    <!-- Filter -->
    <FilterBar
      v-model="filterValues"
      :filters="filterDefs"
      :loading="alumniStore.loading"
      @search="onSearch"
      @reset="onReset"
    />

    <!-- Table -->
    <DataTable
      :columns="columns"
      :rows="alumniStore.list"
      :loading="alumniStore.loading"
      :selected="selected"
      selectable
      row-key="id"
      empty-message="Belum ada data alumni."
      @sort="onSort"
      @select="(s) => (selected = s)"
      @select-all="(s) => (selected = s)"
    >
      <template #cell-study_program="{ row }">
        {{ row.study_program?.name ?? '—' }}
      </template>

      <template #cell-graduation_year="{ row }">
        {{ row.graduation_year?.year ?? '—' }}
      </template>

      <template #cell-survey_status="{ row }">
        <Badge
          :variant="surveyBadge[row.survey_status]?.variant ?? 'muted'"
          dot
        >
          {{ surveyBadge[row.survey_status]?.label ?? row.survey_status }}
        </Badge>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2 justify-end">
          <router-link
            :to="`/admin/alumni/${row.id}`"
            class="p-1.5 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] hover:text-[var(--color-primary)] transition-colors"
            aria-label="Detail alumni"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
          </router-link>
          <router-link
            :to="`/admin/alumni/${row.id}/edit`"
            class="p-1.5 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] hover:text-[var(--color-primary)] transition-colors"
            aria-label="Edit alumni"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
          </router-link>
          <button
            class="p-1.5 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-error-highlight)] hover:text-[var(--color-error)] transition-colors"
            aria-label="Hapus alumni"
            @click="openDelete(row)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Pagination -->
    <Pagination
      :current-page="alumniStore.pagination.current_page"
      :last-page="alumniStore.pagination.last_page"
      :total="alumniStore.pagination.total"
      :per-page="alumniStore.pagination.per_page"
      :loading="alumniStore.loading"
      @change="onPageChange"
    />

    <!-- Delete Confirm -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Hapus Alumni"
      :message="`Hapus alumni &quot;${deleteTarget?.full_name}&quot;? Data tidak akan hilang permanen (soft delete).`"
      confirm-label="Ya, Hapus"
      variant="danger"
      :loading="!!deletingId"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>
