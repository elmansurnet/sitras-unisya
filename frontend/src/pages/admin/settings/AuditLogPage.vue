<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

// ─── State ────────────────────────────────────────────────────────────────────
const logs        = ref([])
const loading     = ref(false)
const expandedId  = ref(null)
const pagination  = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })

const filters = ref({
  module:     '',
  action:     '',
  user_id:    '',
  date_from:  '',
  date_to:    '',
  search:     '',
})

const MODULES = ['alumni', 'employer', 'user', 'questionnaire', 'survey_period', 'setting', 'faculty', 'study_program', 'graduation_year']
const ACTIONS = ['created', 'updated', 'deleted', 'restored', 'login', 'logout', 'export']

const ACTION_STYLES = {
  created:  'bg-green-50 text-green-700 ring-green-600/20',
  updated:  'bg-blue-50 text-blue-700 ring-blue-600/20',
  deleted:  'bg-red-50 text-red-700 ring-red-600/20',
  restored: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  login:    'bg-teal-50 text-teal-700 ring-teal-600/20',
  logout:   'bg-gray-50 text-gray-600 ring-gray-500/20',
  export:   'bg-purple-50 text-purple-700 ring-purple-600/20',
}

// ─── Computed ─────────────────────────────────────────────────────────────────
const activeFilterCount = computed(() =>
  Object.values(filters.value).filter(v => v !== '').length
)

function actionStyle(action) {
  return ACTION_STYLES[action] ?? 'bg-gray-50 text-gray-600 ring-gray-500/20'
}

function parseJson(val) {
  if (!val) return null
  if (typeof val === 'object') return val
  try { return JSON.parse(val) } catch { return null }
}

function diffKeys(oldVal, newVal) {
  const o = parseJson(oldVal) ?? {}
  const n = parseJson(newVal) ?? {}
  const keys = new Set([...Object.keys(o), ...Object.keys(n)])
  return [...keys].filter(k => JSON.stringify(o[k]) !== JSON.stringify(n[k]))
}

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')),
    }
    const { data } = await api.get('/admin/audit-logs', { params })
    logs.value = data.data
    pagination.value = data.meta ?? {
      current_page: data.current_page,
      last_page:    data.last_page,
      total:        data.total,
      per_page:     data.per_page,
    }
  } catch {
    showToast('Gagal memuat audit log.', 'error')
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchLogs(1)
}

function clearFilters() {
  filters.value = { module: '', action: '', user_id: '', date_from: '', date_to: '', search: '' }
  fetchLogs(1)
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

// ─── Export CSV (client-side dari data halaman ini) ───────────────────────────
function exportCsv() {
  if (!logs.value.length) return
  const headers = ['ID', 'Waktu', 'User', 'Module', 'Action', 'Record ID', 'IP']
  const rows = logs.value.map(l => [
    l.id,
    l.created_at,
    l.user?.name ?? l.user_id ?? '-',
    l.module,
    l.action,
    l.record_id ?? '-',
    l.ip_address ?? '-',
  ])
  const csvContent = [headers, ...rows]
    .map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))
    .join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `audit-log-${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
  showToast('CSV berhasil diunduh.', 'success')
}

onMounted(() => fetchLogs(1))
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Audit Log</h1>
        <p class="mt-1 text-sm text-gray-500">Riwayat aktivitas sistem — read-only.</p>
      </div>
      <button
        @click="exportCsv"
        :disabled="!logs.length || loading"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Export CSV
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-gray-200 bg-white p-4">
      <div class="flex flex-wrap items-end gap-3">
        <!-- Search -->
        <div class="relative min-w-48 flex-1">
          <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
          </svg>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari user, module, record ID…"
            class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          />
        </div>
        <!-- Module -->
        <select v-model="filters.module" class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
          <option value="">Semua Modul</option>
          <option v-for="m in MODULES" :key="m" :value="m">{{ m }}</option>
        </select>
        <!-- Action -->
        <select v-model="filters.action" class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
          <option value="">Semua Aksi</option>
          <option v-for="a in ACTIONS" :key="a" :value="a">{{ a }}</option>
        </select>
        <!-- Date From -->
        <div class="flex flex-col gap-1">
          <label class="text-xs text-gray-400">Dari</label>
          <input v-model="filters.date_from" type="date" class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <!-- Date To -->
        <div class="flex flex-col gap-1">
          <label class="text-xs text-gray-400">Sampai</label>
          <input v-model="filters.date_to" type="date" class="rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
        </div>
        <!-- Apply -->
        <button
          @click="applyFilters"
          class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
        >Terapkan</button>
        <!-- Clear -->
        <button
          v-if="activeFilterCount > 0"
          @click="clearFilters"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
        >Reset ({{ activeFilterCount }})</button>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 8" :key="i" class="h-12 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- Empty -->
    <div v-else-if="logs.length === 0" class="flex flex-col items-center py-16 text-center">
      <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
      </svg>
      <p class="text-sm font-medium text-gray-500">Tidak ada log yang ditemukan.</p>
      <button v-if="activeFilterCount > 0" @click="clearFilters" class="mt-3 text-sm text-teal-600 hover:underline">Hapus filter</button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs w-36">Waktu</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Pengguna</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Modul</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Aksi</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Record ID</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider text-xs w-28">IP</th>
            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider text-xs w-16">Detail</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="log in logs" :key="log.id">
            <!-- Main row -->
            <tr
              class="hover:bg-gray-50 transition-colors cursor-pointer"
              :class="expandedId === log.id ? 'bg-teal-50/40' : ''"
              @click="toggleExpand(log.id)"
            >
              <td class="px-4 py-3 text-xs text-gray-500 tabular-nums whitespace-nowrap">
                {{ new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-600 text-xs font-semibold">
                    {{ (log.user?.name ?? '?').charAt(0).toUpperCase() }}
                  </div>
                  <span class="text-xs text-gray-700">{{ log.user?.name ?? log.user_id ?? 'System' }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="font-mono text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ log.module }}</span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="actionStyle(log.action)"
                >{{ log.action }}</span>
              </td>
              <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ log.record_id ?? '—' }}</td>
              <td class="px-4 py-3 text-xs font-mono text-gray-400">{{ log.ip_address ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <button
                  class="rounded-md p-1 text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-colors"
                  :title="expandedId === log.id ? 'Tutup detail' : 'Lihat detail'"
                  @click.stop="toggleExpand(log.id)"
                >
                  <svg
                    class="h-4 w-4 transition-transform"
                    :class="expandedId === log.id ? 'rotate-180' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
              </td>
            </tr>

            <!-- Expanded detail row -->
            <tr v-if="expandedId === log.id" :key="'detail-' + log.id">
              <td colspan="7" class="bg-gray-50 px-6 py-4">
                <div class="space-y-3">
                  <!-- Description -->
                  <p v-if="log.description" class="text-sm text-gray-600">{{ log.description }}</p>

                  <!-- Diff old vs new -->
                  <div v-if="log.old_values || log.new_values" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <!-- Old Values -->
                    <div v-if="log.old_values" class="rounded-lg border border-gray-200 bg-white">
                      <div class="border-b border-gray-200 px-3 py-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Nilai Lama</span>
                      </div>
                      <div class="p-3 space-y-1.5">
                        <template v-for="key in diffKeys(log.old_values, log.new_values)" :key="key">
                          <div class="flex items-start gap-2">
                            <span class="font-mono text-xs text-gray-400 min-w-28 shrink-0">{{ key }}</span>
                            <span class="text-xs text-red-600 bg-red-50 px-1.5 py-0.5 rounded break-all">
                              {{ parseJson(log.old_values)?.[key] ?? '—' }}
                            </span>
                          </div>
                        </template>
                        <p v-if="!diffKeys(log.old_values, log.new_values).length" class="text-xs text-gray-400">Tidak ada perubahan terdeteksi.</p>
                      </div>
                    </div>

                    <!-- New Values -->
                    <div v-if="log.new_values" class="rounded-lg border border-gray-200 bg-white">
                      <div class="border-b border-gray-200 px-3 py-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Nilai Baru</span>
                      </div>
                      <div class="p-3 space-y-1.5">
                        <template v-for="key in diffKeys(log.old_values, log.new_values)" :key="key">
                          <div class="flex items-start gap-2">
                            <span class="font-mono text-xs text-gray-400 min-w-28 shrink-0">{{ key }}</span>
                            <span class="text-xs text-green-700 bg-green-50 px-1.5 py-0.5 rounded break-all">
                              {{ parseJson(log.new_values)?.[key] ?? '—' }}
                            </span>
                          </div>
                        </template>
                        <p v-if="!diffKeys(log.old_values, log.new_values).length" class="text-xs text-gray-400">—</p>
                      </div>
                    </div>
                  </div>

                  <!-- Raw data jika tidak ada old/new (mis. login/export) -->
                  <div v-else-if="log.new_values || log.metadata" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-3 py-2">
                      <span class="text-xs font-semibold text-gray-500 uppercase">Metadata</span>
                    </div>
                    <pre class="overflow-x-auto p-3 text-xs text-gray-600">{{ JSON.stringify(parseJson(log.new_values ?? log.metadata), null, 2) }}</pre>
                  </div>

                  <!-- Meta info -->
                  <div class="flex flex-wrap gap-4 text-xs text-gray-400">
                    <span>Log ID: <span class="font-mono text-gray-600">{{ log.id }}</span></span>
                    <span v-if="log.user_agent">Agent: <span class="font-mono text-gray-600">{{ log.user_agent.slice(0, 60) }}…</span></span>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
        <span class="text-xs text-gray-500">
          Total {{ pagination.total }} log — Halaman {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <div class="flex items-center gap-1">
          <button
            @click="fetchLogs(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1 || loading"
            class="rounded-md border border-gray-300 p-1.5 text-gray-500 hover:bg-gray-50 disabled:opacity-40 transition-colors"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>
          <template v-for="page in pagination.last_page" :key="page">
            <button
              v-if="Math.abs(page - pagination.current_page) <= 2 || page === 1 || page === pagination.last_page"
              @click="fetchLogs(page)"
              class="min-w-8 rounded-md px-2 py-1 text-xs font-medium transition-colors"
              :class="page === pagination.current_page ? 'bg-teal-600 text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50'"
            >{{ page }}</button>
            <span
              v-else-if="page === pagination.current_page - 3 || page === pagination.current_page + 3"
              class="px-1 text-gray-400"
            >…</span>
          </template>
          <button
            @click="fetchLogs(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page || loading"
            class="rounded-md border border-gray-300 p-1.5 text-gray-500 hover:bg-gray-50 disabled:opacity-40 transition-colors"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
