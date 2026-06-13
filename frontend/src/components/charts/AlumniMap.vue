<script setup>
/**
 * AlumniMap.vue — Peta Sebaran Alumni (Sesi 5B Task 5B.7)
 *
 * Props:
 *   markers  : Array<{ province, city, count, coordinates: { lat, lng } }>
 *   center   : [lat, lng] default [-2.5, 118]
 *   zoom     : Number   default 5
 *   height   : String   default '420px'
 *   title    : String   optional
 *
 * Usage:
 *   <AlumniMap :markers="mapData" />
 *   <AlumniMap :markers="mapData" :center="[-2.5, 118]" :zoom="5" height="500px" />
 */
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'

const props = defineProps({
  markers: {
    type: Array,
    default: () => [],
  },
  center: {
    type: Array,
    default: () => [-2.5, 118],
  },
  zoom: {
    type: Number,
    default: 5,
  },
  height: {
    type: String,
    default: '420px',
  },
  title: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------
const mapContainer = ref(null)
const mapInstance  = ref(null)
const leafletRef   = ref(null)
const mapReady     = ref(false)
const initError    = ref(null)

// Hitung max count untuk skala intensitas warna
const maxCount = computed(() => {
  if (!props.markers.length) return 1
  return Math.max(...props.markers.map(m => m.count), 1)
})

// ---------------------------------------------------------------------------
// Helpers: warna marker berdasarkan intensitas
// ---------------------------------------------------------------------------
/**
 * Kembalikan class CSS warna berdasarkan rasio count/maxCount
 * low  (<33%)  : teal-400
 * mid  (<66%)  : teal-600
 * high (>=66%) : teal-800
 */
function getIntensity(count) {
  const ratio = count / maxCount.value
  if (ratio < 0.33) return 'low'
  if (ratio < 0.66) return 'mid'
  return 'high'
}

const intensityColors = {
  low:  { bg: '#2dd4bf', border: '#0d9488', text: '#0f172a' },
  mid:  { bg: '#0d9488', border: '#0f766e', text: '#ffffff' },
  high: { bg: '#0f766e', border: '#134e4a', text: '#ffffff' },
}

/**
 * Buat Leaflet DivIcon kustom dengan badge count
 */
function createMarkerIcon(L, count, intensity) {
  const { bg, border, text } = intensityColors[intensity]
  const size = intensity === 'high' ? 44 : intensity === 'mid' ? 38 : 32
  const html = `
    <div style="
      width: ${size}px;
      height: ${size}px;
      background: ${bg};
      border: 2.5px solid ${border};
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: ${text};
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: ${count >= 100 ? '10px' : '12px'};
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(0,0,0,0.18);
      cursor: pointer;
      transition: transform 0.15s ease;
    ">${count >= 1000 ? Math.round(count / 100) / 10 + 'k' : count}</div>
  `
  return L.divIcon({
    html,
    className: '',
    iconSize:  [size, size],
    iconAnchor: [size / 2, size / 2],
  })
}

// ---------------------------------------------------------------------------
// Inisialisasi Leaflet via dynamic import (esm.sh)
// ---------------------------------------------------------------------------
async function initMap() {
  if (!mapContainer.value) return
  try {
    // Inject Leaflet CSS jika belum ada
    if (!document.getElementById('leaflet-css')) {
      const link      = document.createElement('link')
      link.id         = 'leaflet-css'
      link.rel        = 'stylesheet'
      link.href       = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
      link.integrity  = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY='
      link.crossOrigin = ''
      document.head.appendChild(link)
    }

    // Dynamic import Leaflet
    const L = await import('https://esm.sh/leaflet@1.9.4')
    leafletRef.value = L.default ?? L
    const Lx = leafletRef.value

    // Fix default icon path di bundler
    delete Lx.Icon.Default.prototype._getIconUrl
    Lx.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
      iconUrl:       'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      shadowUrl:     'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    })

    // Buat map instance
    mapInstance.value = Lx.map(mapContainer.value, {
      center:          props.center,
      zoom:            props.zoom,
      zoomControl:     true,
      scrollWheelZoom: false,
      attributionControl: true,
    })

    // Tile layer OpenStreetMap
    Lx.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
      maxZoom: 18,
    }).addTo(mapInstance.value)

    mapReady.value = true
    renderMarkers()
  } catch (err) {
    initError.value = 'Gagal memuat peta. Periksa koneksi internet Anda.'
    console.error('[AlumniMap] Leaflet init error:', err)
  }
}

// ---------------------------------------------------------------------------
// Render / update markers
// ---------------------------------------------------------------------------
let markerLayer = null

function renderMarkers() {
  const L = leafletRef.value
  const map = mapInstance.value
  if (!L || !map) return

  // Hapus layer lama
  if (markerLayer) {
    map.removeLayer(markerLayer)
    markerLayer = null
  }

  if (!props.markers.length) return

  const layerGroup = L.layerGroup()

  props.markers.forEach(marker => {
    const { province, city, count, coordinates } = marker
    if (!coordinates?.lat || !coordinates?.lng) return

    const intensity = getIntensity(count)
    const icon = createMarkerIcon(L, count, intensity)

    const m = L.marker([coordinates.lat, coordinates.lng], { icon })

    // Popup
    const popupContent = `
      <div style="font-family: 'Plus Jakarta Sans', sans-serif; min-width: 140px; padding: 4px 0;">
        <div style="font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 4px;">${province}</div>
        ${city ? `<div style="font-size: 12px; color: #475569; margin-bottom: 6px;">${city}</div>` : ''}
        <div style="display: flex; align-items: center; gap: 6px;">
          <span style="background: #ccfbef; color: #0f766e; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 9999px;">
            ${count.toLocaleString('id-ID')} alumni
          </span>
        </div>
      </div>
    `
    m.bindPopup(popupContent, {
      closeButton: true,
      minWidth: 160,
    })

    // Hover tooltip singkat
    m.bindTooltip(`${province}: ${count.toLocaleString('id-ID')} alumni`, {
      direction: 'top',
      offset: [0, -10],
    })

    layerGroup.addLayer(m)
  })

  layerGroup.addTo(map)
  markerLayer = layerGroup

  // Auto-fit bounds jika ada marker
  if (props.markers.length > 0) {
    const validMarkers = props.markers.filter(m => m.coordinates?.lat && m.coordinates?.lng)
    if (validMarkers.length > 0) {
      const bounds = L.latLngBounds(
        validMarkers.map(m => [m.coordinates.lat, m.coordinates.lng])
      )
      map.fitBounds(bounds, { padding: [30, 30], maxZoom: 8 })
    }
  }
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  await nextTick()
  await initMap()
})

onUnmounted(() => {
  if (mapInstance.value) {
    mapInstance.value.remove()
    mapInstance.value = null
    markerLayer       = null
    leafletRef.value  = null
    mapReady.value    = false
  }
})

// Re-render saat markers berubah
watch(
  () => props.markers,
  async () => {
    if (!mapReady.value) return
    await nextTick()
    renderMarkers()
  },
  { deep: true }
)

// Update center & zoom dari luar
watch(
  () => [props.center, props.zoom],
  ([newCenter, newZoom]) => {
    if (!mapInstance.value) return
    mapInstance.value.setView(newCenter, newZoom)
  }
)

// Invalidate map size saat loading selesai (cegah tile blank)
watch(
  () => props.loading,
  (val) => {
    if (!val && mapInstance.value) {
      setTimeout(() => mapInstance.value?.invalidateSize(), 100)
    }
  }
)
</script>

<template>
  <div class="alumni-map-wrapper" :style="{ height }">
    <!-- Loading Skeleton -->
    <div
      v-if="props.loading"
      class="alumni-map-skeleton"
      :style="{ height }"
      aria-busy="true"
      aria-label="Memuat peta sebaran alumni"
    >
      <div class="skeleton-shimmer" />
      <div class="skeleton-center">
        <svg
          width="36" height="36" viewBox="0 0 24 24"
          fill="none" stroke="#0d9488" stroke-width="1.5"
          class="skeleton-icon" aria-hidden="true"
        >
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>
        <p class="skeleton-label">Memuat peta…</p>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="initError"
      class="alumni-map-error"
      :style="{ height }"
      role="alert"
    >
      <svg
        width="32" height="32" viewBox="0 0 24 24"
        fill="none" stroke="#ef4444" stroke-width="1.5"
        aria-hidden="true"
      >
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <p class="error-message">{{ initError }}</p>
    </div>

    <!-- Map Container -->
    <div
      v-show="!props.loading && !initError"
      class="alumni-map-inner"
    >
      <!-- Map element -->
      <div ref="mapContainer" class="leaflet-map" :style="{ height }" />

      <!-- Empty state overlay -->
      <transition name="fade">
        <div
          v-if="mapReady && !props.loading && !props.markers.length"
          class="alumni-map-empty"
          aria-live="polite"
        >
          <svg
            width="36" height="36" viewBox="0 0 24 24"
            fill="none" stroke="#94a3b8" stroke-width="1.5"
            aria-hidden="true"
          >
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            <circle cx="12" cy="9" r="2.5"/>
          </svg>
          <p class="empty-label">Belum ada data sebaran alumni.</p>
        </div>
      </transition>

      <!-- Legend -->
      <div
        v-if="mapReady && props.markers.length > 0"
        class="alumni-map-legend"
        role="complementary"
        aria-label="Legenda peta"
      >
        <p class="legend-title">Jumlah Alumni</p>
        <div class="legend-items">
          <div class="legend-item">
            <span class="legend-dot" style="background:#2dd4bf; border-color:#0d9488;"></span>
            <span class="legend-text">Sedikit</span>
          </div>
          <div class="legend-item">
            <span class="legend-dot" style="background:#0d9488; border-color:#0f766e;"></span>
            <span class="legend-text">Sedang</span>
          </div>
          <div class="legend-item">
            <span class="legend-dot" style="background:#0f766e; border-color:#134e4a;"></span>
            <span class="legend-text">Banyak</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.alumni-map-wrapper {
  position: relative;
  width: 100%;
  border-radius: 0.75rem;
  overflow: hidden;
  background: #f8fafc;
}

/* ── Leaflet map ─────────────────────────────────── */
.alumni-map-inner {
  position: relative;
  width: 100%;
  height: 100%;
}

.leaflet-map {
  width: 100%;
  border-radius: 0.75rem;
  z-index: 0;
}

/* ── Skeleton ────────────────────────────────────── */
.alumni-map-skeleton {
  width: 100%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 0.75rem;
  overflow: hidden;
}

.skeleton-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255,255,255,0.6) 50%,
    transparent 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}

.skeleton-center {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.skeleton-icon {
  opacity: 0.45;
}

.skeleton-label {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  font-size: 0.8125rem;
  color: #94a3b8;
  margin: 0;
}

/* ── Error ───────────────────────────────────────── */
.alumni-map-error {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #fff1f2;
  border-radius: 0.75rem;
  border: 1px solid #fecdd3;
}

.error-message {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  font-size: 0.875rem;
  color: #dc2626;
  margin: 0;
  text-align: center;
  padding: 0 1rem;
}

/* ── Empty overlay ───────────────────────────────── */
.alumni-map-empty {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: rgba(248, 250, 252, 0.75);
  backdrop-filter: blur(2px);
  border-radius: 0.75rem;
  pointer-events: none;
}

.empty-label {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0;
}

/* ── Legend ──────────────────────────────────────── */
.alumni-map-legend {
  position: absolute;
  bottom: 24px;
  right: 12px;
  z-index: 10;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 8px 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  min-width: 110px;
  pointer-events: none;
}

.legend-title {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  font-size: 0.6875rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 6px 0;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-dot {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border-width: 2px;
  border-style: solid;
  flex-shrink: 0;
}

.legend-text {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  font-size: 0.75rem;
  color: #334155;
}

/* ── Fade transition ─────────────────────────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Leaflet override: popup ─────────────────────── */
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 0.625rem !important;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
  border: 1px solid #e2e8f0 !important;
  padding: 0 !important;
}

:deep(.leaflet-popup-content) {
  margin: 10px 14px !important;
}

:deep(.leaflet-popup-tip-container) {
  margin-top: -1px;
}
</style>
