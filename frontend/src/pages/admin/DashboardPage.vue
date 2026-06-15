<script setup>
import { computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BarChart from '@/components/charts/BarChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import AlumniMap from '@/components/charts/AlumniMap.vue'

const store = useDashboardStore()
onMounted(() => store.fetchAll())

const summary           = computed(() => store.summary ?? {})
const totalAlumni       = computed(() => Number(summary.value.total_alumni     ?? 0))
const totalEmployers    = computed(() => Number(summary.value.total_employers   ?? 0))
const activePeriod      = computed(() => summary.value.active_survey_period ?? null)
const surveyResponses   = computed(() => {
  if (!activePeriod.value) return 0
  return Number(activePeriod.value.responses_completed ?? 0)
})
const responseRate      = computed(() => {
  if (!activePeriod.value) return 0
  return Number(activePeriod.value.response_rate ?? 0)
})

const trendSeries = computed(() => {
  const data = Array.isArray(store.trendData) ? store.trendData.map((t) => Number(t.count ?? 0)) : []
  return data.length ? [{ name: 'Respons Survei', data }] : []
})
const trendCategories = computed(() =>
  Array.isArray(store.trendData) ? store.trendData.map((t) => t.month ?? '-') : []
)
const donutSeriesSafe = computed(() =>
  Array.isArray(store.donutSeries) ? store.donutSeries.map((v) => Number(v ?? 0)) : []
)
const barSeries = computed(() => {
  const data = Array.isArray(store.topIndustries) ? store.topIndustries.map((i) => Number(i.count ?? 0)) : []
  return data.length ? [{ name: 'Jumlah Alumni', data }] : []
})
const barCategories = computed(() =>
  Array.isArray(store.topIndustries) ? store.topIndustries.map((i) => i.sector ?? '-') : []
)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-sm text-gray-500 mt-1">Ringkasan data dan aktivitas sistem SITRAS UNISYA</p>
    </div>

    <!-- Stat Cards: 4 cards sesuai blueprint -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Alumni -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-500">Total Alumni</div>
          <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
            </svg>
          </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ totalAlumni.toLocaleString('id-ID') }}</div>
        <div class="text-xs text-gray-400 mt-1">Terdaftar di sistem</div>
      </div>

      <!-- Total Employer -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-500">Total Employer</div>
          <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" />
            </svg>
          </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ totalEmployers.toLocaleString('id-ID') }}</div>
        <div class="text-xs text-gray-400 mt-1">Perusahaan mitra</div>
      </div>

      <!-- Respons Survei Aktif -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-500">Respons Survei</div>
          <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ surveyResponses.toLocaleString('id-ID') }}</div>
        <div class="text-xs text-gray-400 mt-1">{{ activePeriod ? activePeriod.name : 'Tidak ada periode aktif' }}</div>
      </div>

      <!-- Response Rate -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm text-gray-500">Response Rate</div>
          <div class="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">{{ responseRate.toFixed(1) }}%</div>
        <div class="text-xs text-gray-400 mt-1">Periode survei aktif</div>
      </div>
    </div>

    <!-- Charts row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Tren Respons Survei</h2>
        <div v-if="!trendSeries.length" class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data tren.</div>
        <LineChart v-else :series="trendSeries" :categories="trendCategories" />
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Distribusi Status Pekerjaan</h2>
        <div v-if="!donutSeriesSafe.some((v) => v > 0)" class="h-48 flex items-center justify-center text-sm text-gray-400">Belum ada data pekerjaan.</div>
        <DonutChart v-else :series="donutSeriesSafe" :labels="['Bekerja', 'Wirausaha', 'Lanjut Studi', 'Belum Bekerja']" />
      </div>
    </div>

    <!-- Charts row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Top Industri Alumni</h2>
        <div v-if="!barSeries.length" class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data industri.</div>
        <BarChart v-else :series="barSeries" :categories="barCategories" />
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Sebaran Alumni</h2>
        <AlumniMap :points="store.mapData ?? []" />
      </div>
    </div>
  </div>
</template>
