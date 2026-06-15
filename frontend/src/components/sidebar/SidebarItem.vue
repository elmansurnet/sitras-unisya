<template>
  <router-link
    :to="to"
    class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors
           text-gray-400 hover:text-white hover:bg-gray-800
           [&.router-link-active]:text-white [&.router-link-active]:bg-gray-800
           [&.router-link-active]:font-medium"
    :aria-label="label"
  >
    <span v-if="icon" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
      <component :is="getIconComponent(icon)" class="w-4 h-4" />
    </span>
    <span v-else class="w-1.5 h-1.5 rounded-full bg-gray-600 flex-shrink-0
                        [.router-link-active_&]:bg-emerald-400" aria-hidden="true" />
    <span class="truncate">{{ label }}</span>
  </router-link>
</template>

<script setup>
import { defineProps, h } from 'vue'

defineProps({
  to:    { type: [String, Object], required: true },
  label: { type: String, required: true },
  icon:  { type: String, default: null },
})

// Semua icon menggunakan h() render function — tidak butuh Vue runtime compiler.
const icons = {
  'grid': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('rect', { x: '3', y: '3', width: '7', height: '7' }),
    h('rect', { x: '14', y: '3', width: '7', height: '7' }),
    h('rect', { x: '14', y: '14', width: '7', height: '7' }),
    h('rect', { x: '3', y: '14', width: '7', height: '7' }),
  ]),
  'users': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75' }),
  ]),
  'briefcase': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('rect', { x: '2', y: '7', width: '20', height: '14', rx: '2' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2M12 12v4M10 14h4' }),
  ]),
  'clipboard-list': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' }),
  ]),
  'bar-chart-2': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M18 20V10M12 20V4M6 20v-6' }),
  ]),
  'trending-up': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polyline', { points: '23 6 13.5 15.5 8.5 10.5 1 18' }),
    h('polyline', { points: '17 6 23 6 23 12' }),
  ]),
  'bell': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' }),
  ]),
  'database': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('ellipse', { cx: '12', cy: '5', rx: '9', ry: '3' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M21 12c0 1.66-4 3-9 3s-9-1.34-9-3' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5' }),
  ]),
  'user-cog': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('circle', { cx: '9', cy: '7', r: '4' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 21v-2a4 4 0 014-4h4' }),
    h('circle', { cx: '19', cy: '19', r: '2' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19 15v2M19 21v2M15.34 16.34l1.41 1.41M21.25 21.25l1.41 1.41M13.75 21.25l-1.41 1.41M19.25 16.34l-1.41 1.41' }),
  ]),
  'settings': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('circle', { cx: '12', cy: '12', r: '3' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z' }),
  ]),
  'shield': () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' }),
  ]),
}

function getIconComponent(name) {
  return icons[name] ?? (() => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('circle', { cx: '12', cy: '12', r: '2' }),
  ]))
}
</script>
