<script setup>
/**
 * SidebarGroup.vue — Collapsible sidebar navigation group
 * Props:
 *   label : String — judul grup
 *   icon  : String — nama ikon Lucide (opsional)
 */
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const props = defineProps({
  label : { type: String, required: true },
  icon  : { type: String, default: null  },
})

const route  = useRoute()
const open   = ref(true)

const iconPaths = {
  'users'          : 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M12 11a4 4 0 100-8 4 4 0 000 8z',
  'briefcase'      : 'M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2',
  'clipboard-list' : 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  'bar-chart-2'    : 'M18 20V10M12 20V4M6 20v-6',
  'bell'           : 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  'database'       : 'M12 2a9 3 0 019 3v2a9 3 0 01-18 0V5a9 3 0 019-3zM3 7v4a9 3 0 0018 0V7M3 13v4a9 3 0 0018 0v-4',
  'settings'       : 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
}
</script>

<template>
  <div>
    <!-- Group toggle button -->
    <button
      type="button"
      class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm
             text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
      @click="open = !open"
    >
      <span class="flex items-center gap-2">
        <svg
          v-if="icon && iconPaths[icon]"
          class="w-4 h-4 flex-shrink-0"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
        >
          <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[icon]" />
        </svg>
        <span class="font-medium">{{ label }}</span>
      </span>
      <svg
        :class="['w-3.5 h-3.5 transition-transform duration-200', open ? 'rotate-180' : '']"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Children -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="open" class="overflow-hidden ml-3 pl-3 border-l border-gray-700 space-y-0.5 mt-0.5">
        <slot />
      </div>
    </Transition>
  </div>
</template>
