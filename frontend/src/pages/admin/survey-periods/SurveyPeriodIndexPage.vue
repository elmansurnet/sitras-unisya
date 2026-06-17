<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyAdminStore } from '@/stores/surveyAdmin'
import { useToast } from '@/composables/useToast'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Pagination from '@/components/common/Pagination.vue'

const router = useRouter()
const store  = useSurveyAdminStore()
const { toast } = useToast()

const filterStatus  = ref('')
const confirmModal  = ref({ open: false, title: '', message: '', onConfirm: null })
const blastingId    = ref(null)
const activatingId  = ref(null)
const closingId     = ref(null)

const statusOptions = [
  { value: '',         label: 'Semua Status' },
  { value: 'draft',    label: 'Draft' },
  { value: 'aktif',    label: 'Aktif' },
  { value: 'ditutup',  label: 'Ditutup' },
]

const periods        = computed(() => store.periods ?? [])
const pagination     = computed(() => store.pagination)
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

onMounted(async () => { await loadPeriods() })

async function loadPeriods(page = 1) {
  try {
    await store.fetchPeriods({ page })
  } catch {
    toast.error('Gagal memuat daftar periode survei.')
  }
}

function goCreate()    { router.push({ name: 'admin.survey-periods.create' }) }
function goDetail(id)  { router.push({ name: 'admin.survey-periods.detail', params: { id } }) }

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
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Periode Survei</h1>
        <p class="text-sm text-gray-500">Kelola periode pelaksanaan tracer study</p>
      </div>
      <button
        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
        @click="goCreate"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat Periode
      </button>
    </div>

    <!-- Filter -->
    <div class="mb-4 flex items-center gap-3">
      <label class="text-sm font-medium text-gray-600" for="filter-status">Status:</label>
      <select
        id="filter-status"
        v-model="filterStatus"
        class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
      >
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex items-center justify-center py-24">
      <svg class="h-8 w-8 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="filteredPeriods.length === 0"
      class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-16 text-center shadow-sm"
    >
      <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="text-sm font-medium text-gray-600">
        {{ filterStatus ? 'Tidak ada periode dengan status ini' : 'Belum ada periode survei' }}
      </p>
      <p class="mt-1 text-xs text-gray-400">
        {{ filterStatus ? 'Coba ganti filter status' : 'Klik "Buat Periode" untuk memulai' }}
      </p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50 text-left">
              <th class="px-4 py-3 font-medium text-gray-600">Nama Periode</th>
              <th class="px-4 py-3 font-medium text-gray-600">Tahun Akademik</th>
              <th class="px-4 py-3 font-medium text-gray-600">Kuesioner</th>
              <th class="px-4 py-3 font-medium text-gray-600">Periode</th>
              <th class="px-4 py-3 font-medium text-gray-600">Status</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600">Respons</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr
              v-for="row in filteredPeriods"
              :key="row.id"
              class="transition-colors hover:bg-gray-50"
            >
              <!-- Nama -->
              <td class="px-4 py-3">
                <button
                  class="font-medium text-gray-900 hover:text-teal-700 text-left"
                  @click="goDetail(row.id)"
                >
                  {{ row.name }}
                </button>
              </td>

              <!-- Tahun Akademik -->
              <td class="px-4 py-3 text-gray-600">{{ row.academic_year ?? '—' }}</td>

              <!-- Kuesioner -->
              <td class="px-4 py-3 text-gray-600 max-w-[180px] truncate">
                {{ row.questionnaire?.title ?? '—' }}
              </td>

              <!-- Rentang tanggal -->
              <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                {{ formatDate(row.start_date) }} — {{ formatDate(row.end_date) }}
              </td>

              <!-- Status -->
              <td class="px-4 py-3">
                <Badge :variant="badgeVariant(row.status)">{{ badgeLabel(row.status) }}</Badge>
              </td>

              <!-- Respons -->
              <td class="px-4 py-3 text-center tabular-nums">
                <span class="font-medium text-gray-900">{{ row.response_count ?? 0 }}</span>
                <span class="text-gray-400"> / {{ row.target_alumni_count ?? '—' }}</span>
              </td>

              <!-- Aksi -->
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                  <!-- Detail -->
                  <button
                    class="rounded p-1.5 text-gray-400 hover:bg-teal-50 hover:text-teal-600 transition-colors"
                    title="Lihat Detail"
                    @click="goDetail(row.id)"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>

                  <!-- Aktifkan (draft only) -->
                  <button
                    v-if="row.status === 'draft'"
                    class="rounded p-1.5 text-gray-400 hover:bg-green-50 hover:text-green-600 transition-colors disabled:opacity-50"
                    :disabled="activatingId === row.id"
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

                  <!-- Kirim Undangan (aktif only) -->
                  <button
                    v-if="row.status === 'aktif'"
                    class="rounded p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-colors disabled:opacity-50"
                    :disabled="blastingId === row.id"
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

                  <!-- Tutup (aktif only) -->
                  <button
                    v-if="row.status === 'aktif'"
                    class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50"
                    :disabled="closingId === row.id"
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
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="border-t border-gray-100 px-4 py-3">
        <Pagination
          v-if="pagination?.last_page > 1"
          :meta="pagination"
          @change="loadPeriods"
        />
        <p v-else class="text-xs text-gray-400">
          Menampilkan {{ filteredPeriods.length }} periode
        </p>
      </div>
    </div>

    <!-- Confirm Modal -->
    <ConfirmModal
      v-model="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      @confirm="handleConfirm"
    />
  </div>
</template>
