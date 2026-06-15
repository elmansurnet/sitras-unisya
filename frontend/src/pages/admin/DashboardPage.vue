<script setup>
import { computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BarChart from '@/components/charts/BarChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import AlumniMap from '@/components/charts/AlumniMap.vue'

const store = useDashboardStore()
onMounted(() => store.fetchAll())

const summary = computed(() => store.summary ?? {})
const totalAlumni = computed(() => Number(summary.value.total_alumni ?? 0))
const totalEmployers = computed(() => Number(summary.value.total_employers ?? 0))
const trendSeries = computed(() => {
  const data = Array.isArray(store.trendData) ? store.trendData.map((t) => Number(t.count ?? 0)) : []
  return data.length ? [{ name: 'Respons Survei', data }] : []
})
const trendCategories = computed(() => Array.isArray(store.trendData) ? store.trendData.map((t) => t.month ?? '-') : [])
const donutSeriesSafe = computed(() => Array.isArray(store.donutSeries) ? store.donutSeries.map((v) => Number(v ?? 0)) : [])
const barSeries = computed(() => {
  const data = Array.isArray(store.topIndustries) ? store.topIndustries.map((i) => Number(i.count ?? 0)) : []
  return data.length ? [{ name: 'Jumlah Alumni', data }] : []
})
const barCategories = computed(() => Array.isArray(store.topIndustries) ? store.topIndustries.map((i) => i.sector ?? '-') : [])
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5"><div class="text-sm text-gray-500">Total Alumni</div><div class="mt-2 text-2xl font-bold text-gray-900">{{ totalAlumni }}</div></div>
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5"><div class="text-sm text-gray-500">Total Employer</div><div class="mt-2 text-2xl font-bold text-gray-900">{{ totalEmployers }}</div></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Tren Respons</h2>
        <div v-if="!trendSeries.length" class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data tren.</div>
        <LineChart v-else :series="trendSeries" :categories="trendCategories" />
      </div>
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Distribusi Status Pekerjaan</h2>
        <div v-if="!donutSeriesSafe.some((v) => v > 0)" class="h-48 flex items-center justify-center text-sm text-gray-400">Belum ada data pekerjaan.</div>
        <DonutChart v-else :series="donutSeriesSafe" :labels="['Bekerja', 'Wirausaha', 'Lanjut Studi', 'Belum Bekerja']" />
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Top Industri</h2>
        <div v-if="!barSeries.length" class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data industri.</div>
        <BarChart v-else :series="barSeries" :categories="barCategories" />
      </div>
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Sebaran Alumni</h2>
        <AlumniMap :points="store.mapData ?? []" />
      </div>
    </div>
  </div>
</template>
