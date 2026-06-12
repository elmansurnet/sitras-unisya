/**
 * stores/notification.js — Pinia Notification Store
 *
 * Digunakan oleh:
 *   - pages/admin/notifications/NotificationTemplatePage.vue
 *   - pages/admin/notifications/NotificationLogPage.vue
 *
 * State  : templates, selectedTemplate, logs, pagination, filters, loading, error
 * Getters: activeTemplates, templatesByType
 * Actions:
 *   Templates : fetchTemplates, fetchTemplate, createTemplate,
 *               updateTemplate, deleteTemplate
 *   Logs      : fetchLogs, setLogFilters, resetLogFilters
 *
 * API:
 *   GET    /api/v1/admin/notifications/templates
 *   GET    /api/v1/admin/notifications/templates/{id}
 *   POST   /api/v1/admin/notifications/templates
 *   PUT    /api/v1/admin/notifications/templates/{id}
 *   DELETE /api/v1/admin/notifications/templates/{id}
 *   GET    /api/v1/admin/notifications/logs
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificationStore = defineStore('notification', () => {
  // ---------------------------------------------------------------------------
  // State — Templates
  // ---------------------------------------------------------------------------
  const templates         = ref([])   // array NotificationTemplate
  const selectedTemplate  = ref(null) // template sedang diedit/dilihat
  const templateLoading   = ref(false)
  const templateError     = ref(null)

  // ---------------------------------------------------------------------------
  // State — Logs
  // ---------------------------------------------------------------------------
  const logs        = ref([])   // array NotificationLog
  const pagination  = ref({
    current_page : 1,
    per_page     : 20,
    total        : 0,
    last_page    : 1,
  })
  const filters     = ref({
    type            : '',   // 'email' | 'whatsapp' | ''
    status          : '',   // 'pending' | 'sent' | 'failed' | 'delivered' | ''
    recipient_type  : '',   // 'alumni' | 'employer' | ''
    date_from       : '',   // YYYY-MM-DD
    date_to         : '',   // YYYY-MM-DD
    page            : 1,
    per_page        : 20,
  })
  const logLoading  = ref(false)
  const logError    = ref(null)

  // Alias loading umum (dipakai halaman yang butuh satu flag)
  const loading = computed(() => templateLoading.value || logLoading.value)
  const error   = computed(() => templateError.value   || logError.value)

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------
  const activeTemplates = computed(() =>
    templates.value.filter((t) => t.is_active)
  )

  const templatesByType = computed(() => ({
    email    : templates.value.filter((t) => t.type === 'email'),
    whatsapp : templates.value.filter((t) => t.type === 'whatsapp'),
  }))

  // ---------------------------------------------------------------------------
  // Actions — Templates
  // ---------------------------------------------------------------------------

  /**
   * Ambil daftar template.
   * @param {{ type?: string, event?: string }} params
   */
  async function fetchTemplates(params = {}) {
    templateLoading.value = true
    templateError.value   = null
    try {
      const { data } = await api.get('/admin/notifications/templates', { params })
      templates.value = data.data ?? []
      return templates.value
    } catch (err) {
      templateError.value = err.response?.data ?? { message: 'Gagal memuat template notifikasi.' }
      throw err
    } finally {
      templateLoading.value = false
    }
  }

  /**
   * Ambil detail satu template.
   * @param {number} id
   */
  async function fetchTemplate(id) {
    templateLoading.value = true
    templateError.value   = null
    try {
      const { data } = await api.get(`/admin/notifications/templates/${id}`)
      selectedTemplate.value = data.data
      return data.data
    } catch (err) {
      templateError.value = err.response?.data ?? { message: 'Gagal memuat detail template.' }
      throw err
    } finally {
      templateLoading.value = false
    }
  }

  /**
   * Buat template baru.
   * @param {object} payload { name, type, event, subject, body, variables, is_active }
   */
  async function createTemplate(payload) {
    templateLoading.value = true
    templateError.value   = null
    try {
      const { data } = await api.post('/admin/notifications/templates', payload)
      templates.value.unshift(data.data)
      return data.data
    } catch (err) {
      templateError.value = err.response?.data ?? { message: 'Gagal membuat template.' }
      throw err
    } finally {
      templateLoading.value = false
    }
  }

  /**
   * Update template yang ada.
   * @param {number} id
   * @param {object} payload
   */
  async function updateTemplate(id, payload) {
    templateLoading.value = true
    templateError.value   = null
    try {
      const { data } = await api.put(`/admin/notifications/templates/${id}`, payload)
      const idx = templates.value.findIndex((t) => t.id === id)
      if (idx !== -1) templates.value[idx] = data.data
      if (selectedTemplate.value?.id === id) selectedTemplate.value = data.data
      return data.data
    } catch (err) {
      templateError.value = err.response?.data ?? { message: 'Gagal memperbarui template.' }
      throw err
    } finally {
      templateLoading.value = false
    }
  }

  /**
   * Hapus template.
   * @param {number} id
   */
  async function deleteTemplate(id) {
    templateLoading.value = true
    templateError.value   = null
    try {
      await api.delete(`/admin/notifications/templates/${id}`)
      templates.value = templates.value.filter((t) => t.id !== id)
      if (selectedTemplate.value?.id === id) selectedTemplate.value = null
    } catch (err) {
      templateError.value = err.response?.data ?? { message: 'Gagal menghapus template.' }
      throw err
    } finally {
      templateLoading.value = false
    }
  }

  // ---------------------------------------------------------------------------
  // Actions — Logs
  // ---------------------------------------------------------------------------

  /**
   * Ambil daftar log notifikasi dengan filter aktif.
   */
  async function fetchLogs() {
    logLoading.value = true
    logError.value   = null
    try {
      // Bersihkan filter kosong sebelum dikirim ke API
      const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '' && v !== null)
      )
      const { data } = await api.get('/admin/notifications/logs', { params })
      logs.value = data.data ?? []
      if (data.meta) {
        pagination.value = {
          current_page : data.meta.current_page,
          per_page     : data.meta.per_page,
          total        : data.meta.total,
          last_page    : data.meta.last_page,
        }
      }
      return data
    } catch (err) {
      logError.value = err.response?.data ?? { message: 'Gagal memuat log notifikasi.' }
      throw err
    } finally {
      logLoading.value = false
    }
  }

  /**
   * Set filter log dan otomatis reload.
   * @param {object} newFilters Partial filter object
   */
  async function setLogFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters, page: 1 }
    await fetchLogs()
  }

  /** Ganti halaman paginasi log */
  async function setLogPage(page) {
    filters.value.page = page
    await fetchLogs()
  }

  /** Reset semua filter log ke default */
  async function resetLogFilters() {
    filters.value = {
      type           : '',
      status         : '',
      recipient_type : '',
      date_from      : '',
      date_to        : '',
      page           : 1,
      per_page       : 20,
    }
    await fetchLogs()
  }

  // ---------------------------------------------------------------------------
  // Expose
  // ---------------------------------------------------------------------------
  return {
    // state — templates
    templates, selectedTemplate, templateLoading, templateError,
    // state — logs
    logs, pagination, filters, logLoading, logError,
    // computed aliases
    loading, error,
    // getters
    activeTemplates, templatesByType,
    // actions — templates
    fetchTemplates, fetchTemplate, createTemplate, updateTemplate, deleteTemplate,
    // actions — logs
    fetchLogs, setLogFilters, setLogPage, resetLogFilters,
  }
})
