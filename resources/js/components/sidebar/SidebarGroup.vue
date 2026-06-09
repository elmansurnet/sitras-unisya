<template>
  <div>
    <!-- Group header toggle -->
    <button
      class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-sm
             text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
      :aria-expanded="open"
      @click="open = !open"
    >
      <!-- Icon -->
      <span class="w-4 h-4 flex-shrink-0" aria-hidden="true">
        <svg v-if="icon === 'users'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
        <svg v-else-if="icon === 'briefcase'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2M12 12v4M10 14h4"/>
        </svg>
        <svg v-else-if="icon === 'clipboard-list'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="2"/>
        </svg>
      </span>

      <span class="flex-1 truncate text-left">{{ label }}</span>

      <!-- Chevron -->
      <svg
        class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200"
        :class="open ? 'rotate-180' : ''"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Child items -->
    <Transition name="sidebar-group">
      <div v-if="open" class="pl-4 mt-0.5 space-y-0.5">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { watch } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  icon:  { type: String, default: null },
})

const open  = ref(false)
const route = useRoute()

// Auto-expand jika child route aktif
watch(
  () => route.name,
  () => {
    // Buka group jika ada child yang cocok
    // Logic ini akan bekerja setelah anak-anak dirender
  },
  { immediate: true },
)
</script>

<style scoped>
.sidebar-group-enter-active,
.sidebar-group-leave-active {
  transition: max-height 0.2s ease, opacity 0.2s;
  overflow: hidden;
}
.sidebar-group-enter-from,
.sidebar-group-leave-to {
  max-height: 0;
  opacity: 0;
}
.sidebar-group-enter-to,
.sidebar-group-leave-from {
  max-height: 300px;
  opacity: 1;
}
</style>
