<script setup>
/**
 * SidebarItem.vue — Single sidebar navigation link
 * Props:
 *   to    : RouteLocationRaw — vue-router :to target
 *   label : String
 *   icon  : String — nama ikon (opsional, untuk item top-level)
 */
import { computed } from 'vue'
import { useLink, useRoute } from 'vue-router'

const props = defineProps({
  to    : { type: [String, Object], required: true },
  label : { type: String,          required: true },
  icon  : { type: String,          default: null  },
})

const route   = useRoute()
const { href, isActive, isExactActive } = useLink({ to: computed(() => props.to) })

const iconPaths = {
  'grid'        : 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
  'users'       : 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M12 11a4 4 0 100-8 4 4 0 000 8z',
  'user-cog'    : 'M10 19.5A7.5 7.5 0 1110 4.5a7.5 7.5 0 010 15zM21.17 8A2 2 0 1019 10M22 12h-2M21.17 16A2 2 0 1019 14M18 12a1 1 0 100-2 1 1 0 000 2z',
  'settings'    : 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
  'shield'      : 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
  'trending-up' : 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
  'bar-chart-2' : 'M18 20V10M12 20V4M6 20v-6',
}
</script>

<template>
  <router-link
    :to="to"
    :class="[
      'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
      isActive
        ? 'bg-emerald-600 text-white font-medium'
        : 'text-gray-400 hover:text-white hover:bg-gray-800',
    ]"
  >
    <svg
      v-if="icon && iconPaths[icon]"
      class="w-4 h-4 flex-shrink-0"
      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
    >
      <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[icon]" />
    </svg>
    <span>{{ label }}</span>
  </router-link>
</template>
