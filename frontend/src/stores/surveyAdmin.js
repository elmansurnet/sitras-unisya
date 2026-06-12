/**
 * stores/surveyAdmin.js — Pinia Survey Admin Store
 *
 * Digunakan oleh:
 *   - pages/admin/survey-periods/SurveyPeriodIndexPage.vue
 *   - pages/admin/survey-periods/SurveyPeriodDetailPage.vue
 *
 * State  : periods, selectedPeriod, pagination, loading, error
 * Actions:
 *   fetchPeriods      GET  /api/v1/admin/survey-periods
 *   fetchPeriod       GET  /api/v1/admin/survey-periods/{id}
 *   createPeriod      POST /api/v1/admin/survey-periods
 *   updatePeriod      PUT  /api/v1/admin/survey-periods/{id}
 *   activatePeriod    POST /api/v1/admin/survey-periods/{id}/activate
 *   closePeriod       POST /api/v1/admin/survey-periods/{id}/close
 *   blastInvitations  POST /api/v1/admin/survey-periods/{id}/send-invitations
 */

import { defineStore } from 'pinia'
import { ref }         from 'vue'
import api             from '@/services/api'

export const useSurveyAdminStore = defineStore('surveyAdmin', () => {
  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------
  const periods         = ref([])
  const selectedPeriod  = ref(null)
  const pagination      = ref({
    current_page : 1,
    per_page     : 20,
    total        : 0,
    last_page    : 1,
  })
  const loading = ref(false)
  const error   = ref(null)

  // -------------------------------------------------------------------------
  // Actions
  // -------------------------------------------------------------------------

  /**
   * Ambil daftar periode survei (paginated).
   * @param {{ page?: number, status?: string, per_page?: number }} params
   */
  async function fetchPeriods(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get('/admin/survey-periods', {
        params: { per_page: 20, ...params },
      })
      periods.value = data.data ?? []
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
      error.value = err.response?.data ?? { message: 'Gagal memuat periode survei.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Ambil detail satu periode survei.
   * @param {number} id
   */
  async function fetchPeriod(id) {
    loading.value = true
    error.value   = null
    try {
      const { data }    = await api.get(`/admin/survey-periods/${id}`)
      selectedPeriod.value = data.data
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal memuat detail periode.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Buat periode baru.
   * @param {object} payload
   */
  async function createPeriod(payload) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post('/admin/survey-periods', payload)
      periods.value.unshift(data.data)
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal membuat periode.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update periode yang ada.
   * @param {number} id
   * @param {object} payload
   */
  async function updatePeriod(id, payload) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.put(`/admin/survey-periods/${id}`, payload)
      const idx = periods.value.findIndex((p) => p.id === id)
      if (idx !== -1) periods.value[idx] = data.data
      if (selectedPeriod.value?.id === id) selectedPeriod.value = data.data
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal memperbarui periode.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Aktifkan periode (status: draft → aktif).
   * @param {number} id
   */
  async function activatePeriod(id) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post(`/admin/survey-periods/${id}/activate`)
      _syncPeriod(id, data.data)
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal mengaktifkan periode.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Tutup periode (status: aktif → ditutup).
   * @param {number} id
   */
  async function closePeriod(id) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post(`/admin/survey-periods/${id}/close`)
      _syncPeriod(id, data.data)
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal menutup periode.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Kirim undangan massal ke alumni (blast).
   * Body: { questionnaire_id } — sesuai catatan di 05_API.md:
   * survey_periods tidak punya FK ke questionnaires; questionnaire_id
   * dikirim sebagai parameter saat blast, bukan disimpan di tabel.
   *
   * @param {number} periodId
   * @param {number} questionnaireId
   */
  async function blastInvitations(periodId, questionnaireId) {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.post(
        `/admin/survey-periods/${periodId}/send-invitations`,
        { questionnaire_id: questionnaireId },
      )
      return data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal mengirim undangan.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  // -------------------------------------------------------------------------
  // Private helper
  // -------------------------------------------------------------------------
  function _syncPeriod(id, updated) {
    const idx = periods.value.findIndex((p) => p.id === id)
    if (idx !== -1) periods.value[idx] = updated
    if (selectedPeriod.value?.id === id) selectedPeriod.value = updated
  }

  // -------------------------------------------------------------------------
  // Expose
  // -------------------------------------------------------------------------
  return {
    periods, selectedPeriod, pagination, loading, error,
    fetchPeriods, fetchPeriod, createPeriod, updatePeriod,
    activatePeriod, closePeriod, blastInvitations,
  }
})
