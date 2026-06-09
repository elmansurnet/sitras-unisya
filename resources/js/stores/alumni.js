import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAlumniStore = defineStore('alumni', {
  state: () => ({
    list: [],
    current: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
    filters: {
      search: '',
      study_program_id: null,
      graduation_year_id: null,
      survey_status: null,
      gender: null,
    },
    loading: false,
    loadingDetail: false,
    loadingSubmit: false,
    loadingImport: false,
    importResult: null,
    error: null,
  }),

  getters: {
    hasFilters: (state) =>
      Object.values(state.filters).some((v) => v !== null && v !== ''),
  },

  actions: {
    async fetchList(page = 1) {
      this.loading = true
      this.error = null
      try {
        const params = { page, per_page: this.pagination.per_page, ...this.filters }
        Object.keys(params).forEach((k) => {
          if (params[k] === null || params[k] === '') delete params[k]
        })
        const { data } = await api.get('/admin/alumni', { params })
        this.list = data.data.data
        this.pagination = {
          current_page: data.data.current_page,
          last_page: data.data.last_page,
          per_page: data.data.per_page,
          total: data.data.total,
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchDetail(id) {
      this.loadingDetail = true
      this.current = null
      this.error = null
      try {
        const { data } = await api.get(`/admin/alumni/${id}`)
        this.current = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat detail alumni.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    async fetchProfile() {
      this.loadingDetail = true
      this.current = null
      try {
        const { data } = await api.get('/alumni/profile')
        this.current = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat profil.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    async create(payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.post('/admin/alumni', payload)
        return data
      } catch (err) {
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    async update(id, payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.put(`/admin/alumni/${id}`, payload)
        if (this.current?.id === id) this.current = data.data
        return data
      } catch (err) {
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    async updateProfile(payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.put('/alumni/profile', payload)
        this.current = data.data
        return data
      } catch (err) {
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    async uploadPhoto(formData) {
      this.loadingSubmit = true
      try {
        const { data } = await api.post('/alumni/profile/photo', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        if (this.current) this.current.photo_url = data.data.photo_url
        return data
      } catch (err) {
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    async destroy(id) {
      try {
        await api.delete(`/admin/alumni/${id}`)
        this.list = this.list.filter((a) => a.id !== id)
        this.pagination.total -= 1
      } catch (err) {
        throw err
      }
    },

    async importAlumni(formData, onUploadProgress = null) {
      this.loadingImport = true
      this.importResult = null
      try {
        const { data } = await api.post('/admin/alumni/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress,
        })
        this.importResult = data.data
        return data
      } catch (err) {
        throw err
      } finally {
        this.loadingImport = false
      }
    },

    async exportAlumni() {
      try {
        const params = { ...this.filters }
        Object.keys(params).forEach((k) => {
          if (params[k] === null || params[k] === '') delete params[k]
        })
        const { data } = await api.post('/admin/alumni/export', params)
        return data
      } catch (err) {
        throw err
      }
    },

    async sendInvitation(alumniId, surveyPeriodId, channel = 'whatsapp') {
      try {
        const { data } = await api.post(`/admin/alumni/${alumniId}/send-invitation`, {
          survey_period_id: surveyPeriodId,
          channel,
        })
        return data
      } catch (err) {
        throw err
      }
    },

    // Work History
    async fetchWorkHistory(alumniId = null) {
      try {
        const url = alumniId ? `/admin/alumni/${alumniId}/work-histories` : '/alumni/work-histories'
        const { data } = await api.get(url)
        return data.data
      } catch (err) {
        throw err
      }
    },

    async storeWorkHistory(payload, alumniId = null) {
      try {
        const url = alumniId ? `/admin/alumni/${alumniId}/work-histories` : '/alumni/work-histories'
        const { data } = await api.post(url, payload)
        return data
      } catch (err) {
        throw err
      }
    },

    async updateWorkHistory(historyId, payload) {
      try {
        const { data } = await api.put(`/alumni/work-histories/${historyId}`, payload)
        return data
      } catch (err) {
        throw err
      }
    },

    async destroyWorkHistory(historyId) {
      try {
        await api.delete(`/alumni/work-histories/${historyId}`)
      } catch (err) {
        throw err
      }
    },

    setFilter(key, value) {
      this.filters[key] = value
    },

    resetFilters() {
      this.filters = {
        search: '',
        study_program_id: null,
        graduation_year_id: null,
        survey_status: null,
        gender: null,
      }
    },

    clearCurrent() {
      this.current = null
    },
  },
})
