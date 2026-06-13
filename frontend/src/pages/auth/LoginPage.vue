<script setup>
/**
 * LoginPage.vue — Login Admin/Superadmin
 * Route: /auth/login (name: login)
 * API: POST /api/v1/auth/login (05_API.md §2.1)
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const router    = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

const form = reactive({
  email:    '',
  password: '',
})

const errors  = ref({})
const loading = ref(false)
const showPw  = ref(false)

async function submit() {
  errors.value  = {}
  loading.value = true
  try {
    await authStore.loginAdmin(form.email, form.password)
    router.push({ name: 'admin.dashboard' })
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      errors.value = data.errors
    } else {
      toast.error(data?.message ?? 'Login gagal. Periksa email dan password.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Masuk sebagai Admin</h1>
      <p class="mt-1 text-sm text-gray-500">Gunakan akun administrator yang diberikan.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-5" novalidate>
      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          :disabled="loading"
          :class="[
            'w-full h-10 px-3 rounded-lg border text-sm outline-none transition-shadow',
            errors.email
              ? 'border-red-400 focus:ring-2 focus:ring-red-300'
              : 'border-gray-300 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500',
          ]"
          placeholder="admin@unisya.ac.id"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPw ? 'text' : 'password'"
            autocomplete="current-password"
            :disabled="loading"
            :class="[
              'w-full h-10 pl-3 pr-10 rounded-lg border text-sm outline-none transition-shadow',
              errors.password
                ? 'border-red-400 focus:ring-2 focus:ring-red-300'
                : 'border-gray-300 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500',
            ]"
            placeholder="••••••••"
          />
          <button
            type="button"
            tabindex="-1"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
            @click="showPw = !showPw"
            :aria-label="showPw ? 'Sembunyikan password' : 'Tampilkan password'"
          >
            <svg v-if="showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
        <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
      </div>

      <!-- Submit -->
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
          Masuk...
        </span>
        <span v-else>Masuk</span>
      </button>
    </form>

    <!-- Link ke alumni login -->
    <p class="mt-6 text-center text-sm text-gray-500">
      Kamu alumni?
      <router-link :to="{ name: 'auth.otp.request' }" class="text-emerald-600 font-medium hover:underline">
        Login via OTP
      </router-link>
    </p>
  </div>
</template>
