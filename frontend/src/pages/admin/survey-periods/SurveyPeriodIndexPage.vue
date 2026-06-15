<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyAdminStore } from '@/stores/surveyAdmin'
import { useToast } from '@/composables/useToast'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Pagination from '@/components/common/Pagination.vue'

const router = useRouter()
const store = useSurveyAdminStore()
const { toast } = useToast()

const filterStatus = ref('')
const confirmModal = ref({ open: false, title: '', message: '', onConfirm: null })
const blastingId = ref(null)
const activatingId = ref(null)
const closingId = ref(null)

const columns = [
  { key: 'name', label: 'Nama Periode', sortable: true },
  { key: 'academic_year', label: 'Tahun Akademik' },
  { key: 'questionnaire', label: 'Kuesioner' },
  { key: 'date_range', label: 'Periode' },
  { key: 'status', label: 'Status' },
  { key: 'response_count', label: 'Respons', align: 'center' },
  { key: 'actions', label: 'Aksi', align: 'center' },
]

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'aktif', label: 'Aktif' },
  { value: 'ditutup', label: 'Ditutup' },
]

const periods = computed(() => store.periods ?? [])
const pagination = computed(() => store.pagination)
const filteredPeriods = computed(() => !filterStatus.value ? periods.value : periods.value.filter((p) => p.status === filterStatus.value))

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function badgeVariant(status) {
  return { draft: 'secondary', aktif: 'success', ditutup: 'danger' }[status] ?? 'secondary'
}

function badgeLabel(status) {
  return { draft: 'Draft', aktif: 'Aktif', ditutup: 'Ditutup' }[status] ?? status
}

onMounted(async () => {
  await loadPeriods()
})

async function loadPeriods(page = 1) {
  try {
    await store.fetchPeriods({ page })
  } catch {
    toast.error('Gagal memuat daftar periode survei.')
  }
}

function goCreate() {
  router.push({ name: 'admin.survey-periods.create' })
}

function goDetail(id) {
  router.push({ name: 'admin.survey-periods.detail', params: { id } })
}

function confirmActivate(period) {
  confirmModal.value = {
    open: true,
    title: 'Aktifkan Periode Survei',
    message: `Aktifkan periode "${period.name}"? Alumni akan bisa mengisi survei setelah diundang.`,
    onConfirm: () => doActivate(period.id),
  }
}

function confirmClose(period) {
  confirmModal.value = {
    open: true,
    title: 'Tutup Periode Survei',
    message: `Tutup periode "${period.name}"? Tindakan ini tidak dapat dibatalkan.`,
    onConfirm: () => doClose(period.id),
  }
}

function confirmBlast(period) {
  confirmModal.value = {
    open: true,
    title: 'Kirim Undangan Massal',
    message: `Kirim undangan survei ke semua alumni pada periode "${period.name}"? Proses ini berjalan di background queue.`,
    onConfirm: () => doBlast(period.id, period.questionnaire_id),
  }
}

async function doActivate(id) {
  activatingId.value = id
  try {
    await store.activatePeriod(id)
    toast.success('Periode survei berhasil diaktifkan.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengaktifkan periode.')
  } finally {
    activatingId.value = null
  }
}

async function doClose(id) {
  closingId.value = id
  try {
    await store.closePeriod(id)
    toast.success('Periode survei berhasil ditutup.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menutup periode.')
  } finally {
    closingId.value = null
  }
}

async function doBlast(periodId, questionnaireId) {
  blastingId.value = periodId
  try {
    await store.blastInvitations(periodId, questionnaireId)
    toast.success('Undangan massal berhasil diantrekan. Proses berjalan di background.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengirim undangan massal.')
  } finally {
    blastingId.value = null
  }
}

function handleConfirm() {
  if (confirmModal.value.onConfirm) confirmModal.value.onConfirm()
  confirmModal.value.open = false
}
</script>

<template>
  <div class="survey-period-index">
    <div class="page-header">
      <div class="page-header__left">
        <h1 class="page-title">Periode Survei</h1>
        <p class="page-subtitle">Kelola periode pelaksanaan tracer study</p>
      </div>
      <button class="btn btn-primary" @click="goCreate">Buat Periode</button>
    </div>

    <div class="filter-row">
      <label class="filter-label" for="filter-status">Status:</label>
      <select id="filter-status" v-model="filterStatus" class="select-input select-input--sm">
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </div>

    <div class="card">
      <DataTable :columns="columns" :rows="filteredPeriods" :loading="store.loading" empty-message="Belum ada periode survei.">
        <template #cell-name="{ row }">
          <button class="link-btn" @click="goDetail(row.id)">{{ row.name }}</button>
        </template>
        <template #cell-questionnaire="{ row }">{{ row.questionnaire?.title ?? '-' }}</template>
        <template #cell-date_range="{ row }"><span class="date-range">{{ formatDate(row.start_date) }} — {{ formatDate(row.end_date) }}</span></template>
        <template #cell-status="{ row }"><Badge :variant="badgeVariant(row.status)">{{ badgeLabel(row.status) }}</Badge></template>
        <template #cell-response_count="{ row }"><span class="response-count">{{ row.response_count ?? 0 }} <span class="response-total">/ {{ row.target_alumni_count ?? '—' }}</span></span></template>
      </DataTable>
      <Pagination v-if="pagination?.last_page > 1" :meta="pagination" @change="loadPeriods" />
    </div>

    <ConfirmModal
      v-model="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      @confirm="handleConfirm"
    />
  </div>
</template>
