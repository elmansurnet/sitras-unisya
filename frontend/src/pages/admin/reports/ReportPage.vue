<script setup>
/**
 * ReportPage.vue — Generate & Unduh Laporan (5B.10)
 * Route  : /admin/reports
 * Store  : useDashboardStore
 * Doc ref: 06_UI_UX.md §3.9, 05_API.md §8
 */
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useToast } from '@/composables/useToast'

const store       = useDashboardStore()
const { showToast } = useToast()

// ─── Opsi filter (diisi dari API summary / dihard-code sebagian) ─────────────
const periods        = ref([])
const graduationYears = ref([])
const studyPrograms  = ref([])

// ─── Form ────────────────────────────────────────────────────────────────────
const form = ref({
  period_id:          null,
  graduation_year_id: null,
  study_program_id:   null,
  format:             'pdf',   // 'pdf' | 'excel'
})

const formErrors = ref({})

function validate() {
  const errs = {}
  if (!form.value.period_id) errs.period_id = 'Periode survei wajib dipilih.'
  formErrors.value = errs
  return Object.keys(errs).length === 0
}

async function handleGenerate() {
  if (!validate()) return
  try {
    const { filename } = await store.generateReport({ ...form.value })
    showToast(`Laporan "${filename}" berhasil diunduh.`, 'success')
  } catch {
    showToast(store.error.generating ?? 'Gagal generate laporan. Coba lagi.', 'error')
  }
}

// ─── Tabel laporan tersimpan ─────────────────────────────────────────────────
const reportColumns = [
  { key: 'filename',     label: 'Nama File' },
  { key: 'format',      label: 'Format',  class: 'w-20 text-center' },
  { key: 'file_size',   label: 'Ukuran',  class: 'w-24 text-right' },
  { key: 'generated_by',label: 'Dibuat Oleh' },
  { key: 'created_at',  label: 'Tanggal', class: 'w-44' },
  { key: 'actions',     label: '',        class: 'w-24 text-right' },
]

function formatBytes(kb) {
  if (!kb) return '—'
  if (kb >= 1024) return (kb / 1024).toFixed(1) + ' MB'
  return kb + ' KB'
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

async function handleDownload(report) {
  try {
    await store.downloadReport(report.id, report.filename)
  } catch {
    showToast('Gagal mengunduh laporan.', 'error')
  }
}

const isGenerating = computed(() => store.loading.generating)
const isLoadingReports = computed(() => store.loading.reports)

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(async () => {
  // Muat opsi filter dari summary jika tersedia, lalu muat daftar laporan
  const s = store.summary
  if (s?.graduation_years) graduationYears.value = s.graduation_years
  if (s?.study_programs)   studyPrograms.value   = s.study_programs
  if (s?.survey_periods)   periods.value         = s.survey_periods

  await store.fetchReports()
})
</script>

<template>
  <div class="space-y-6">
    <!-- ── Page Header ── -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Laporan</h1>
      <p class="mt-1 text-sm text-gray-500">Generate dan unduh laporan tracer study dalam format PDF atau Excel</p>
    </div>

    <!-- ── Form Generate Laporan ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card p-6">
      <h2 class="text-base font-semibold text-gray-800 mb-5">Generate Laporan Baru</h2>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Periode Survei (required) -->
        <div>
          <label for="rpt-period" class="block text-sm font-medium text-gray-700 mb-1">
            Periode Survei <span class="text-red-500">*</span>
          </label>
          <select
            id="rpt-period"
            v-model="form.period_id"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            :class="formErrors.period_id ? 'border-red-500 focus:ring-red-500' : 'border-gray-300'"
            :aria-describedby="formErrors.period_id ? 'rpt-period-err' : undefined"
          >
            <option :value="null">-- Pilih Periode --</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <p v-if="formErrors.period_id" id="rpt-period-err" class="mt-1 text-xs text-red-600" role="alert">
            {{ formErrors.period_id }}
          </p>
        </div>

        <!-- Angkatan (opsional) -->
        <div>
          <label for="rpt-gradyear" class="block text-sm font-medium text-gray-700 mb-1">Angkatan</label>
          <select
            id="rpt-gradyear"
            v-model="form.graduation_year_id"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option :value="null">Semua Angkatan</option>
            <option v-for="y in graduationYears" :key="y.id" :value="y.id">{{ y.academic_year }}</option>
          </select>
        </div>

        <!-- Program Studi (opsional) -->
        <div>
          <label for="rpt-prodi" class="block text-sm font-medium text-gray-700 mb-1">Program Studi</label>
          <select
            id="rpt-prodi"
            v-model="form.study_program_id"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option :value="null">Semua Program Studi</option>
            <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
          </select>
        </div>
      </div>

      <!-- Format Output -->
      <div class="mt-5">
        <p class="text-sm font-medium text-gray-700 mb-2">Format Output</p>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.format"
              type="radio"
              value="pdf"
              class="text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">PDF</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.format"
              type="radio"
              value="excel"
              class="text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm text-gray-700">Excel (.xlsx)</span>
          </label>
        </div>
      </div>

      <!-- Tombol Generate -->
      <div class="mt-6 flex items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center gap-2 bg-primary-600 text-white rounded-lg px-5 py-2.5 text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isGenerating"
          @click="handleGenerate"
        >
          <!-- Spinner saat generating -->
          <svg
            v-if="isGenerating"
            class="animate-spin h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <svg
            v-else
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
          </svg>
          <span>{{ isGenerating ? 'Sedang membuat laporan…' : 'Generate Laporan' }}</span>
        </button>

        <p v-if="isGenerating" class="text-sm text-gray-500">Laporan akan otomatis diunduh setelah selesai.</p>
      </div>

      <!-- Progress overlay saat generating -->
      <div
        v-if="isGenerating"
        class="mt-4 bg-primary-50 border border-primary-200 rounded-lg p-4"
        role="status"
        aria-live="polite"
      >
        <div class="flex items-center gap-3">
          <div class="animate-spin rounded-full h-5 w-5 border-t-2 border-primary-600"></div>
          <div>
            <p class="text-sm font-medium text-primary-700">Sedang memproses laporan…</p>
            <p class="text-xs text-primary-500 mt-0.5">Harap tunggu. Proses ini mungkin memerlukan beberapa detik.</p>
          </div>
        </div>
        <div class="mt-3 h-1.5 bg-primary-200 rounded-full overflow-hidden">
          <div class="h-full bg-primary-600 rounded-full animate-pulse w-2/3"></div>
        </div>
      </div>
    </div>

    <!-- ── Tabel Laporan Tersimpan ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800">Laporan Tersimpan</h2>
        <button
          type="button"
          class="text-sm text-primary-600 hover:text-primary-700 transition-colors"
          :disabled="isLoadingReports"
          @click="store.fetchReports()"
        >
          {{ isLoadingReports ? 'Memuat…' : 'Segarkan' }}
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="isLoadingReports" class="p-6 space-y-3">
        <div v-for="i in 3" :key="i" class="h-10 skeleton rounded"></div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!store.reports.length"
        class="p-12 flex flex-col items-center justify-center text-gray-400"
      >
        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V7l-5-5H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <p class="text-sm font-medium text-gray-500">Belum ada laporan yang pernah dibuat.</p>
        <p class="text-xs text-gray-400 mt-1">Generate laporan pertama Anda menggunakan form di atas.</p>
      </div>

      <!-- Tabel data -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left">
              <th
                v-for="col in reportColumns" :key="col.key"
                class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide"
                :class="col.class"
                scope="col"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="report in store.reports"
              :key="report.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- Nama File -->
              <td class="px-4 py-3">
                <span class="text-gray-800 font-medium">{{ report.filename }}</span>
              </td>

              <!-- Format Badge -->
              <td class="px-4 py-3 text-center">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase"
                  :class="report.format === 'pdf'
                    ? 'bg-red-100 text-red-700'
                    : 'bg-emerald-100 text-emerald-700'"
                >
                  {{ report.format }}
                </span>
              </td>

              <!-- Ukuran -->
              <td class="px-4 py-3 text-right text-gray-500 tabular-nums">
                {{ formatBytes(report.file_size_kb) }}
              </td>

              <!-- Dibuat Oleh -->
              <td class="px-4 py-3 text-gray-600">
                {{ report.generated_by ?? '—' }}
              </td>

              <!-- Tanggal -->
              <td class="px-4 py-3 text-gray-500 text-xs">
                {{ formatDate(report.created_at) }}
              </td>

              <!-- Aksi -->
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-xs font-medium transition-colors"
                  :aria-label="`Unduh laporan ${report.filename}`"
                  @click="handleDownload(report)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Unduh
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}
.skeleton {
  background: linear-gradient(
    90deg,
    #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 0.25rem;
}
</style>
