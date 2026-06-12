/**
 * api.js — Axios instance terpusat
 * Implementasi sesuai 04_ARCHITECTURE.md §4.4
 *
 * - baseURL  : VITE_API_URL + '/api/v1'
 * - Request  : inject Bearer token dari localStorage
 * - Response : handle 401 (redirect login) + 403 (redirect unauthorized)
 *
 * PENTING: Semua API call WAJIB via instance ini, BUKAN axios langsung.
 */

import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: (import.meta.env.VITE_API_URL ?? '') + '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
  },
  withCredentials: true, // kirim cookie untuk Sanctum CSRF
})

// ---------------------------------------------------------------------------
// Request Interceptor — inject Bearer token
// ---------------------------------------------------------------------------
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// ---------------------------------------------------------------------------
// Response Interceptor — handle 401 / 403
// ---------------------------------------------------------------------------
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Token expired atau tidak valid — hapus token dan redirect ke login
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' })
      }
    } else if (status === 403) {
      // Tidak punya izin akses
      router.push({ name: 'unauthorized' })
    }

    return Promise.reject(error)
  },
)

export default api
