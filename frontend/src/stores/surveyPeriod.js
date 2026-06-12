/**
 * stores/surveyPeriod.js — Pinia Survey Period Store
 *
 * Digunakan oleh:
 *   - pages/admin/survey-periods/SurveyPeriodIndexPage.vue
 *   - pages/admin/survey-periods/SurveyPeriodDetailPage.vue
 *
 * API endpoints (05_API.md §admin-survey-periods):
 *   GET    /api/v1/admin/survey-periods              → fetchList
 *   POST   /api/v1/admin/survey-periods              → create
 *   GET    /api/v1/admin/survey-periods/:id          → fetchDetail
 *   PUT    /api/v1/admin/survey-periods/:id          → update
 *   DELETE /api/v1/admin/survey-periods/:id          → destroy
 *   POST   /api/v1/admin/survey-periods/:id/activate → activate
 *   POST   /api/v1/admin/survey-periods/:id/close    → close
 *   POST   /api/v1/admin/survey-periods/:id/invitations → sendInvitations
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSurveyPeriodStore = defineStore('surveyPeriod', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------
  const list       = ref([])    // array of survey period summaries
  const current    = ref(null)  // detail period yang sedang diedit/dilihat
  const loading    = ref(false)
  const submitting = ref(false)
  const error      = ref(null)

  const pagination = ref({
    currentPage : 1,
    perPage     : 15,
    total       : 0,
    lastPage    : 1,
  })

  const filters = ref({
    search : '',
    status : '',
  })

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------
  const hasFilters = computed(() =>
    !!filters.value.search || !!filters.value.status
  )

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------
  function _buildParams(page = 1) {
    const p = { page, per_page: pagination.value.perPage }
    if (filters.value.search) p.search = filters.value.search
    if (filters.value.status) p.status = filters.value.status
    return p
  }

  function _setPagination(meta) {
    if (!meta) return
    pagination.value.currentPage = meta.current_page ?? 1
    pagination.value.perPage     = meta.per_page     ?? 15
    pagination.value.total       = meta.total        ?? 0
    pagination.value.lastPage    = meta.last_page     ?? 1
  }

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  /** Ambil daftar periode survei dengan pagination + filter */
  async function fetchList(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get('/admin/survey-periods', { params: _buildParams(page) })
      list.value = data.data ?? []
      _setPagination(data.meta)
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal memuat daftar periode survei.'
    } finally {
      loading.value = false
    }
  }

  /** Ambil detail satu periode survei (untuk halaman edit/detail) */
  async function fetchDetail(id) {
    loading.value = true
    error.value   = null
    current.value = null
    try {
      const { data } = await api.get(`/admin/survey-periods/${id}`)
      current.value = data.data
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal memuat detail periode survei.'
      throw err
    } finally {
      loading.value = false
    }
  }

  /** Buat periode survei baru */
  async function create(payload) {
    submitting.value = true
    error.value      = null
    try {
      const { data } = await api.post('/admin/survey-periods', payload)
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal membuat periode survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  /** Update periode survei */
  async function update(id, payload) {
    submitting.value = true
    error.value      = null
    try {
      const { data } = await api.put(`/admin/survey-periods/${id}`, payload)
      current.value = data.data
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal menyimpan periode survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  /** Hapus periode survei (hanya status draft) */
  async function destroy(id) {
    submitting.value = true
    error.value      = null
    try {
      await api.delete(`/admin/survey-periods/${id}`)
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal menghapus periode survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  /** Aktifkan periode survei (draft → active) */
  async function activate(id) {
    submitting.value = true
    error.value      = null
    try {
      const { data } = await api.post(`/admin/survey-periods/${id}/activate`)
      _updateListItem(data.data)
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal mengaktifkan periode survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  /** Tutup periode survei (active → closed) */
  async function close(id) {
    submitting.value = true
    error.value      = null
    try {
      const { data } = await api.post(`/admin/survey-periods/${id}/close`)
      _updateListItem(data.data)
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal menutup periode survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  /**
   * Kirim undangan survei ke alumni yang belum diundang.
   * @param {number} id - survey_period ID
   * @param {number} questionnaireId - questionnaire yang digunakan
   */
  async function sendInvitations(id, questionnaireId) {
    submitting.value = true
    error.value      = null
    try {
      const { data } = await api.post(`/admin/survey-periods/${id}/invitations`, {
        questionnaire_id: questionnaireId,
      })
      return data.data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Gagal mengirim undangan survei.'
      throw err
    } finally {
      submitting.value = false
    }
  }

  // ---------------------------------------------------------------------------
  // Filter helpers
  // ---------------------------------------------------------------------------
  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function resetFilters() {
    filters.value = { search: '', status: '' }
  }

  // ---------------------------------------------------------------------------
  // Internal helpers
  // ---------------------------------------------------------------------------
  function _updateListItem(updated) {
    const idx = list.value.findIndex((p) => p.id === updated.id)
    if (idx !== -1) list.value[idx] = updated
  }

  function clearCurrent() {
    current.value = null
    error.value   = null
  }

  // ---------------------------------------------------------------------------
  // Expose
  // ---------------------------------------------------------------------------
  return {
    // state
    list, current, loading, submitting, error, pagination, filters,
    // getters
    hasFilters,
    // actions
    fetchList, fetchDetail, create, update, destroy,
    activate, close, sendInvitations,
    setFilters, resetFilters, clearCurrent,
  }
})
