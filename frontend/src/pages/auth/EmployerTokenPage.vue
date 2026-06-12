<script setup>
/**
 * EmployerTokenPage.vue
 * Halaman akses employer via token URL: /login/employer/:token
 * Token divalidasi → redirect ke /employer/survey dengan session employer
 * Sesuai 06_UI_UX.md §8 + 07_SECURITY.md (ValidateEmployerToken middleware)
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route  = useRoute()
const router = useRouter()

const state   = ref('loading') // 'loading' | 'invalid' | 'expired'
const message = ref('')

onMounted(async () => {
  const token = route.params.token
  if (!token) {
    state.value   = 'invalid'
    message.value = 'Token tidak ditemukan di URL.'
    return
  }
  try {
    // POST /api/v1/employer/verify-token — backend set cookie/session employer
    await api.post('/employer/verify-token', { token })
    router.replace({ name: 'employer.survey' })
  } catch (err) {
    const status = err.response?.status
    if (status === 410) {
      state.value   = 'expired'
      message.value = 'Link survei sudah kadaluarsa atau sudah pernah digunakan.'
    } else if (status === 404) {
      state.value   = 'invalid'
      message.value = 'Token tidak valid. Pastikan link yang Anda gunakan benar.'
    } else {
      state.value   = 'invalid'
      message.value = 'Terjadi kesalahan. Silakan coba lagi atau hubungi administrator.'
    }
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <!-- Loading -->
    <div v-if="state === 'loading'" class="text-center">
      <div class="inline-block w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4" />
      <p class="text-gray-600 text-sm">Memverifikasi akses survei…</p>
    </div>

    <!-- Invalid / Expired -->
    <div v-else class="bg-white rounded-xl shadow-md p-8 max-w-sm w-full text-center">
      <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
           :class="state === 'expired' ? 'bg-yellow-100' : 'bg-red-100'">
        <svg v-if="state === 'expired'" class="w-7 h-7 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-gray-800 mb-2">
        {{ state === 'expired' ? 'Link Kadaluarsa' : 'Akses Tidak Valid' }}
      </h2>
      <p class="text-sm text-gray-500">{{ message }}</p>
    </div>
  </div>
</template>
