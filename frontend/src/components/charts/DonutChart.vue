<script setup>
/**
 * DonutChart.vue — Komponen Donut Chart reusable (Sesi 5B Task 5B.5)
 *
 * Wrapper ApexCharts untuk donut chart dengan center label total.
 * Dipakai di DashboardPage (Status Pekerjaan Alumni).
 *
 * Props:
 *   series      {Array}   required — [number] nilai tiap segmen
 *   labels      {Array}   required — label tiap segmen
 *   height      {Number}  default 320
 *   title       {String}  default '' — judul chart (kosong = tidak tampil)
 *   colors      {Array}   default palette SITRAS
 *   loading     {Boolean} default false
 *   showTotal   {Boolean} default true — tampil total di tengah donut
 *   showLegend  {Boolean} default true
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  series:     { type: Array,   required: true },
  labels:     { type: Array,   required: true },
  height:     { type: Number,  default: 320 },
  title:      { type: String,  default: '' },
  colors:     { type: Array,   default: () => [] },
  loading:    { type: Boolean, default: false },
  showTotal:  { type: Boolean, default: true },
  showLegend: { type: Boolean, default: true },
})

// ─── Palette SITRAS ─────────────────────────────────────────────────────────
const SITRAS_DONUT_COLORS = [
  '#0d9488', // teal-600  bekerja
  '#14b8a6', // teal-500  wirausaha
  '#f59e0b', // amber-500 lanjut studi
  '#94a3b8', // slate-400 belum bekerja
]

const resolvedColors = computed(() =>
  props.colors.length > 0 ? props.colors : SITRAS_DONUT_COLORS
)

const total = computed(() =>
  props.series.reduce((sum, v) => sum + (v ?? 0), 0)
)

const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 500,
    },
  },
  colors: resolvedColors.value,
  labels: props.labels,
  dataLabels: {
    enabled: false,
  },
  plotOptions: {
    pie: {\n      donut: {
        size: '68%',
        labels: {
          show: props.showTotal,
          total: {
            show:      props.showTotal,
            showAlways: true,
            label:     'Total',
            fontSize:  '13px',
            fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
            fontWeight: 600,
            color:     '#64748b',
            formatter: () => total.value.toLocaleString('id-ID'),
          },
          value: {
            fontSize:  '22px',
            fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
            fontWeight: 700,
            color:     '#1e293b',
            formatter: (val) => Number(val).toLocaleString('id-ID'),
          },
          name: {
            fontSize:  '13px',
            fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
            fontWeight: 500,
            color:     '#64748b',
            offsetY:   4,
          },
        },
      },
      expandOnClick: false,
    },
  },
  stroke: {
    width: 2,
    colors: ['#ffffff'],
  },
  tooltip: {
    theme: 'light',
    style: {
      fontSize:   '13px',
      fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    },
    y: {
      formatter: (val) => {
        const pct = total.value > 0
          ? ((val / total.value) * 100).toFixed(1)
          : '0.0'
        return `${val.toLocaleString('id-ID')} alumni (${pct}%)`
      },
    },
  },
  legend: {
    show: props.showLegend,
    position: 'bottom',
    horizontalAlign: 'center',
    fontSize: '13px',
    fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    labels: { colors: '#475569' },
    markers: {
      width: 10,
      height: 10,
      radius: 2,
    },
    itemMargin: { horizontal: 12, vertical: 4 },
    formatter: (seriesName, opts) => {
      const val = opts.w.globals.series[opts.seriesIndex]
      return `${seriesName}: ${val.toLocaleString('id-ID')}`
    },
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
        chart: { height: Math.max(props.height - 60, 220) },
        legend: { position: 'bottom', fontSize: '11px' },
      },
    },
  ],
}))

const isEmpty = computed(() =>
  !props.series?.length || props.series.every(v => !v)
)
</script>

<template>
  <!-- Skeleton -->
  <div v-if="loading" class="donut-skeleton" :style="{ height: height + 'px' }">
    <div class="donut-circle" />
    <div class="donut-legend">
      <div class="legend-item" v-for="n in 4" :key="n">
        <div class="legend-dot" />
        <div class="legend-line" :style="{ width: (40 + n * 8) + 'px' }" />
      </div>
    </div>
  </div>

  <!-- Empty -->
  <div
    v-else-if="isEmpty"
    class="donut-empty"
    :style="{ height: height + 'px' }"
  >
    <slot name="empty">
      <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
      </svg>
      <p class="empty-text">Belum ada data untuk ditampilkan.</p>
    </slot>
  </div>

  <!-- Chart -->
  <VueApexCharts
    v-else
    type="donut"
    :height="height"
    :options="chartOptions"
    :series="series"
  />
</template>

<style scoped>
/* Skeleton */
.donut-skeleton {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}
.donut-circle {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
.donut-legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 2px;
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
.legend-line {
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

/* Empty */
.donut-empty {
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
