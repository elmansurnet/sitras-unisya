<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

function goHome() {
  switch (authStore.userRole) {
    case 'superadmin':
    case 'admin':
      router.push({ name: 'admin.dashboard' })
      break
    case 'alumni':
      router.push({ name: 'alumni.home' })
      break
    case 'employer':
      router.push({ name: 'employer.survey.fill' })
      break
    default:
      router.push({ name: 'login' })
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="max-w-md w-full text-center">
      <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0-6v2m-6 4h12a2 2 0 002-2V9a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 9V7a2 2 0 114 0v2" />
        </svg>
      </div>
      <h1 class="text-5xl font-bold text-gray-800 mb-2">403</h1>
      <h2 class="text-xl font-semibold text-gray-700 mb-3">Akses Ditolak</h2>
      <p class="text-sm text-gray-500 mb-8">
        Anda tidak memiliki izin untuk mengakses halaman ini.
      </p>
      <button
        type="button"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-emerald-600
               text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
        @click="goHome"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Kembali ke Beranda
      </button>
    </div>
  </div>
</template>
