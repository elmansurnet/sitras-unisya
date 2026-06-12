<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header minimal: distraction-free untuk survei -->
    <header class="bg-white border-b border-gray-200 h-14">
      <div class="max-w-3xl mx-auto h-full flex items-center justify-between px-4 sm:px-6">
        <!-- Logo kiri -->
        <div class="flex items-center gap-2">
          <svg class="w-7 h-7 text-emerald-600" viewBox="0 0 40 40" fill="none" aria-label="Logo UNISYA">
            <rect width="40" height="40" rx="8" fill="currentColor" fill-opacity="0.12" />
            <path d="M8 28V14l12-6 12 6v14l-12 6L8 28Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
          </svg>
          <span class="text-sm font-semibold text-gray-700">SITRAS UNISYA</span>
        </div>

        <!-- Nama perusahaan kanan -->
        <div class="text-right" v-if="companyName">
          <p class="text-sm font-medium text-gray-800">{{ companyName }}</p>
          <p class="text-xs text-gray-400">Survei Tracer Study</p>
        </div>
      </div>
    </header>

    <!-- Content: terpusat, lebar terbatas agar fokus -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-8">
      <router-view />
    </main>

    <!-- Footer minimal -->
    <footer class="py-4 text-center text-xs text-gray-400 border-t border-gray-100">
      &copy; {{ new Date().getFullYear() }} Universitas Islam Syarifuddin &mdash; Sistem Tracer Study
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore  = useAuthStore()
const companyName = computed(() => authStore.user?.company_name ?? null)
</script>
