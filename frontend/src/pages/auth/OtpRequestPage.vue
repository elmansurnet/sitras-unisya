<script setup>
/**
 * OtpRequestPage.vue — Alumni: minta OTP via nomor WA/NIM
 * Route: /auth/otp (name: auth.otp.request)
 * API: POST /api/v1/auth/otp/request (05_API.md §2.2)
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const toast  = useToast()

const form = reactive({ nim: '', phone: '' })
const errors  = ref({})
const loading = ref(false)
const mode    = ref('nim') // 'nim' | 'phone'

async function submit() {
  errors.value  = {}
  loading.value = true
  try {
    const payload = mode.value === 'nim'
      ? { nim: form.nim }
      : { phone: form.phone }
    const res = await api.post('/auth/otp/request', payload)
    // Simpan identifier sementara di sessionStorage
    sessionStorage.setItem('otp_identifier', JSON.stringify({
      mode: mode.value,
      value: mode.value === 'nim' ? form.nim : form.phone,
    }))
    toast.success(res.data?.message ?? 'OTP berhasil dikirim ke WhatsApp Anda.')
    router.push({ name: 'auth.otp.verify' })
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) errors.value = data.errors
    else toast.error(data?.message ?? 'Gagal mengirim OTP.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Login Alumni</h1>
      <p class="mt-1 text-sm text-gray-500">Masukkan NIM atau nomor WA untuk menerima kode OTP.</p>
    </div>

    <!-- Mode toggle -->
    <div class="flex rounded-lg border border-gray-200 p-1 mb-6 bg-gray-50">
      <button
        type="button"
        :class="[
          'flex-1 py-1.5 text-sm font-medium rounded-md transition-colors',
          mode === 'nim'
            ? 'bg-white text-emerald-700 shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
        ]"
        @click="mode = 'nim'"
      >Pakai NIM</button>
      <button
        type="button"
        :class="[
          'flex-1 py-1.5 text-sm font-medium rounded-md transition-colors',
          mode === 'phone'
            ? 'bg-white text-emerald-700 shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
        ]"
        @click="mode = 'phone'"
      >Pakai No. WA</button>
    </div>

    <form @submit.prevent="submit" class="space-y-5" novalidate>
      <!-- NIM -->
      <div v-if="mode === 'nim'">
        <label for="nim" class="block text-sm font-medium text-gray-700 mb-1.5">NIM</label>
        <input
          id="nim"
          v-model="form.nim"
          type="text"
          inputmode="numeric"
          :disabled="loading"
          :class="[
            'w-full h-10 px-3 rounded-lg border text-sm outline-none transition-shadow',
            errors.nim
              ? 'border-red-400 focus:ring-2 focus:ring-red-300'
              : 'border-gray-300 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500',
          ]"
          placeholder="Nomor Induk Mahasiswa"
        />
        <p v-if="errors.nim" class="mt-1 text-xs text-red-500">{{ errors.nim[0] }}</p>
      </div>

      <!-- Phone -->
      <div v-else>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Nomor WhatsApp</label>
        <input
          id="phone"
          v-model="form.phone"
          type="tel"
          :disabled="loading"
          :class="[
            'w-full h-10 px-3 rounded-lg border text-sm outline-none transition-shadow',
            errors.phone
              ? 'border-red-400 focus:ring-2 focus:ring-red-300'
              : 'border-gray-300 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500',
          ]"
          placeholder="08xxxxxxxxxx"
        />
        <p v-if="errors.phone" class="mt-1 text-xs text-red-500">{{ errors.phone[0] }}</p>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full h-10 rounded-lg bg-emerald-600 text-white text-sm font-semibold
               hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400
               focus:ring-offset-1 disabled:opacity-60 transition-colors"
      >
        <span v-if="loading" class="inline-flex items-center gap-2">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          Mengirim OTP...
        </span>
        <span v-else>Kirim OTP</span>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Admin?
      <router-link :to="{ name: 'login' }" class="text-emerald-600 font-medium hover:underline">Login Admin</router-link>
    </p>
  </div>
</template>
