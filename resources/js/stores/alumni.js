/**
 * stores/alumni.js — Pinia Store Alumni
 * Task 2A.15 | Sesuai 05_API.md §3 & 06_UI_UX.md
 */
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAlumniStore = defineStore('alumni', {
  state: () => ({
    // List
    items: [],
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
    },
    filters: {
      search: '',
      faculty_id: null,
      study_program_id: null,
      graduation_year_id: null,
      survey_status: null,
      gender: null,
    },

    // Detail / current
    current: null,

    // Work histories
    workHistories: [],

    // UI state
    loading: false,
    loadingDetail: false,
    loadingWorkHistory: false,
    loadingImport: false,
    loadingExport: false,

    // Import result
    importResult: null,

    // Error
    error: null,
  }),

  getters: {
    hasItems: (state) => state.items.length > 0,
    totalPages: (state) => state.pagination.last_page,
    currentPage: (state) => state.pagination.current_page,
  },

  actions: {
    // ── LIST ────────────────────────────────────────────────────────────────
    async fetchList(page = 1) {
      this.loading = true
      this.error   = null
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...Object.fromEntries(
            Object.entries(this.filters).filter(([, v]) => v !== null && v !== '')
          ),
        }
        const { data } = await api.get('/admin/alumni', { params })
        this.items      = data.data.data
        this.pagination = {
          current_page: data.data.current_page,
          last_page:    data.data.last_page,
          per_page:     data.data.per_page,
          total:        data.data.total,
          from:         data.data.from,
          to:           data.data.to,
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data alumni.'
      } finally {
        this.loading = false
      }
    },

    // ── DETAIL ──────────────────────────────────────────────────────────────
    async fetchDetail(id) {
      this.loadingDetail = true
      this.error         = null
      try {
        const { data } = await api.get(`/admin/alumni/${id}`)
        this.current = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat detail alumni.'
      } finally {
        this.loadingDetail = false
      }
    },

    // ── CREATE ──────────────────────────────────────────────────────────────
    async create(payload) {
      this.loading = true
      this.error   = null
      try {
        const { data } = await api.post('/admin/alumni', payload)
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menyimpan alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── UPDATE ──────────────────────────────────────────────────────────────
    async update(id, payload) {
      this.loading = true
      this.error   = null
      try {
        const { data } = await api.put(`/admin/alumni/${id}`, payload)
        this.current = data.data
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── DELETE ──────────────────────────────────────────────────────────────
    async remove(id) {
      this.loading = true
      this.error   = null
      try {
        const { data } = await api.delete(`/admin/alumni/${id}`)
        this.items = this.items.filter((a) => a.id !== id)
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── UPLOAD PHOTO (alumni self) ─────────────────────────────────────────
    async uploadPhoto(formData) {
      this.loading = true
      this.error   = null
      try {
        const { data } = await api.post('/alumni/profile/photo', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        if (this.current) this.current.photo_url = data.data.photo_url
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengunggah foto.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── IMPORT ──────────────────────────────────────────────────────────────
    async importExcel(formData) {
      this.loadingImport = true
      this.importResult  = null
      this.error         = null
      try {
        const { data } = await api.post('/admin/alumni/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.importResult = data.data
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengimpor alumni.'
        throw err
      } finally {
        this.loadingImport = false
      }
    },

    // ── DOWNLOAD TEMPLATE ───────────────────────────────────────────────────
    async downloadTemplate() {
      const { data } = await api.get('/admin/alumni/import/template', {
        responseType: 'blob',
      })
      return data
    },

    // ── EXPORT ──────────────────────────────────────────────────────────────
    async exportExcel() {
      this.loadingExport = true
      this.error         = null
      try {
        const params = Object.fromEntries(
          Object.entries(this.filters).filter(([, v]) => v !== null && v !== '')
        )
        const { data } = await api.get('/admin/alumni/export', {
          params,
          responseType: 'blob',
        })
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengekspor data.'
        throw err
      } finally {
        this.loadingExport = false
      }
    },

    // ── SEND INVITATION ─────────────────────────────────────────────────────
    async sendInvitation(id) {
      const { data } = await api.post(`/admin/alumni/${id}/send-invitation`)
      return data
    },

    // ── WORK HISTORIES ──────────────────────────────────────────────────────
    async fetchWorkHistories() {
      this.loadingWorkHistory = true
      this.error              = null
      try {
        const { data } = await api.get('/alumni/work-histories')
        this.workHistories = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat riwayat kerja.'
      } finally {
        this.loadingWorkHistory = false
      }
    },

    async addWorkHistory(payload) {
      const { data } = await api.post('/alumni/work-histories', payload)
      this.workHistories.push(data.data)
      return data
    },

    async updateWorkHistory(id, payload) {
      const { data } = await api.put(`/alumni/work-histories/${id}`, payload)
      const idx = this.workHistories.findIndex((w) => w.id === id)
      if (idx !== -1) this.workHistories[idx] = data.data
      return data
    },

    async deleteWorkHistory(id) {
      await api.delete(`/alumni/work-histories/${id}`)
      this.workHistories = this.workHistories.filter((w) => w.id !== id)
    },

    // ── SELF PROFILE (alumni role) ──────────────────────────────────────────
    async fetchProfile() {
      this.loadingDetail = true
      this.error         = null
      try {
        const { data } = await api.get('/alumni/profile')
        this.current = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat profil.'
      } finally {
        this.loadingDetail = false
      }
    },

    async updateProfile(payload) {
      this.loading = true
      this.error   = null
      try {
        const { data } = await api.put('/alumni/profile', payload)
        this.current = data.data
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui profil.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── FILTERS ─────────────────────────────────────────────────────────────
    setFilter(key, value) {
      this.filters[key] = value
    },

    resetFilters() {
      this.filters = {
        search: '',
        faculty_id: null,
        study_program_id: null,
        graduation_year_id: null,
        survey_status: null,
        gender: null,
      }
    },

    clearCurrent() {
      this.current = null
    },

    clearImportResult() {
      this.importResult = null
    },
  },
})
