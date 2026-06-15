<script setup>
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'
import BarChart from '@/components/charts/BarChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import AlumniMap from '@/components/charts/AlumniMap.vue'

const router = useRouter()
const store = useDashboardStore()

onMounted(() => store.fetchAll())

const summary = computed(() => store.summary ?? {})
const employmentStats = computed(() => summary.value.employment_stats ?? {})
const totalAlumni = computed(() => Number(summary.value.total_alumni ?? 0))
const totalEmployers = computed(() => Number(summary.value.total_employers ?? 0))
const donutSeriesSafe = computed(() => Array.isArray(store.donutSeries) ? store.donutSeries.map((v) => Number(v ?? 0)) : [])
const trendSeries = computed(() => [{ name: 'Respons Survei', data: (store.trendData ?? []).map((t) => Number(t.count ?? 0)) }])
const trendCategories = computed(() => (store.trendData ?? []).map((t) => t.month ?? '-'))
const donutLabels = ['Bekerja', 'Wirausaha', 'Lanjut Studi', 'Belum Bekerja']
const barSeries = computed(() => [{ name: 'Jumlah Alumni', data: (store.topIndustries ?? []).map((i) => Number(i.count ?? 0)) }])
const barCategories = computed(() => (store.topIndustries ?? []).map((i) => i.sector ?? '-'))

const kpiCards = computed(() => [
  { id: 'alumni', label: 'Total Alumni', value: totalAlumni.value.toLocaleString('id-ID'), color: 'teal', link: '/admin/alumni' },
  { id: 'response', label: 'Tingkat Respons', value: `${store.responseRate ?? 0}%`, color: 'blue', link: null },
  { id: 'employer', label: 'Total Employer', value: totalEmployers.value.toLocaleString('id-ID'), color: 'amber', link: '/admin/employers' },
  { id: 'working', label: 'Alumni Bekerja', value: totalAlumni.value ? `${Math.round(((store.totalWorking ?? 0) / totalAlumni.value) * 100)}%` : '0%', color: 'emerald', link: '/admin/dashboard/stats' },
])
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">Ringkasan data dan aktivitas sistem</p>
      </div>
      <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" @click="store.fetchAll()" :disabled="store.isAnyLoading">Refresh</button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in kpiCards" :key="card.id" class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <div class="text-sm text-gray-500">{{ card.label }}</div>
        <div class="mt-2 text-2xl font-bold text-gray-900">{{ card.value }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Tren Respons</h2>
        <template v-if="store.loading.summary || !trendSeries[0].data.length">
          <div class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data tren.</div>
        </template>
        <LineChart v-else :series="trendSeries" :categories="trendCategories" />
      </div>

      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Distribusi Status Pekerjaan</h2>
        <template v-if="store.loading.summary">
          <div class="h-48 flex items-center justify-center text-sm text-gray-400">Memuat grafik...</div>
        </template>
        <template v-else-if="donutSeriesSafe.some((v) => v > 0)">
          <DonutChart :series="donutSeriesSafe" :labels="donutLabels" />
        </template>
        <div v-else class="flex flex-col items-center justify-center h-48 text-center">
          <p class="text-sm text-gray-400">Belum ada data pekerjaan.</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Top 10 Industri Penyerap Alumni</h2>
        <template v-if="store.loading.employmentStats || !barSeries[0].data.length">
          <div class="h-64 flex items-center justify-center text-sm text-gray-400">Belum ada data industri.</div>
        </template>
        <BarChart v-else :series="barSeries" :categories="barCategories" />
      </div>

      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Sebaran Alumni</h2>
        <template v-if="store.loading.mapData">
          <div class="h-64 flex items-center justify-center text-sm text-gray-400">Memuat peta...</div>
        </template>
        <AlumniMap v-else :points="store.mapData ?? []" />
      </div>
    </div>
  </div>
</template>
