<script setup>
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'
import BarChart from '@/components/charts/BarChart.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import LineChart from '@/components/charts/LineChart.vue'
import AlumniMap from '@/components/charts/AlumniMap.vue'

const router = useRouter()
const store  = useDashboardStore()

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => store.fetchAll())

// ─── KPI Cards ───────────────────────────────────────────────────────────────
const kpiCards = computed(() => [
  {
    id: 'alumni',
    label: 'Total Alumni',
    value: store.summary.total_alumni.toLocaleString('id-ID'),
    icon: 'users',
    color: 'teal',
    link: '/admin/alumni',
  },
  {
    id: 'response',
    label: 'Tingkat Respons',
    value: `${store.responseRate}%`,
    icon: 'chart-bar',
    color: 'blue',
    link: null,
    isRate: true,
  },
  {
    id: 'employer',
    label: 'Total Employer',
    value: store.summary.total_employers.toLocaleString('id-ID'),
    icon: 'building',
    color: 'amber',
    link: '/admin/employers',
  },
  {
    id: 'working',
    label: 'Alumni Bekerja',
    value: store.summary.total_alumni
      ? `${Math.round((store.totalWorking / store.summary.total_alumni) * 100)}%`
      : '0%',
    icon: 'briefcase',
    color: 'emerald',
    link: '/admin/dashboard/stats',
  },
])

// ─── Active Period ────────────────────────────────────────────────────────────
const activePeriod = computed(() => store.summary.active_survey_period)

// ─── Line Chart ───────────────────────────────────────────────────────────────
const trendSeries = computed(() => [{
  name: 'Respons Survei',
  data: store.trendData.map(t => t.count),
}])
const trendCategories = computed(() => store.trendData.map(t => t.month))

// ─── Donut Chart ──────────────────────────────────────────────────────────────
const donutLabels = ['Bekerja', 'Wirausaha', 'Lanjut Studi', 'Belum Bekerja']

// ─── Bar Chart ────────────────────────────────────────────────────────────────
const barSeries = computed(() => [{
  name: 'Jumlah Alumni',
  data: store.topIndustries.map(i => i.count),
}])
const barCategories = computed(() => store.topIndustries.map(i => i.sector))

// ─── Recent Activities ────────────────────────────────────────────────────────
const recentActivities = computed(() =>
  (store.summary.recent_activities ?? []).slice(0, 5)
)

// ─── Helpers ─────────────────────────────────────────────────────────────────
function formatDateTime(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function formatDate(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
  })
}

const colorMap = {
  teal:    { bg: 'bg-teal-50',    icon: 'text-teal-600',    border: 'border-teal-100' },
  blue:    { bg: 'bg-blue-50',    icon: 'text-blue-600',    border: 'border-blue-100' },
  amber:   { bg: 'bg-amber-50',   icon: 'text-amber-600',   border: 'border-amber-100' },
  emerald: { bg: 'bg-emerald-50', icon: 'text-emerald-600', border: 'border-emerald-100' },
}
</script>

<template>
  <div class="space-y-6">
    <!-- ─── Page Header ─────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">Ringkasan data dan aktivitas sistem</p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium
               bg-white border border-gray-300 text-gray-700 rounded-lg
               hover:bg-gray-50 transition-colors"
        @click="store.fetchAll()"
        :disabled="store.isAnyLoading"
        aria-label="Refresh data dashboard"
      >
        <!-- heroicon: arrow-path -->
        <svg class="w-4 h-4" :class="{ 'animate-spin': store.isAnyLoading }" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0
                   004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003
                   8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- ─── KPI Cards ────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <template v-if="store.loading.summary">
        <div v-for="n in 4" :key="n"
             class="bg-white rounded-xl shadow-card border border-gray-100 p-5 animate-pulse">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-gray-200" />
            <div class="flex-1 space-y-2">
              <div class="h-3 bg-gray-200 rounded w-2/3" />
              <div class="h-6 bg-gray-200 rounded w-1/2" />
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <component
          v-for="card in kpiCards"
          :key="card.id"
          :is="card.link ? 'a' : 'div'"
          :href="card.link ?? undefined"
          @click.prevent="card.link && router.push(card.link)"
          class="bg-white rounded-xl shadow-card border p-5 transition-shadow"
          :class="[
            colorMap[card.color].border,
            card.link ? 'hover:shadow-md cursor-pointer' : '',
          ]"
          :aria-label="card.link ? `Lihat ${card.label}` : undefined"
        >
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 flex-shrink-0 rounded-lg flex items-center justify-center"
                 :class="colorMap[card.color].bg">
              <!-- users -->
              <svg v-if="card.icon === 'users'" class="w-5 h-5" :class="colorMap[card.color].icon"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17
                         20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7
                         20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                         m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0
                         11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2
                         0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <!-- chart-bar -->
              <svg v-else-if="card.icon === 'chart-bar'" class="w-5 h-5" :class="colorMap[card.color].icon"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0
                         002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2
                         2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0
                         0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0
                         01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <!-- building -->
              <svg v-else-if="card.icon === 'building'" class="w-5 h-5" :class="colorMap[card.color].icon"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14
                         0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1
                         4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <!-- briefcase -->
              <svg v-else-if="card.icon === 'briefcase'" class="w-5 h-5" :class="colorMap[card.color].icon"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 13.255A23.931 23.931 0 0112 15c-3.183
                         0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2
                         2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2
                         2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs text-gray-500 truncate">{{ card.label }}</p>
              <p class="text-2xl font-bold text-gray-900 tabular-nums mt-0.5 leading-tight">{{ card.value }}</p>
            </div>
          </div>
          <!-- response rate progress ring -->
          <div v-if="card.isRate" class="mt-3">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
              <span>Periode aktif</span>
              <span>{{ store.responseRate }}%</span>
            </div>
            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full bg-blue-500 rounded-full transition-all duration-500"
                :style="{ width: `${store.responseRate}%` }"
                :aria-valuenow="store.responseRate"
                aria-valuemin="0"
                aria-valuemax="100"
                role="progressbar"
                :aria-label="`Tingkat respons ${store.responseRate}%`"
              />
            </div>
          </div>
        </component>
      </template>
    </div>

    <!-- ─── Active Survey Period ─────────────────────────────────────────── -->
    <div v-if="activePeriod || store.loading.summary"
         class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
      <template v-if="store.loading.summary">
        <div class="animate-pulse space-y-3">
          <div class="h-4 bg-gray-200 rounded w-1/3" />
          <div class="h-3 bg-gray-200 rounded w-1/2" />
          <div class="h-2 bg-gray-200 rounded-full" />
        </div>
      </template>
      <template v-else-if="activePeriod">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium
                           bg-emerald-100 text-emerald-700 rounded-full">
                Aktif
              </span>
              <h2 class="text-sm font-semibold text-gray-900 truncate">
                {{ activePeriod.name }}
              </h2>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Berakhir {{ formatDate(activePeriod.end_date) }}
              &bull;
              {{ activePeriod.responses_completed }}
              dari
              {{ (activePeriod.responses_completed ?? 0) + (activePeriod.responses_pending ?? 0) }}
              respons
            </p>
            <!-- response rate bar -->
            <div class="mt-3">
              <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Progress respons</span>
                <span class="font-semibold text-gray-700">{{ activePeriod.response_rate }}%</span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700"
                  :class="activePeriod.response_rate >= 80 ? 'bg-emerald-500' :
                           activePeriod.response_rate >= 50 ? 'bg-teal-500' : 'bg-amber-400'"
                  :style="{ width: `${activePeriod.response_rate}%` }"
                  role="progressbar"
                  :aria-valuenow="activePeriod.response_rate"
                  aria-valuemin="0" aria-valuemax="100"
                  :aria-label="`Progress respons ${activePeriod.response_rate}%`"
                />
              </div>
            </div>
          </div>
          <div class="flex flex-row sm:flex-col gap-2">
            <button
              class="px-3 py-1.5 text-xs font-medium bg-teal-600 text-white
                     rounded-lg hover:bg-teal-700 transition-colors whitespace-nowrap"
              @click="router.push(`/admin/survey-periods/${activePeriod.id}`)"
            >
              Lihat Progress
            </button>
            <button
              class="px-3 py-1.5 text-xs font-medium bg-white border border-gray-300
                     text-gray-700 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap"
              @click="router.push(`/admin/survey-periods/${activePeriod.id}`)"
            >
              Kirim Undangan
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- ─── Charts Row 1: Line + Donut ──────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Line Chart -->
      <div class="lg:col-span-2 bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Tren Respons Survei</h2>
        <template v-if="store.loading.summary">
          <div class="animate-pulse h-48 bg-gray-100 rounded-lg" />
        </template>
        <template v-else-if="trendSeries[0]?.data.length">
          <LineChart :series="trendSeries" :categories="trendCategories" :height="220" />
        </template>
        <div v-else class="flex flex-col items-center justify-center h-48 text-center">
          <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2
                     2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2
                     2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0
                     0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <p class="text-sm text-gray-400">Belum ada data tren.</p>
        </div>
      </div>

      <!-- Donut Chart -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Status Pekerjaan Alumni</h2>
        <template v-if="store.loading.summary">
          <div class="animate-pulse flex flex-col items-center gap-3">
            <div class="w-32 h-32 rounded-full bg-gray-200" />
            <div class="space-y-2 w-full">
              <div v-for="n in 4" :key="n" class="h-3 bg-gray-200 rounded" />
            </div>
          </div>
        </template>
        <template v-else-if="store.donutSeries.some(v => v > 0)">
          <DonutChart :series="store.donutSeries" :labels="donutLabels" />
        </template>
        <div v-else class="flex flex-col items-center justify-center h-48 text-center">
          <p class="text-sm text-gray-400">Belum ada data pekerjaan.</p>
        </div>
      </div>
    </div>

    <!-- ─── Charts Row 2: Bar + Map ─────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Bar Chart: Top Industri -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-gray-900">Top 10 Industri Penyerap Alumni</h2>
          <a
            href="/admin/dashboard/stats"
            @click.prevent="router.push('/admin/dashboard/stats')"
            class="text-xs text-teal-600 hover:text-teal-700 font-medium"
          >
            Lihat Semua
          </a>
        </div>
        <template v-if="store.loading.employmentStats">
          <div class="animate-pulse h-64 bg-gray-100 rounded-lg" />
        </template>
        <template v-else-if="barSeries[0]?.data.length">
          <BarChart :series="barSeries" :categories="barCategories" :height="300" :horizontal="true" />
        </template>
        <div v-else class="flex flex-col items-center justify-center h-48 text-center">
          <p class="text-sm text-gray-400">Belum ada data industri.</p>
        </div>
      </div>

      <!-- Alumni Map -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-gray-900">Sebaran Alumni</h2>
          <span class="text-xs text-gray-400">{{ store.mapData.length }} lokasi</span>
        </div>
        <template v-if="store.loading.mapData">
          <div class="animate-pulse h-64 bg-gray-100 rounded-lg" />
        </template>
        <template v-else-if="store.mapData.length">
          <AlumniMap
            :markers="store.mapData"
            :center="[-2.5, 118]"
            :zoom="4"
            style="height: 300px; border-radius: 0.5rem; overflow: hidden;"
          />
        </template>
        <div v-else class="flex flex-col items-center justify-center h-64 text-center">
          <p class="text-sm text-gray-400">Belum ada data lokasi alumni.</p>
        </div>
      </div>
    </div>

    <!-- ─── Recent Activities ─────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl shadow-card border border-gray-100 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-900">Aktivitas Sistem Terbaru</h2>
        <a
          href="/admin/audit-logs"
          @click.prevent="router.push('/admin/audit-logs')"
          class="text-xs text-teal-600 hover:text-teal-700 font-medium"
        >
          Lihat Semua
        </a>
      </div>

      <!-- Skeleton -->
      <template v-if="store.loading.summary">
        <ul class="divide-y divide-gray-50" role="list">
          <li v-for="n in 5" :key="n" class="py-3 flex items-start gap-3 animate-pulse">
            <div class="w-7 h-7 rounded-full bg-gray-200 flex-shrink-0" />
            <div class="flex-1 space-y-1.5">
              <div class="h-3 bg-gray-200 rounded w-2/3" />
              <div class="h-3 bg-gray-200 rounded w-1/3" />
            </div>
          </li>
        </ul>
      </template>

      <!-- Data -->
      <template v-else-if="recentActivities.length">
        <ul class="divide-y divide-gray-50" role="list">
          <li
            v-for="(activity, idx) in recentActivities"
            :key="idx"
            class="py-3 flex items-start gap-3"
          >
            <!-- dot -->
            <span
              class="mt-0.5 w-2 h-2 rounded-full flex-shrink-0 bg-teal-400"
              aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
              <p class="text-sm text-gray-700 leading-snug">
                <span class="font-medium">{{ activity.action }}</span>
                <span v-if="activity.description" class="text-gray-500">
                  &nbsp;— {{ activity.description }}
                </span>
              </p>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ formatDateTime(activity.created_at) }}
              </p>
            </div>
          </li>
        </ul>
      </template>

      <!-- Empty -->
      <div v-else class="flex flex-col items-center justify-center py-10 text-center">
        <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2
                   0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2
                   2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="text-sm text-gray-400">Belum ada aktivitas sistem.</p>
      </div>
    </div>

    <!-- ─── Error Banner ──────────────────────────────────────────────────── -->
    <div
      v-if="store.error.summary || store.error.employmentStats || store.error.mapData"
      class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 flex items-center
             justify-between gap-3"
      role="alert"
    >
      <div class="flex items-center gap-2 text-sm text-red-700">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54
                   0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464
                   0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        {{ store.error.summary || store.error.employmentStats || store.error.mapData }}
      </div>
      <button
        class="text-xs font-medium text-red-700 hover:text-red-800 underline whitespace-nowrap"
        @click="store.fetchAll()"
      >
        Coba Lagi
      </button>
    </div>
  </div>
</template>
