<script setup>
/**
 * StatisticsPage.vue — Statistik Ketenagakerjaan Detail (5B.9)
 * Route  : /admin/dashboard/stats
 * Store  : useDashboardStore
 * Doc ref: 06_UI_UX.md §3.1 (employment stats), §8 (routing)
 */
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BarChart from '@/components/charts/BarChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import AlumniMap from '@/components/charts/AlumniMap.vue'

const store = useDashboardStore()

// ─── Filter ──────────────────────────────────────────────────────────────────
const periods           = ref([])   // diisi dari store.summary setelah mount
const graduationYears   = ref([])
const studyPrograms     = ref([])

const selectedPeriod     = ref(null)
const selectedGradYear   = ref(null)
const selectedStudyProg  = ref(null)

async function applyFilter() {
  await store.fetchStatistics({
    period_id:           selectedPeriod.value   || null,
    graduation_year_id:  selectedGradYear.value || null,
    study_program_id:    selectedStudyProg.value|| null,
  })
}

function resetFilter() {
  selectedPeriod.value    = null
  selectedGradYear.value  = null
  selectedStudyProg.value = null
  store.resetFilters()
  store.fetchStatistics()
}

// ─── Computed — data untuk komponen chart ────────────────────────────────────
const stats = computed(() => store.employmentStats)

/** Bar Chart: Top 10 industri */
const barSeries      = computed(() => [{
  name: 'Jumlah Alumni',
  data: store.topIndustries.map(i => i.count),
}])
const barCategories  = computed(() => store.topIndustries.map(i => i.sector))

/** Donut Chart: status pekerjaan */
const donutSeries = computed(() => store.donutSeries)
const donutLabels = ['Bekerja', 'Wirausaha', 'Lanjut Studi', 'Belum Bekerja']

/** Line Chart: Tingkat serapan per angkatan */
const lineSeries     = computed(() => [{
  name: 'Tingkat Bekerja (%)',
  data: stats.value.by_graduation_year.map(y => +y.rate.toFixed(1)),
}])
const lineCategories = computed(() =>
  stats.value.by_graduation_year.map(y => y.academic_year)
)

/** Tabel: per program studi */
const programColumns = [
  { key: 'name',     label: 'Program Studi' },
  { key: 'total',    label: 'Total Alumni',  class: 'text-right' },
  { key: 'employed', label: 'Bekerja',       class: 'text-right' },
  { key: 'rate',     label: 'Tingkat (%)',   class: 'text-right' },
]

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(async () => {
  // Data awal: statistik + peta
  await store.fetchStatistics()

  // Bangun option list dari summary jika tersedia
  const s = store.summary
  if (s?.graduation_years)  graduationYears.value  = s.graduation_years
  if (s?.study_programs)    studyPrograms.value    = s.study_programs
  if (s?.survey_periods)    periods.value          = s.survey_periods
})
</script>

<template>
  <div class="space-y-6">
    <!-- ── Page Header ── -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Statistik Ketenagakerjaan</h1>
        <p class="mt-1 text-sm text-gray-500">Analisis serapan tenaga kerja dan distribusi alumni</p>
      </div>
    </div>

    <!-- ── Filter Bar ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card p-4">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        <!-- Periode Survei -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Periode Survei</label>
          <select
            v-model="selectedPeriod"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option :value="null">Semua Periode</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>

        <!-- Angkatan -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Angkatan</label>
          <select
            v-model="selectedGradYear"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option :value="null">Semua Angkatan</option>
            <option v-for="y in graduationYears" :key="y.id" :value="y.id">
              {{ y.academic_year }}
            </option>
          </select>
        </div>

        <!-- Program Studi -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Program Studi</label>
          <select
            v-model="selectedStudyProg"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option :value="null">Semua Program Studi</option>
            <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
          </select>
        </div>

        <!-- Tombol -->
        <div class="flex items-end gap-2">
          <button
            type="button"
            class="flex-1 bg-primary-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
            :disabled="store.loading.employmentStats || store.loading.mapData"
            @click="applyFilter"
          >
            <span v-if="store.loading.employmentStats">Memuat…</span>
            <span v-else>Filter</span>
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            @click="resetFilter"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <!-- Tingkat Bekerja -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tingkat Bekerja</p>
        <div v-if="store.loading.employmentStats" class="mt-2 h-8 skeleton skeleton-text w-24"></div>
        <p v-else class="mt-2 text-3xl font-bold text-primary-600">
          {{ stats.employment_rate?.toFixed(1) ?? '—' }}<span class="text-lg font-medium">%</span>
        </p>
        <p class="mt-1 text-xs text-gray-400">dari total alumni tersurvei</p>
      </div>

      <!-- Rata-rata Masa Tunggu -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Rata-rata Masa Tunggu</p>
        <div v-if="store.loading.employmentStats" class="mt-2 h-8 skeleton skeleton-text w-24"></div>
        <p v-else class="mt-2 text-3xl font-bold text-gray-800">
          {{ stats.average_waiting_months ?? '—' }}<span class="text-base font-medium text-gray-500"> bln</span>
        </p>
        <p class="mt-1 text-xs text-gray-400">setelah wisuda hingga bekerja</p>
      </div>

      <!-- Relevansi Bidang Kerja -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Relevansi Bidang Kerja</p>
        <div v-if="store.loading.employmentStats" class="mt-2 h-8 skeleton skeleton-text w-24"></div>
        <p v-else class="mt-2 text-3xl font-bold text-emerald-600">
          {{ stats.relevance_rate?.toFixed(1) ?? '—' }}<span class="text-lg font-medium">%</span>
        </p>
        <p class="mt-1 text-xs text-gray-400">pekerjaan sesuai bidang studi</p>
      </div>

      <!-- Data Prodi -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Program Studi</p>
        <div v-if="store.loading.employmentStats" class="mt-2 h-8 skeleton skeleton-text w-16"></div>
        <p v-else class="mt-2 text-3xl font-bold text-gray-800">
          {{ stats.by_study_program?.length ?? 0 }}
        </p>
        <p class="mt-1 text-xs text-gray-400">prodi dengan data tersedia</p>
      </div>
    </div>

    <!-- ── Baris Charts: Bar + Donut ── -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Bar Chart: Top 10 Industri -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Top 10 Industri Penyerap Alumni</h3>
        <div v-if="store.loading.employmentStats" class="h-64 flex items-center justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-primary-600"></div>
        </div>
        <div v-else-if="!stats.by_industry?.length" class="h-64 flex flex-col items-center justify-center text-gray-400">
          <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V7l-5-5H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <p class="text-sm">Belum ada data industri</p>
        </div>
        <BarChart
          v-else
          :series="barSeries"
          :categories="barCategories"
          :height="300"
          horizontal
        />
      </div>

      <!-- Donut Chart: Status Pekerjaan -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Distribusi Status Pekerjaan</h3>
        <div v-if="store.loading.employmentStats" class="h-64 flex items-center justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-primary-600"></div>
        </div>
        <div v-else-if="donutSeries.every(v => v === 0)" class="h-64 flex flex-col items-center justify-center text-gray-400">
          <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke-width="1.5" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01" />
          </svg>
          <p class="text-sm">Belum ada data pekerjaan</p>
        </div>
        <DonutChart
          v-else
          :series="donutSeries"
          :labels="donutLabels"
        />
      </div>
    </div>

    <!-- ── Line Chart: Tren per Angkatan ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
      <h3 class="text-sm font-semibold text-gray-700 mb-4">Tingkat Serapan per Angkatan</h3>
      <div v-if="store.loading.employmentStats" class="h-48 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-primary-600"></div>
      </div>
      <div v-else-if="!stats.by_graduation_year?.length" class="h-48 flex flex-col items-center justify-center text-gray-400">
        <p class="text-sm">Belum ada data per angkatan</p>
      </div>
      <LineChart
        v-else
        :series="lineSeries"
        :categories="lineCategories"
        :height="220"
        y-label="%"
      />
    </div>

    <!-- ── Peta Sebaran ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card p-5">
      <h3 class="text-sm font-semibold text-gray-700 mb-4">Sebaran Domisili Alumni</h3>
      <div v-if="store.loading.mapData" class="h-80 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-primary-600"></div>
      </div>
      <div v-else-if="!store.mapData?.length" class="h-80 flex flex-col items-center justify-center text-gray-400">
        <p class="text-sm">Belum ada data koordinat alumni</p>
      </div>
      <AlumniMap
        v-else
        :markers="store.mapData"
        center="[-2.5, 118]"
        :zoom="5"
        class="h-80 rounded-lg overflow-hidden"
      />
    </div>

    <!-- ── Tabel per Program Studi ── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Serapan per Program Studi</h3>
      </div>

      <div v-if="store.loading.employmentStats" class="p-8 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-primary-600"></div>
      </div>

      <div v-else-if="!stats.by_study_program?.length" class="p-8 flex flex-col items-center justify-center text-gray-400">
        <p class="text-sm">Belum ada data program studi</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left">
              <th
                v-for="col in programColumns" :key="col.key"
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
              v-for="row in stats.by_study_program"
              :key="row.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3 text-gray-800 font-medium">{{ row.name }}</td>
              <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ row.total.toLocaleString('id') }}</td>
              <td class="px-4 py-3 text-right text-gray-600 tabular-nums">{{ row.employed.toLocaleString('id') }}</td>
              <td class="px-4 py-3 text-right">
                <span
                  class="inline-flex items-center gap-1 font-semibold"
                  :class="row.rate >= 70 ? 'text-emerald-600' : row.rate >= 50 ? 'text-yellow-600' : 'text-red-500'"
                >
                  {{ row.rate.toFixed(1) }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Skeleton shimmer helper (lokal, karena common/DataTable mungkin punya miliknya sendiri) */
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
.skeleton-text { height: 1em; }
</style>
