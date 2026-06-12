<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Topbar -->
    <header class="sticky top-0 z-20 bg-white border-b border-gray-200 h-14">
      <div class="max-w-5xl mx-auto h-full flex items-center justify-between px-4 sm:px-6">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <svg class="w-7 h-7 text-emerald-600" viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="8" fill="currentColor" fill-opacity="0.12" />
            <path d="M8 28V14l12-6 12 6v14l-12 6L8 28Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
          </svg>
          <span class="font-semibold text-gray-800 text-sm">SITRAS UNISYA</span>
        </div>

        <!-- Nav links (hidden di mobile, tampil di sm+) -->
        <nav class="hidden sm:flex items-center gap-1" aria-label="Menu alumni">
          <router-link
            v-for="item in navItems"
            :key="item.name"
            :to="{ name: item.name }"
            class="px-3 py-2 rounded-md text-sm text-gray-600 hover:text-emerald-700 hover:bg-emerald-50
                   transition-colors [&.router-link-active]:text-emerald-700 [&.router-link-active]:font-medium"
          >
            {{ item.label }}
          </router-link>
        </nav>

        <!-- User info + logout -->
        <div class="flex items-center gap-3">
          <div class="hidden sm:block text-right">
            <p class="text-sm font-medium text-gray-800 leading-none">
              {{ authStore.user?.alumni?.full_name ?? authStore.user?.name }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ authStore.user?.alumni?.study_program ?? 'Alumni' }}
            </p>
          </div>
          <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">
            {{ initial }}
          </div>
          <button
            class="text-sm text-gray-500 hover:text-red-500 transition-colors hidden sm:block"
            @click="authStore.logout()"
          >Keluar</button>

          <!-- Hamburger mobile -->
          <button
            class="sm:hidden p-1.5 rounded text-gray-500 hover:bg-gray-100"
            @click="mobileMenuOpen = !mobileMenuOpen"
            aria-label="Menu"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu dropdown -->
      <Transition name="slide-down">
        <div v-if="mobileMenuOpen" class="sm:hidden bg-white border-t border-gray-100 px-4 py-2 space-y-1">
          <router-link
            v-for="item in navItems"
            :key="item.name"
            :to="{ name: item.name }"
            class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >{{ item.label }}</router-link>
          <button
            class="w-full text-left px-3 py-2 text-sm text-red-500"
            @click="authStore.logout()"
          >Keluar</button>
        </div>
      </Transition>
    </header>

    <!-- Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore     = useAuthStore()
const mobileMenuOpen = ref(false)

const initial = computed(() => {
  const name = authStore.user?.alumni?.full_name ?? authStore.user?.name ?? 'A'
  return name.charAt(0).toUpperCase()
})

const navItems = [
  { name: 'alumni.home',        label: 'Beranda' },
  { name: 'alumni.profile',     label: 'Profil' },
  { name: 'alumni.employment',  label: 'Riwayat Pekerjaan' },
  { name: 'alumni.survey',      label: 'Isi Survei' },
]
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active {
  transition: max-height 0.2s ease, opacity 0.2s;
}
.slide-down-enter-from, .slide-down-leave-to {
  max-height: 0;
  opacity: 0;
}
.slide-down-enter-to, .slide-down-leave-from {
  max-height: 300px;
  opacity: 1;
}
</style>
