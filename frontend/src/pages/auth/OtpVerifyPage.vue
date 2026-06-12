<script setup>
/**
 * OtpVerifyPage.vue — Verifikasi kode OTP 6 digit
 * Route: /auth/otp/verify (name: auth.otp.verify)
 * API: POST /api/v1/auth/otp/verify (05_API.md §2.3)
 * Security: OTP 6 digit, max 3 percobaan, cooldown 60s, expired 5 menit
 */
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

const code     = ref('')
const errors   = ref({})
const loading  = ref(false)
const resending = ref(false)

// Countdown untuk resend (60 detik cooldown)
const countdown = ref(60)
let timer = null

const identifier = (() => {
  try {
    return JSON.parse(sessionStorage.getItem('otp_identifier') ?? 'null')
  } catch {
    return null
  }
})()

onMounted(() => {
  // Jika tidak ada identifier, redirect balik
  if (!identifier) {
    router.replace({ name: 'auth.otp.request' })
    return
  }
  startCountdown()
})

onUnmounted(() => clearInterval(timer))

function startCountdown() {
  countdown.value = 60
  clearInterval(timer)
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--
    else clearInterval(timer)
  }, 1000)
}

async function submit() {
  if (code.value.length !== 6) {
    errors.value = { code: ['Kode OTP harus 6 digit.'] }
    return
  }
  errors.value  = {}
  loading.value = true
  try {
    const payload = {
      code: code.value,
      ...(identifier?.mode === 'nim'   ? { nim: identifier.value }   : {}),
      ...(identifier?.mode === 'phone' ? { phone: identifier.value } : {}),
    }
    const res = await api.post('/auth/otp/verify', payload)
    // Store token & user via authStore
    authStore.setSession(res.data.data.token, res.data.data.user)
    sessionStorage.removeItem('otp_identifier')
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) errors.value = data.errors
    else toast.error(data?.message ?? 'Kode OTP tidak valid atau sudah kedaluwarsa.')
  } finally {
    loading.value = false
  }
}

async function resend() {
  if (countdown.value > 0 || !identifier) return
  resending.value = true
  try {
    const payload = identifier.mode === 'nim'
      ? { nim: identifier.value }
      : { phone: identifier.value }
    await api.post('/auth/otp/request', payload)
    toast.success('OTP baru telah dikirim.')
    startCountdown()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengirim ulang OTP.')
  } finally {
    resending.value = false
  }
}

// Auto-submit saat 6 digit masuk
function onInput(e) {
  const val = e.target.value.replace(/\D/g, '').slice(0, 6)
  code.value = val
  if (val.length === 6) submit()
}
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Verifikasi OTP</h1>
      <p class="mt-1 text-sm text-gray-500">
        Masukkan kode 6 digit yang dikirim ke
        <span class="font-medium text-gray-700">
          {{ identifier?.mode === 'nim' ? `NIM ${identifier.value}` : identifier?.value ?? 'WhatsApp Anda' }}
        </span>.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-5" novalidate>
      <div>
        <label for="otp-code" class="block text-sm font-medium text-gray-700 mb-1.5">Kode OTP</label>
        <input
          id="otp-code"
          :value="code"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          :disabled="loading"
          :class="[
            'w-full h-12 px-4 rounded-lg border text-center text-2xl font-mono tracking-[0.5em] outline-none transition-shadow',
            errors.code
              ? 'border-red-400 focus:ring-2 focus:ring-red-300'
              : 'border-gray-300 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500',
          ]"
          placeholder="------"
          @input="onInput"
        />
        <p v-if="errors.code" class="mt-1 text-xs text-red-500">{{ errors.code[0] }}</p>
      </div>

      <button
        type="submit"
        :disabled="loading || code.length !== 6"
        class="w-full h-10 rounded-lg bg-emerald-600 text-white text-sm font-semibold
               hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400
               focus:ring-offset-1 disabled:opacity-60 transition-colors"
      >
        <span v-if="loading" class="inline-flex items-center gap-2">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          Memverifikasi...
        </span>
        <span v-else>Verifikasi</span>
      </button>
    </form>

    <!-- Resend -->
    <div class="mt-5 text-center text-sm text-gray-500">
      <span v-if="countdown > 0">Kirim ulang OTP dalam {{ countdown }} detik</span>
      <button
        v-else
        type="button"
        :disabled="resending"
        class="text-emerald-600 font-medium hover:underline disabled:opacity-60"
        @click="resend"
      >
        {{ resending ? 'Mengirim...' : 'Kirim Ulang OTP' }}
      </button>
    </div>

    <div class="mt-4 text-center">
      <router-link :to="{ name: 'auth.otp.request' }" class="text-sm text-gray-400 hover:text-gray-600">
        ← Kembali
      </router-link>
    </div>
  </div>
</template>
