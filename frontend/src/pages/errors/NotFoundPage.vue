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
      <!-- Ilustrasi -->
      <div class="relative mb-8">
        <div class="text-[9rem] font-black text-gray-100 leading-none select-none">404</div>
        <div class="absolute inset-0 flex items-center justify-center">
          <svg class="w-20 h-20 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <h1 class="text-2xl font-bold text-gray-800 mb-2">Halaman Tidak Ditemukan</h1>
      <p class="text-sm text-gray-500 mb-8">
        Maaf, halaman yang Anda cari tidak ada atau telah dipindahkan.
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
