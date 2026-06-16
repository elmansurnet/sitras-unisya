<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyAdminStore } from '@/stores/surveyAdmin'
import { useToast } from '@/composables/useToast'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Pagination from '@/components/common/Pagination.vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'

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
const filteredPeriods = computed(() =>
  !filterStatus.value
    ? periods.value
    : periods.value.filter((p) => p.status === filterStatus.value)
)

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
      <button class="btn btn-primary" @click="goCreate">+ Buat Periode</button>
    </div>

    <div class="filter-row">
      <label class="filter-label" for="filter-status">Status:</label>
      <select id="filter-status" v-model="filterStatus" class="select-input select-input--sm">
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>

    <!-- Loading skeleton -->
    <SkeletonLoader v-if="store.loading" variant="table" :rows="5" />

    <div v-else class="card">
      <DataTable
        :columns="columns"
        :rows="filteredPeriods"
        :loading="false"
        empty-message="Belum ada periode survei."
      >
        <!-- Nama periode — klik untuk detail -->
        <template #cell-name="{ row }">
          <button class="link-btn" @click="goDetail(row.id)">{{ row.name }}</button>
        </template>

        <!-- Kuesioner terkait -->
        <template #cell-questionnaire="{ row }">
          {{ row.questionnaire?.title ?? '-' }}
        </template>

        <!-- Rentang tanggal -->
        <template #cell-date_range="{ row }">
          <span class="date-range">
            {{ formatDate(row.start_date) }} — {{ formatDate(row.end_date) }}
          </span>
        </template>

        <!-- Badge status -->
        <template #cell-status="{ row }">
          <Badge :variant="badgeVariant(row.status)">{{ badgeLabel(row.status) }}</Badge>
        </template>

        <!-- Hitungan respons -->
        <template #cell-response_count="{ row }">
          <span class="response-count">
            {{ row.response_count ?? 0 }}
            <span class="response-total">/ {{ row.target_alumni_count ?? '—' }}</span>
          </span>
        </template>

        <!-- BUG #5 FIX: slot aksi yang sebelumnya tidak ada sama sekali -->
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-center gap-1">
            <!-- Tombol Detail -->
            <button
              class="btn-icon btn-icon--ghost"
              title="Lihat Detail"
              @click="goDetail(row.id)"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>

            <!-- Tombol Aktifkan — hanya jika status draft -->
            <button
              v-if="row.status === 'draft'"
              class="btn-icon btn-icon--success"
              :class="{ 'opacity-50 pointer-events-none': activatingId === row.id }"
              title="Aktifkan Periode"
              @click="confirmActivate(row)"
            >
              <svg v-if="activatingId === row.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

            <!-- Tombol Kirim Undangan Massal — hanya jika status aktif -->
            <button
              v-if="row.status === 'aktif'"
              class="btn-icon btn-icon--primary"
              :class="{ 'opacity-50 pointer-events-none': blastingId === row.id }"
              title="Kirim Undangan Massal"
              @click="confirmBlast(row)"
            >
              <svg v-if="blastingId === row.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </button>

            <!-- Tombol Tutup — hanya jika status aktif -->
            <button
              v-if="row.status === 'aktif'"
              class="btn-icon btn-icon--danger"
              :class="{ 'opacity-50 pointer-events-none': closingId === row.id }"
              title="Tutup Periode"
              @click="confirmClose(row)"
            >
              <svg v-if="closingId === row.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <Pagination
        v-if="pagination?.last_page > 1"
        :meta="pagination"
        @change="loadPeriods"
      />
    </div>

    <ConfirmModal
      v-model="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      @confirm="handleConfirm"
    />
  </div>
</template>
