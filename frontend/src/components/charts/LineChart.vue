<script setup>
/**
 * LineChart.vue — Komponen Line / Area Chart reusable (Sesi 5B Task 5B.6)
 *
 * Wrapper ApexCharts untuk line chart / area chart.
 * Dipakai di DashboardPage (Tren Respons Survei) dan StatisticsPage.
 *
 * Props:
 *   series      {Array}   required — [{ name: string, data: number[] }]
 *   categories  {Array}   required — label sumbu X (misal bulan)
 *   height      {Number}  default 300
 *   title       {String}  default ''
 *   colors      {Array}   default palette SITRAS
 *   loading     {Boolean} default false
 *   area        {Boolean} default false — true = area chart (fill gradient)
 *   strokeWidth {Number}  default 2
 *   markers     {Boolean} default true — tampil titik di setiap data point
 */
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  series:      { type: Array,   required: true },
  categories:  { type: Array,   required: true },
  height:      { type: Number,  default: 300 },
  title:       { type: String,  default: '' },
  colors:      { type: Array,   default: () => [] },
  loading:     { type: Boolean, default: false },
  area:        { type: Boolean, default: false },
  strokeWidth: { type: Number,  default: 2 },
  markers:     { type: Boolean, default: true },
})

// ─── Palette SITRAS ─────────────────────────────────────────────────────────
const SITRAS_LINE_COLORS = [
  '#0d9488', // teal-600 (primary)
  '#f59e0b', // amber-500
  '#3b82f6', // blue-500
  '#22c55e', // green-500
  '#ef4444', // red-500
]

const resolvedColors = computed(() =>
  props.colors.length > 0 ? props.colors : SITRAS_LINE_COLORS
)

const chartType = computed(() => props.area ? 'area' : 'line')

const chartOptions = computed(() => ({
  chart: {
    type: chartType.value,
    fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    toolbar: { show: false },
    zoom:    { enabled: false },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 600,
      animateGradually: { enabled: true, delay: 80 },
      dynamicAnimation: { enabled: true, speed: 350 },
    },
  },
  colors: resolvedColors.value,
  stroke: {
    curve: 'smooth',
    width: props.strokeWidth,
  },
  fill: props.area
    ? {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo:   0.02,
          stops: [0, 95, 100],
          colorStops: resolvedColors.value.map(color => [
            { offset: 0,   color, opacity: 0.35 },
            { offset: 100, color, opacity: 0.02 },
          ]),
        },
      }
    : { opacity: 1 },
  markers: {
    size:         props.markers ? 4 : 0,
    colors:       ['#ffffff'],
    strokeColors: resolvedColors.value,
    strokeWidth:  2,
    hover:        { size: 6, sizeOffset: 2 },
  },
  xaxis: {
    categories: props.categories,
    labels: {
      style: {
        fontSize:   '12px',
        colors:     '#64748b',
        fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
      },
      rotate: -30,
      rotateAlways: false,
    },
    axisBorder: { show: false },
    axisTicks:  { show: false },
    crosshairs: {
      stroke: { color: '#e2e8f0', width: 1, dashArray: 4 },
    },
  },
  yaxis: {
    labels: {
      style: {
        fontSize:   '12px',
        colors:     '#64748b',
        fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
      },
      formatter: (val) => Number.isInteger(val)
        ? val.toLocaleString('id-ID')
        : val.toFixed(1),
    },
    min: 0,
  },
  grid: {
    borderColor:     '#e2e8f0',
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true  } },
    padding: { top: 0, right: 8, bottom: 0, left: 0 },
  },
  tooltip: {
    theme: 'light',
    shared: true,
    intersect: false,
    style: {
      fontSize:   '13px',
      fontFamily: '"Plus Jakarta Sans", "Inter", sans-serif',
    },
    y: { formatter: (val) => val.toLocaleString('id-ID') },
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
        chart:  { height: Math.max(props.height - 60, 200) },
        xaxis:  { labels: { style: { fontSize: '10px' }, rotate: -45 } },
        yaxis:  { labels: { style: { fontSize: '10px' } } },
        markers: { size: 3 },
      },
    },
  ],
}))

const isEmpty = computed(() => {
  if (!props.series?.length) return true
  return props.series.every(s => !s.data?.length || s.data.every(v => v === null || v === undefined))
})
</script>

<template>
  <!-- Skeleton -->
  <div v-if="loading" class="line-skeleton" :style="{ height: height + 'px' }">
    <div class="line-path" />
    <div class="line-path" style="opacity:0.5; margin-top: -40px" />
  </div>

  <!-- Empty -->
  <div
    v-else-if="isEmpty"
    class="line-empty"
    :style="{ height: height + 'px' }"
  >
    <slot name="empty">
      <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
      </svg>
      <p class="empty-text">Belum ada data tren untuk ditampilkan.</p>
    </slot>
  </div>

  <!-- Chart -->
  <VueApexCharts
    v-else
    :type="chartType"
    :height="height"
    :options="chartOptions"
    :series="series"
  />
</template>

<style scoped>
/* Skeleton — simulasi garis chart dengan pseudo SVG path */
.line-skeleton {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 0;
  padding: 24px 8px 16px;
  overflow: hidden;
  position: relative;
}
.line-path {
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  clip-path: polygon(
    0% 90%, 5% 80%, 10% 75%, 15% 65%, 20% 55%, 25% 60%, 30% 50%,
    35% 40%, 40% 45%, 45% 35%, 50% 25%, 55% 30%, 60% 20%,
    65% 15%, 70% 25%, 75% 20%, 80% 10%, 85% 18%, 90% 12%,
    95% 8%, 100% 5%, 100% 100%, 0% 100%
  );
}

/* Empty */
.line-empty {
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
