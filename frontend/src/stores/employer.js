import { defineStore } from 'pinia'
import api from '@/services/api'

export const useEmployerStore = defineStore('employer', {
  state: () => ({
    list: [],
    current: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 15,
      total: 0,
    },
    filters: {
      search: '',
      company_type: '',
      industry_sector: '',
      survey_status: '',
      address_city: '',
      sort_by: 'created_at',
      sort_dir: 'desc',
    },
    loading: false,
    loadingDetail: false,
    loadingToken: false,
    error: null,
  }),

  getters: {
    totalPages: (state) => state.pagination.lastPage,
    hasFilters: (state) =>
      !!(state.filters.search || state.filters.company_type ||
         state.filters.industry_sector || state.filters.survey_status ||
         state.filters.address_city),
  },

  actions: {
    // ─── Fetch list ──────────────────────────────────────────────────────────
    async fetchList(page = 1) {
      this.loading = true
      this.error = null
      try {
        const params = {
          ...this.filters,
          page,
          per_page: this.pagination.perPage,
        }
        const { data } = await api.get('/admin/employers', { params })
        this.list = data.data
        this.pagination = {
          currentPage: data.meta.current_page,
          lastPage:    data.meta.last_page,
          perPage:     data.meta.per_page,
          total:       data.meta.total,
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data employer.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchById(id) {
      this.loadingDetail = true
      this.error = null
      try {
        const { data } = await api.get(`/admin/employers/${id}`)
        this.current = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat detail employer.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    // ─── CRUD ─────────────────────────────────────────────────────────────────
    async create(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/admin/employers', payload)
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menambah employer.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async update(id, payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.put(`/admin/employers/${id}`, payload)
        if (this.current?.id === id) this.current = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui employer.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async destroy(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/admin/employers/${id}`)
        this.list = this.list.filter((e) => e.id !== id)
        if (this.current?.id === id) this.current = null
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus employer.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ─── Token ─────────────────────────────────────────────────────────────────
    async sendSurveyToken(id, channel) {
      this.loadingToken = true
      this.error = null
      try {
        const { data } = await api.post(`/admin/employers/${id}/send-survey-token`, { channel })
        if (this.current?.id === id) {
          this.current.survey_status           = data.data.survey_status
          this.current.survey_token_expires_at = data.data.survey_token_expires_at
        }
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengirim token survei.'
        throw err
      } finally {
        this.loadingToken = false
      }
    },

    async regenerateToken(id) {
      this.loadingToken = true
      this.error = null
      try {
        const { data } = await api.post(`/admin/employers/${id}/regenerate-token`)
        if (this.current?.id === id) {
          this.current.survey_status           = data.data.survey_status
          this.current.survey_token_expires_at = data.data.survey_token_expires_at
        }
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal regenerate token.'
        throw err
      } finally {
        this.loadingToken = false
      }
    },

    // ─── Filter & Reset ───────────────────────────────────────────────────────
    setFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
    },

    resetFilters() {
      this.filters = {
        search: '',
        company_type: '',
        industry_sector: '',
        survey_status: '',
        address_city: '',
        sort_by: 'created_at',
        sort_dir: 'desc',
      }
    },

    clearCurrent() {
      this.current = null
    },
  },
})
