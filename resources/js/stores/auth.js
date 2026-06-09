/**
 * stores/auth.js — Pinia Auth Store
 *
 * State  : user, token, loading, error
 * Getters: isAuthenticated, isAdmin, isSuperadmin, isAlumni, isEmployer, userRole
 * Actions: loginAdmin, loginOtp, loginEmployer, logout, fetchMe
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------
  const user    = ref(JSON.parse(localStorage.getItem('auth_user') ?? 'null'))
  const token   = ref(localStorage.getItem('auth_token') ?? null)
  const loading = ref(false)
  const error   = ref(null)

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole        = computed(() => user.value?.role ?? null)
  const isSuperadmin    = computed(() => user.value?.role === 'superadmin')
  const isAdmin         = computed(() => ['superadmin', 'admin'].includes(user.value?.role))
  const isAlumni        = computed(() => user.value?.role === 'alumni')
  const isEmployer      = computed(() => user.value?.role === 'employer')

  // ---------------------------------------------------------------------------
  // Helpers (private)
  // ---------------------------------------------------------------------------
  function _setAuth(authToken, authUser) {
    token.value = authToken
    user.value  = authUser
    localStorage.setItem('auth_token', authToken)
    localStorage.setItem('auth_user', JSON.stringify(authUser))
  }

  function _clearAuth() {
    token.value = null
    user.value  = null
    error.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  /**
   * Login superadmin / admin via email + password
   */
  async function loginAdmin(email, password) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post('/auth/login', { email, password })
      _setAuth(data.data.token, data.data.user)
      return data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Terjadi kesalahan. Coba lagi.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Login alumni via OTP (verifikasi OTP)
   */
  async function loginOtp(identifier, identifierType, otpCode) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post('/auth/otp/verify', {
        identifier,
        identifier_type: identifierType,
        otp_code: otpCode,
      })
      _setAuth(data.data.token, data.data.user)
      return data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Terjadi kesalahan. Coba lagi.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Login employer via survey token (token di URL)
   */
  async function loginEmployer(surveyToken) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get(`/auth/employer/token/${surveyToken}`)
      _setAuth(data.data.token, { ...data.data.employer, role: 'employer' })
      return data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Link survei tidak valid.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout: hapus token dari server + clear localStorage
   */
  async function logout() {
    loading.value = true
    try {
      await api.post('/auth/logout')
    } catch {
      // Tetap clear meski request gagal
    } finally {
      _clearAuth()
      loading.value = false
      router.push({ name: 'login' })
    }
  }

  /**
   * Fetch data user yang sedang login (/auth/me)
   */
  async function fetchMe() {
    if (!token.value) return null
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.data
      localStorage.setItem('auth_user', JSON.stringify(data.data))
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal mengambil data pengguna.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  // ---------------------------------------------------------------------------
  // Expose
  // ---------------------------------------------------------------------------
  return {
    // state
    user, token, loading, error,
    // getters
    isAuthenticated, userRole, isSuperadmin, isAdmin, isAlumni, isEmployer,
    // actions
    loginAdmin, loginOtp, loginEmployer, logout, fetchMe,
  }
})
