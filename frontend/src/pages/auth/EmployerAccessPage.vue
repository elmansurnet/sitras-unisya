<script setup>
/**
 * EmployerAccessPage.vue — Validasi token survei employer
 * Route: /survey/:token (name: employer.access)
 * API: POST /api/v1/auth/employer/access (05_API.md §2.4)
 * Token: Str::random(64), one-survey use, expired 30 hari
 */
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

const loading = ref(true)
const error   = ref(null)

onMounted(async () => {
  const token = route.params.token
  try {
    const res = await api.post('/auth/employer/access', { token })
    authStore.setSession(res.data.data.token, res.data.data.user)
    router.replace({ name: 'employer.survey.fill' })
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Token tidak valid atau sudah kedaluwarsa.'
    error.value = msg
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center">
      <svg class="animate-spin h-10 w-10 text-emerald-500 mx-auto mb-4" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
      <p class="text-sm text-gray-500">Memverifikasi token survei...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="max-w-md w-full text-center">
      <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>
      <h1 class="text-xl font-semibold text-gray-900 mb-2">Akses Tidak Valid</h1>
      <p class="text-sm text-gray-500 mb-6">{{ error }}</p>
      <p class="text-xs text-gray-400">Hubungi pihak kampus jika Anda yakin link ini masih berlaku.</p>
    </div>
  </div>
</template>
