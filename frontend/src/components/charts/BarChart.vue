<script setup>
/**
 * BarChart.vue — Komponen Bar Chart reusable (Sesi 5B Task 5B.4)
 *
 * Wrapper ApexCharts untuk bar chart vertikal/horizontal.
 * Dipakai di DashboardPage (Top 10 Industri) dan StatisticsPage.
 *
 * Props:
 *   series      {Array}   required — [{ name: string, data: number[] }]
 *   categories  {Array}   required — label sumbu X (vertikal) atau Y (horizontal)
 *   height      {Number}  default 350
 *   horizontal  {Boolean} default false — true = horizontal bar
 *   title       {String}  default '' — judul chart (kosong = tidak tampil)
 *   colors      {Array}   default palette teal SITRAS
 *   loading     {Boolean} default false — tampil skeleton saat true
 *   distributed {Boolean} default false — tiap bar warna berbeda
 *   showDataLabels {Boolean} default false
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  series:         { type: Array,   required: true },
  categories:     { type: Array,   required: true },
  height:         { type: Number,  default: 350 },
  horizontal:     { type: Boolean, default: false },
  title:          { type: String,  default: '' },
  colors:         { type: Array,   default: () => [] },
  loading:        { type: Boolean, default: false },
  distributed:    { type: Boolean, default: false },
  showDataLabels: { type: Boolean, default: false },
})

// ─── Palette SITRAS (teal primary + fallback) ───────────────────────────────
const SITRAS_COLORS = [
  '#0d9488', // primary-600
  '#14b8a6', // primary-500
  '#f59e0b', // secondary-500 (emas)
  '#3b82f6', // info
  '#22c55e', // success
  '#ef4444', // danger
  '#8b5cf6', // violet
  '#ec4899', // pink
  '#f97316', // orange
  '#06b6d4', // cyan
]

const resolvedColors = computed(() =>
  props.colors.length > 0 ? props.colors : SITRAS_COLORS
)

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 500,
    },
  },
  colors: resolvedColors.value,
  plotOptions: {
    bar: {
      horizontal:   props.horizontal,
      distributed:  props.distributed,
      borderRadius: 4,
      columnWidth:  '60%',
      barHeight:    '70%',
      dataLabels: {
        position: props.horizontal ? 'bottom' : 'top',
      },
    },
  },
  dataLabels: {
    enabled: props.showDataLabels,
    style: {
      fontSize:  '11px',
      fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
      colors: ['#334155'],
    },
    formatter: (val) => val.toLocaleString('id-ID'),
  },
  xaxis: {
    categories: props.categories,
    labels: {
      style: {
        fontSize:   '12px',
        colors:     '#64748b',
        fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
      },
      trim:        true,
      maxHeight:   80,
    },
    axisBorder: { show: false },
    axisTicks:  { show: false },
  },
  yaxis: {
    labels: {
      style: {
        fontSize:   '12px',
        colors:     '#64748b',
        fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
      },
      formatter: (val) => val.toLocaleString('id-ID'),
    },
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4,
    xaxis: { lines: { show: props.horizontal } },
    yaxis: { lines: { show: !props.horizontal } },
    padding: { top: 0, right: 0, bottom: 0, left: 8 },
  },
  tooltip: {
    theme: 'light',
    style: {
      fontSize:   '13px',
      fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    },
    y: { formatter: (val) => val.toLocaleString('id-ID') + ' alumni' },
  },
  legend: {
    show: props.series.length > 1,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '13px',
    fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    labels: { colors: '#475569' },
    markers: { width: 10, height: 10, radius: 2 },
  },
  title: props.title
    ? {
        text:  props.title,
        align: 'left',
        style: {
          fontSize:   '14px',
          fontWeight: '600',
          fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
          color:      '#1e293b',
        },
      }
    : undefined,
  responsive: [
    {
      breakpoint: 640,
      options: {
        chart: { height: Math.max(props.height - 80, 220) },
        xaxis: { labels: { style: { fontSize: '10px' } } },
        yaxis: { labels: { style: { fontSize: '10px' } } },
      },
    },
  ],
}))

// isEmpty: series ada tapi semua data kosong
const isEmpty = computed(() => {
  if (!props.series?.length) return true
  return props.series.every(s => !s.data?.length || s.data.every(v => !v))
})
</script>

<template>
  <!-- Skeleton saat loading -->
  <div v-if="loading" class="bar-chart-skeleton" :style="{ height: height + 'px' }">
    <div class="skeleton-bar" v-for="n in 6" :key="n" :style="{ height: (50 + n * 10) + '%' }" />
  </div>

  <!-- Empty state -->
  <div
    v-else-if="isEmpty"
    class="bar-chart-empty"
    :style="{ height: height + 'px' }"
  >
    <slot name="empty">
      <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <p class="empty-text">Belum ada data untuk ditampilkan.</p>
    </slot>
  </div>

  <!-- Chart -->
  <VueApexCharts
    v-else
    type="bar"
    :height="height"
    :options="chartOptions"
    :series="series"
  />
</template>

<style scoped>
/* Skeleton loader */
.bar-chart-skeleton {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  padding: 16px 8px 8px;
  overflow: hidden;
}
.skeleton-bar {
  flex: 1;
  border-radius: 4px 4px 0 0;
  background: linear-gradient(
    90deg,
    #e2e8f0 25%,
    #f1f5f9 50%,
    #e2e8f0 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

/* Empty state */
.bar-chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
}
.empty-icon {
  width: 40px;
  height: 40px;
  color: #cbd5e1;
}
.empty-text {
  font-size: 0.875rem;
  color: #94a3b8;
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
}

@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}
</style>
