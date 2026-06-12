<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Log Pengiriman Notifikasi</h1>
      <p class="mt-1 text-sm text-gray-500">Riwayat semua notifikasi yang telah dikirim oleh sistem.</p>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-card">
      <div class="flex flex-wrap items-end gap-3">
        <!-- Channel -->
        <div>
          <label for="fl-type" class="mb-1 block text-xs font-medium text-gray-600">Channel</label>
          <select
            id="fl-type"
            v-model="localFilters.type"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">Semua</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="email">Email</option>
          </select>
        </div>

        <!-- Status -->
        <div>
          <label for="fl-status" class="mb-1 block text-xs font-medium text-gray-600">Status</label>
          <select
            id="fl-status"
            v-model="localFilters.status"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">Semua</option>
            <option value="pending">Pending</option>
            <option value="sent">Terkirim</option>
            <option value="failed">Gagal</option>
            <option value="delivered">Delivered</option>
          </select>
        </div>

        <!-- Penerima -->
        <div>
          <label for="fl-recipient" class="mb-1 block text-xs font-medium text-gray-600">Penerima</label>
          <select
            id="fl-recipient"
            v-model="localFilters.recipient_type"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">Semua</option>
            <option value="alumni">Alumni</option>
            <option value="employer">Employer</option>
          </select>
        </div>

        <!-- Date From -->
        <div>
          <label for="fl-from" class="mb-1 block text-xs font-medium text-gray-600">Dari Tanggal</label>
          <input
            id="fl-from"
            v-model="localFilters.date_from"
            type="date"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>

        <!-- Date To -->
        <div>
          <label for="fl-to" class="mb-1 block text-xs font-medium text-gray-600">Sampai Tanggal</label>
          <input
            id="fl-to"
            v-model="localFilters.date_to"
            type="date"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>

        <!-- Aksi Filter -->
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors"
            @click="applyFilter"
          >
            Filter
          </button>
          <button
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            @click="resetFilter"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="notificationStore.logLoading && !logs.length" class="space-y-3">
      <div v-for="n in 8" :key="n" class="h-12 rounded-xl bg-gray-100 animate-pulse" />
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!notificationStore.logLoading && !logs.length"
      class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center"
    >
      <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <h3 class="mt-4 text-sm font-semibold text-gray-900">Belum ada log pengiriman</h3>
      <p class="mt-1 text-sm text-gray-500">Log akan muncul setelah notifikasi pertama dikirim oleh sistem.</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-card">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Channel</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Penerima</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Template</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Waktu Kirim</th>
              <th scope="col" class="relative px-4 py-3"><span class="sr-only">Detail</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="log in logs"
              :key="log.id"
              class="cursor-pointer hover:bg-gray-50 transition-colors"
              @click="openDetail(log)"
            >
              <td class="px-6 py-3">
                <span :class="log.type === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium">
                  <span v-if="log.type === 'whatsapp'">💬</span>
                  <span v-else>✉️</span>
                  {{ log.type === 'whatsapp' ? 'WhatsApp' : 'Email' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="text-sm font-medium text-gray-900">{{ log.recipient }}</div>
                <div class="text-xs text-gray-400 capitalize">{{ log.recipient_type }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ log.template?.name ?? '-' }}
              </td>
              <td class="px-4 py-3">
                <span :class="statusBadgeClass(log.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ statusLabel(log.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ formatDateTime(log.sent_at) }}
              </td>
              <td class="px-4 py-3 text-right">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-gray-200 bg-white px-6 py-3">
        <p class="text-sm text-gray-500">
          Menampilkan
          <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
          –
          <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
          dari <span class="font-medium">{{ pagination.total }}</span> log
        </p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            :disabled="pagination.current_page === 1"
            class="rounded-lg border border-gray-300 p-2 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            aria-label="Halaman sebelumnya"
            @click="changePage(pagination.current_page - 1)"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <template v-for="p in visiblePages" :key="p">
            <span v-if="p === '...'" class="px-2 text-gray-400">…</span>
            <button
              v-else
              type="button"
              class="h-8 w-8 rounded-lg text-sm font-medium transition-colors"
              :class="p === pagination.current_page ? 'bg-primary-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50'"
              @click="changePage(p)"
            >
              {{ p }}
            </button>
          </template>

          <button
            type="button"
            :disabled="pagination.current_page === pagination.last_page"
            class="rounded-lg border border-gray-300 p-2 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            aria-label="Halaman berikutnya"
            @click="changePage(pagination.current_page + 1)"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Detail Modal ─── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDetail"
          class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="detail-modal-title"
          @mousedown.self="showDetail = false"
        >
          <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
              <h2 id="detail-modal-title" class="text-base font-semibold text-gray-900">Detail Log Notifikasi</h2>
              <button
                type="button"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                aria-label="Tutup detail"
                @click="showDetail = false"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-if="detailLog" class="space-y-4 px-6 py-5">
              <!-- Meta row -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">Channel</p>
                  <span :class="detailLog.type === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'" class="mt-1 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium">
                    {{ detailLog.type === 'whatsapp' ? '💬 WhatsApp' : '✉️ Email' }}
                  </span>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Status</p>
                  <span :class="statusBadgeClass(detailLog.status)" class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                    {{ statusLabel(detailLog.status) }}
                  </span>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Penerima</p>
                  <p class="mt-0.5 text-sm text-gray-800">{{ detailLog.recipient }}</p>
                  <p class="text-xs text-gray-400 capitalize">{{ detailLog.recipient_type }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Waktu Kirim</p>
                  <p class="mt-0.5 text-sm text-gray-800">{{ formatDateTime(detailLog.sent_at) }}</p>
                </div>
              </div>

              <!-- Template -->
              <div v-if="detailLog.template">
                <p class="text-xs font-medium text-gray-500">Template</p>
                <p class="mt-0.5 text-sm text-gray-800">{{ detailLog.template.name }}</p>
              </div>

              <!-- Subject (email) -->
              <div v-if="detailLog.subject">
                <p class="text-xs font-medium text-gray-500">Subject</p>
                <p class="mt-0.5 text-sm text-gray-800">{{ detailLog.subject }}</p>
              </div>

              <!-- Error Message -->
              <div v-if="detailLog.error_message">
                <p class="text-xs font-medium text-red-500">Pesan Error</p>
                <p class="mt-0.5 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ detailLog.error_message }}</p>
              </div>

              <!-- Provider Response -->
              <div v-if="detailLog.provider_response">
                <p class="text-xs font-medium text-gray-500">Response Provider</p>
                <pre class="mt-0.5 max-h-40 overflow-y-auto rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700">{{ JSON.stringify(detailLog.provider_response, null, 2) }}</pre>
              </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 px-6 py-4">
              <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                @click="showDetail = false"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'

const notificationStore = useNotificationStore()

const logs       = computed(() => notificationStore.logs)
const pagination = computed(() => notificationStore.pagination)

// ─── Local Filters ────────────────────────────────────────────────────────
const localFilters = ref({
  type           : '',
  status         : '',
  recipient_type : '',
  date_from      : '',
  date_to        : '',
})

async function applyFilter() {
  await notificationStore.setLogFilters({ ...localFilters.value })
}

async function resetFilter() {
  localFilters.value = { type: '', status: '', recipient_type: '', date_from: '', date_to: '' }
  await notificationStore.resetLogFilters()
}

async function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  await notificationStore.setLogPage(page)
}

// ─── Visible Pages (smart pagination) ───────────────────────────────────
const visiblePages = computed(() => {
  const { current_page: cur, last_page: last } = pagination.value
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = new Set([1, last, cur, cur - 1, cur + 1].filter((p) => p >= 1 && p <= last))
  const sorted = [...pages].sort((a, b) => a - b)
  const result = []
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...')
    result.push(sorted[i])
  }
  return result
})

// ─── Detail Modal ─────────────────────────────────────────────────────────
const showDetail = ref(false)
const detailLog  = ref(null)

function openDetail(log) {
  detailLog.value  = log
  showDetail.value = true
}

// ─── Helpers ──────────────────────────────────────────────────────────────
const STATUS_LABEL = {
  pending   : 'Pending',
  sent      : 'Terkirim',
  failed    : 'Gagal',
  delivered : 'Delivered',
}

const STATUS_CLASS = {
  pending   : 'bg-gray-100 text-gray-600',
  sent      : 'bg-blue-100 text-blue-700',
  failed    : 'bg-red-100 text-red-700',
  delivered : 'bg-green-100 text-green-700',
}

function statusLabel(status) {
  return STATUS_LABEL[status] ?? status
}

function statusBadgeClass(status) {
  return STATUS_CLASS[status] ?? 'bg-gray-100 text-gray-600'
}

function formatDateTime(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

// ─── Init ─────────────────────────────────────────────────────────────────
onMounted(() => {
  notificationStore.fetchLogs()
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 150ms ease;
}
.modal-enter-active .w-full,
.modal-leave-active .w-full {
  transition: transform 150ms ease, opacity 150ms ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .w-full {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to .w-full {
  opacity: 0;
  transform: scale(0.95);
}
</style>
