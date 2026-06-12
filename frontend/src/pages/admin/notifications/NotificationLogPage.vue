<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import Pagination from '@/components/common/Pagination.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'

// ─── Store & Composables ────────────────────────────────────────────────────
const store          = useNotificationStore()
const { showToast }  = useToast()

// ─── State Lokal ────────────────────────────────────────────────────────────
const localFilters = ref({
  type           : '',
  status         : '',
  recipient_type : '',
  date_from      : '',
  date_to        : '',
})

// Modal detail log
const showDetailModal = ref(false)
const selectedLog     = ref(null)

// ─── Kolom DataTable ─────────────────────────────────────────────────────────
const columns = [
  { key: 'type',           label: 'Channel',     width: '110px' },
  { key: 'recipient',      label: 'Penerima',    sortable: true },
  { key: 'recipient_type', label: 'Tipe',        width: '110px' },
  { key: 'template',       label: 'Template' },
  { key: 'status',         label: 'Status',      width: '120px' },
  { key: 'sent_at',        label: 'Waktu Kirim', sortable: true, width: '160px' },
  { key: 'actions',        label: 'Detail',      width: '80px' },
]

// ─── Filter Options ──────────────────────────────────────────────────────────
const TYPE_OPTIONS = [
  { value: '',          label: 'Semua Channel' },
  { value: 'whatsapp',  label: 'WhatsApp' },
  { value: 'email',     label: 'Email' },
]

const STATUS_OPTIONS = [
  { value: '',           label: 'Semua Status' },
  { value: 'pending',    label: 'Pending' },
  { value: 'sent',       label: 'Terkirim' },
  { value: 'failed',     label: 'Gagal' },
  { value: 'delivered',  label: 'Delivered' },
]

const RECIPIENT_OPTIONS = [
  { value: '',          label: 'Semua Tipe' },
  { value: 'alumni',    label: 'Alumni' },
  { value: 'employer',  label: 'Employer' },
]

// ─── Computed: ringkasan total log ───────────────────────────────────────────
const totalInfo = computed(() => {
  const { total, current_page, per_page } = store.pagination
  const from = (current_page - 1) * per_page + 1
  const to   = Math.min(current_page * per_page, total)
  return total > 0 ? `Menampilkan ${from}–${to} dari ${total} log` : 'Tidak ada log ditemukan'
})

// ─── Fetch ───────────────────────────────────────────────────────────────────
async function loadLogs() {
  try {
    await store.fetchLogs()
  } catch {
    showToast('Gagal memuat log notifikasi.', 'error')
  }
}

onMounted(() => {
  // Sinkronkan localFilters dengan store.filters
  localFilters.value = {
    type           : store.filters.type,
    status         : store.filters.status,
    recipient_type : store.filters.recipient_type,
    date_from      : store.filters.date_from,
    date_to        : store.filters.date_to,
  }
  loadLogs()
})

// ─── Filter ──────────────────────────────────────────────────────────────────
async function applyFilter() {
  try {
    await store.setLogFilters({ ...localFilters.value })
  } catch {
    showToast('Gagal memfilter log.', 'error')
  }
}

async function resetFilter() {
  localFilters.value = { type: '', status: '', recipient_type: '', date_from: '', date_to: '' }
  try {
    await store.resetLogFilters()
  } catch {
    showToast('Gagal mereset filter.', 'error')
  }
}

async function handlePageChange(page) {
  try {
    await store.setLogPage(page)
  } catch {
    showToast('Gagal memuat halaman.', 'error')
  }
}

// ─── Detail Modal ────────────────────────────────────────────────────────────
function openDetail(log) {
  selectedLog.value     = log
  showDetailModal.value = true
}

// ─── Helper ──────────────────────────────────────────────────────────────────
function formatDateTime(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function statusVariant(status) {
  const map = {
    pending   : 'draft',
    sent      : 'active',
    failed    : 'danger',
    delivered : 'success',
  }
  return map[status] ?? 'draft'
}

function statusLabel(status) {
  const map = {
    pending   : 'Pending',
    sent      : 'Terkirim',
    failed    : 'Gagal',
    delivered : 'Delivered',
  }
  return map[status] ?? status
}

function formatProviderResponse(raw) {
  if (!raw) return '-'
  if (typeof raw === 'object') return JSON.stringify(raw, null, 2)
  return raw
}
</script>

<template>
  <AdminLayout>
    <template #default>
      <!-- ───────────── PAGE HEADER ───────────── -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Log Pengiriman Notifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">Riwayat semua pengiriman notifikasi WhatsApp dan Email dari sistem.</p>
      </div>

      <!-- ───────────── FILTER BAR ───────────── -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
          <!-- Channel -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
            <select
              v-model="localFilters.type"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="localFilters.status"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>

          <!-- Tipe Penerima -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Penerima</label>
            <select
              v-model="localFilters.recipient_type"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="opt in RECIPIENT_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>

          <!-- Rentang Tanggal -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
            <input
              v-model="localFilters.date_from"
              type="date"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
            <input
              v-model="localFilters.date_to"
              type="date"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>

          <!-- Tombol -->
          <div class="flex gap-2">
            <button
              class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors"
              :disabled="store.logLoading"
              @click="applyFilter"
            >Filter</button>
            <button
              class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors"
              :disabled="store.logLoading"
              @click="resetFilter"
            >Reset</button>
          </div>
        </div>
      </div>

      <!-- ───────────── INFO TOTAL ───────────── -->
      <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-500">{{ totalInfo }}</p>
      </div>

      <!-- ───────────── TABEL LOG ───────────── -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100">
        <DataTable
          :columns="columns"
          :data="store.logs"
          :loading="store.logLoading"
          empty-text="Belum ada log pengiriman notifikasi."
        >
          <!-- Channel -->
          <template #cell-type="{ row }">
            <Badge
              :label="row.type === 'whatsapp' ? 'WhatsApp' : 'Email'"
              :variant="row.type === 'whatsapp' ? 'success' : 'info'"
            />
          </template>

          <!-- Penerima -->
          <template #cell-recipient="{ row }">
            <span class="text-sm text-gray-800 font-mono">{{ row.recipient }}</span>
          </template>

          <!-- Tipe Penerima -->
          <template #cell-recipient_type="{ row }">
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full capitalize">
              {{ row.recipient_type === 'alumni' ? 'Alumni' : 'Employer' }}
            </span>
          </template>

          <!-- Template -->
          <template #cell-template="{ row }">
            <span class="text-sm text-gray-700">
              {{ row.template?.name ?? '-' }}
            </span>
          </template>

          <!-- Status -->
          <template #cell-status="{ row }">
            <Badge
              :label="statusLabel(row.status)"
              :variant="statusVariant(row.status)"
            />
          </template>

          <!-- Waktu Kirim -->
          <template #cell-sent_at="{ row }">
            <span class="text-sm text-gray-500">{{ formatDateTime(row.sent_at) }}</span>
          </template>

          <!-- Aksi detail -->
          <template #cell-actions="{ row }">
            <button
              class="text-gray-400 hover:text-primary-600 transition-colors"
              title="Lihat Detail"
              @click="openDetail(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </template>
        </DataTable>

        <!-- Pagination -->
        <div v-if="store.pagination.last_page > 1" class="px-6 py-4 border-t border-gray-100">
          <Pagination
            :current-page="store.pagination.current_page"
            :last-page="store.pagination.last_page"
            :total="store.pagination.total"
            :per-page="store.pagination.per_page"
            @page-change="handlePageChange"
          />
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           MODAL DETAIL LOG
      ════════════════════════════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="showDetailModal && selectedLog"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Detail Log Notifikasi"
          >
            <!-- Overlay -->
            <div class="absolute inset-0 bg-gray-900/50" @click="showDetailModal = false" />

            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-lg w-full max-w-lg max-h-[85vh] flex flex-col">
              <!-- Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Detail Log Notifikasi</h2>
                <button class="text-gray-400 hover:text-gray-600" @click="showDetailModal = false" aria-label="Tutup">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <!-- Metadata -->
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Channel</p>
                    <Badge
                      :label="selectedLog.type === 'whatsapp' ? 'WhatsApp' : 'Email'"
                      :variant="selectedLog.type === 'whatsapp' ? 'success' : 'info'"
                    />
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Status</p>
                    <Badge
                      :label="statusLabel(selectedLog.status)"
                      :variant="statusVariant(selectedLog.status)"
                    />
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Penerima</p>
                    <p class="text-sm font-mono text-gray-800">{{ selectedLog.recipient }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Tipe Penerima</p>
                    <p class="text-sm text-gray-800 capitalize">{{ selectedLog.recipient_type }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Template</p>
                    <p class="text-sm text-gray-800">{{ selectedLog.template?.name ?? '-' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Waktu Kirim</p>
                    <p class="text-sm text-gray-800">{{ formatDateTime(selectedLog.sent_at) }}</p>
                  </div>
                </div>

                <!-- Subject (jika email) -->
                <div v-if="selectedLog.subject">
                  <p class="text-xs font-medium text-gray-500 mb-1">Subject</p>
                  <p class="text-sm text-gray-800">{{ selectedLog.subject }}</p>
                </div>

                <!-- Error Message -->
                <div v-if="selectedLog.error_message">
                  <p class="text-xs font-medium text-red-500 mb-1">Pesan Error</p>
                  <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                    <p class="text-sm text-red-700">{{ selectedLog.error_message }}</p>
                  </div>
                </div>

                <!-- Provider Response (ditampilkan jika ada, terutama saat gagal) -->
                <div v-if="selectedLog.provider_response">
                  <p class="text-xs font-medium text-gray-500 mb-1">Provider Response</p>
                  <pre class="whitespace-pre-wrap text-xs text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-200 overflow-x-auto font-mono">{{ formatProviderResponse(selectedLog.provider_response) }}</pre>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end px-6 py-4 border-t border-gray-200">
                <button
                  class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                  @click="showDetailModal = false"
                >Tutup</button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>
  </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 150ms ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>
