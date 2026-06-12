<script setup>
/**
 * pages/admin/survey-periods/SurveyPeriodIndexPage.vue
 *
 * Halaman daftar Periode Survei untuk admin/superadmin.
 *
 * Fitur:
 *  - List semua periode survei (paginated)
 *  - Filter berdasarkan status (draft | aktif | ditutup)
 *  - Buat periode baru → navigasi ke SurveyPeriodDetailPage (mode create)
 *  - Per baris: Lihat Detail, Aktifkan, Tutup, Kirim Undangan (Blast)
 *  - Badge warna per status
 *  - Konfirmasi sebelum Aktifkan / Tutup / Blast
 *
 * Store   : useSurveyAdminStore (stores/surveyAdmin.js)
 * Komponen: DataTable, FilterBar, Badge, ConfirmModal, Pagination, useToast
 * Route   : /admin/survey-periods  → admin.survey-periods.index
 */
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyAdminStore } from '@/stores/surveyAdmin'
import { useToast } from '@/composables/useToast'
import DataTable    from '@/components/common/DataTable.vue'
import Badge        from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Pagination   from '@/components/common/Pagination.vue'

const router = useRouter()
const store  = useSurveyAdminStore()
const toast  = useToast()

// ---------------------------------------------------------------------------
// State lokal
// ---------------------------------------------------------------------------
const filterStatus   = ref('')
const confirmModal   = ref({ open: false, title: '', message: '', onConfirm: null })
const blastingId     = ref(null)   // id periode yang sedang di-blast
const activatingId   = ref(null)
const closingId      = ref(null)

// ---------------------------------------------------------------------------
// Kolom tabel
// ---------------------------------------------------------------------------
const columns = [
  { key: 'name',            label: 'Nama Periode',       sortable: true },
  { key: 'academic_year',   label: 'Tahun Akademik' },
  { key: 'questionnaire',   label: 'Kuesioner' },
  { key: 'date_range',      label: 'Periode' },
  { key: 'status',          label: 'Status' },
  { key: 'response_count',  label: 'Respons',            align: 'center' },
  { key: 'actions',         label: 'Aksi',               align: 'center' },
]

// ---------------------------------------------------------------------------
// Filter options
// ---------------------------------------------------------------------------
const statusOptions = [
  { value: '',        label: 'Semua Status' },
  { value: 'draft',   label: 'Draft' },
  { value: 'aktif',   label: 'Aktif' },
  { value: 'ditutup', label: 'Ditutup' },
]

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------
const periods = computed(() => store.periods ?? [])
const pagination = computed(() => store.pagination)

const filteredPeriods = computed(() => {
  if (!filterStatus.value) return periods.value
  return periods.value.filter((p) => p.status === filterStatus.value)
})

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

function badgeVariant(status) {
  const map = {
    draft   : 'secondary',
    aktif   : 'success',
    ditutup : 'danger',
  }
  return map[status] ?? 'secondary'
}

function badgeLabel(status) {
  const map = {
    draft   : 'Draft',
    aktif   : 'Aktif',
    ditutup : 'Ditutup',
  }
  return map[status] ?? status
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------
// Aksi tabel
// ---------------------------------------------------------------------------
function goCreate() {
  router.push({ name: 'admin.survey-periods.create' })
}

function goDetail(id) {
  router.push({ name: 'admin.survey-periods.detail', params: { id } })
}

function confirmActivate(period) {
  confirmModal.value = {
    open    : true,
    title   : 'Aktifkan Periode Survei',
    message : `Aktifkan periode "${period.name}"? Alumni akan bisa mengisi survei setelah diundang.`,
    onConfirm: () => doActivate(period.id),
  }
}

function confirmClose(period) {
  confirmModal.value = {
    open    : true,
    title   : 'Tutup Periode Survei',
    message : `Tutup periode "${period.name}"? Tindakan ini tidak dapat dibatalkan.`,
    onConfirm: () => doClose(period.id),
  }
}

function confirmBlast(period) {
  confirmModal.value = {
    open    : true,
    title   : 'Kirim Undangan Massal',
    message : `Kirim undangan survei ke semua alumni pada periode "${period.name}"? Proses ini berjalan di background queue.`,
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

function handleCancelConfirm() {
  confirmModal.value.open = false
}
</script>

<template>
  <div class="survey-period-index">
    <!-- ── Header ─────────────────────────────────────────────────────── -->
    <div class="page-header">
      <div class="page-header__left">
        <h1 class="page-title">Periode Survei</h1>
        <p class="page-subtitle">Kelola periode pelaksanaan tracer study</p>
      </div>
      <button class="btn btn-primary" @click="goCreate">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Buat Periode
      </button>
    </div>

    <!-- ── Filter ─────────────────────────────────────────────────────── -->
    <div class="filter-row">
      <label class="filter-label" for="filter-status">Status:</label>
      <select
        id="filter-status"
        v-model="filterStatus"
        class="select-input select-input--sm"
      >
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>

    <!-- ── Tabel ──────────────────────────────────────────────────────── -->
    <div class="card">
      <DataTable
        :columns="columns"
        :rows="filteredPeriods"
        :loading="store.loading"
        empty-message="Belum ada periode survei."
      >
        <!-- Nama Periode -->
        <template #cell-name="{ row }">
          <button
            class="link-btn"
            @click="goDetail(row.id)"
          >
            {{ row.name }}
          </button>
        </template>

        <!-- Kuesioner -->
        <template #cell-questionnaire="{ row }">
          {{ row.questionnaire?.title ?? '-' }}
        </template>

        <!-- Rentang Tanggal -->
        <template #cell-date_range="{ row }">
          <span class="date-range">
            {{ formatDate(row.start_date) }} — {{ formatDate(row.end_date) }}
          </span>
        </template>

        <!-- Status Badge -->
        <template #cell-status="{ row }">
          <Badge :variant="badgeVariant(row.status)">
            {{ badgeLabel(row.status) }}
          </Badge>
        </template>

        <!-- Jumlah Respons -->
        <template #cell-response_count="{ row }">
          <span class="response-count">
            {{ row.response_count ?? 0 }}
            <span class="response-total">/ {{ row.target_alumni_count ?? '—' }}</span>
          </span>
        </template>

        <!-- Aksi -->
        <template #cell-actions="{ row }">
          <div class="action-group">
            <!-- Lihat Detail -->
            <button
              class="btn btn-ghost btn-sm"
              title="Lihat / Edit Detail"
              @click="goDetail(row.id)"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              Detail
            </button>

            <!-- Aktifkan (hanya dari draft) -->
            <button
              v-if="row.status === 'draft'"
              class="btn btn-success btn-sm"
              :disabled="activatingId === row.id"
              title="Aktifkan Periode"
              @click="confirmActivate(row)"
            >
              <span v-if="activatingId === row.id" class="spinner spinner--xs" aria-hidden="true"/>
              <span v-else>Aktifkan</span>
            </button>

            <!-- Kirim Undangan (hanya saat aktif) -->
            <button
              v-if="row.status === 'aktif'"
              class="btn btn-primary btn-sm"
              :disabled="blastingId === row.id"
              title="Kirim Undangan Massal"
              @click="confirmBlast(row)"
            >
              <span v-if="blastingId === row.id" class="spinner spinner--xs" aria-hidden="true"/>
              <span v-else>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:inline;vertical-align:-2px;margin-right:3px">
                  <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Undangan
              </span>
            </button>

            <!-- Tutup (hanya saat aktif) -->
            <button
              v-if="row.status === 'aktif'"
              class="btn btn-danger btn-sm"
              :disabled="closingId === row.id"
              title="Tutup Periode"
              @click="confirmClose(row)"
            >
              <span v-if="closingId === row.id" class="spinner spinner--xs" aria-hidden="true"/>
              <span v-else>Tutup</span>
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Pagination -->
      <Pagination
        v-if="pagination && pagination.last_page > 1"
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        class="table-pagination"
        @change="loadPeriods"
      />
    </div>

    <!-- ── Confirm Modal ──────────────────────────────────────────────── -->
    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      @confirm="handleConfirm"
      @cancel="handleCancelConfirm"
    />
  </div>
</template>

<style scoped>
.survey-period-index { padding: var(--space-6); max-width: var(--content-wide); margin-inline: auto; }

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}
.page-title   { font-size: var(--text-xl); font-weight: 700; color: var(--color-text); margin: 0; }
.page-subtitle{ font-size: var(--text-sm); color: var(--color-text-muted); margin-top: var(--space-1); }

.filter-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin-bottom: var(--space-4);
}
.filter-label { font-size: var(--text-sm); color: var(--color-text-muted); white-space: nowrap; }

.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.link-btn {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  padding: 0;
  text-align: left;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.link-btn:hover { color: var(--color-primary-hover); }

.date-range   { font-size: var(--text-xs); color: var(--color-text-muted); white-space: nowrap; }

.response-count { font-size: var(--text-sm); font-weight: 600; }
.response-total { font-size: var(--text-xs); color: var(--color-text-muted); margin-left: 2px; }

.action-group {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
  justify-content: center;
}

.table-pagination { padding: var(--space-4) var(--space-6); border-top: 1px solid var(--color-border); }

/* ── Buttons (minimal, inherit dari global style) ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 500;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background var(--transition-interactive), color var(--transition-interactive), border-color var(--transition-interactive);
  white-space: nowrap;
}
.btn:disabled { opacity: 0.55; cursor: not-allowed; }

.btn-primary  { background: var(--color-primary); color: #fff; }
.btn-primary:hover:not(:disabled)  { background: var(--color-primary-hover); }

.btn-success  { background: var(--color-success); color: #fff; }
.btn-success:hover:not(:disabled)  { background: var(--color-success-hover); }

.btn-danger   { background: var(--color-notification); color: #fff; }
.btn-danger:hover:not(:disabled)   { background: var(--color-notification-hover); }

.btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
  border-color: var(--color-border);
}
.btn-ghost:hover:not(:disabled) { background: var(--color-surface-offset); color: var(--color-text); }

.btn-sm { padding: var(--space-1) var(--space-3); font-size: var(--text-xs); }

.select-input {
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-text);
  font-size: var(--text-sm);
  cursor: pointer;
}
.select-input--sm { padding: var(--space-1) var(--space-2); font-size: var(--text-xs); }

/* Spinner */
.spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.spinner--xs { width: 12px; height: 12px; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
