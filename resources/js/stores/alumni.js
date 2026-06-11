import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAlumniStore = defineStore('alumni', {
  state: () => ({
    list: [],
    current: null,
    meta: {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
      from: null,
      to: null,
    },
    filters: {
      search: '',
      study_program_id: null,
      graduation_year_id: null,
      survey_status: null,
      gender: null,
      sort_by: 'created_at',
      sort_dir: 'desc',
    },
    loading: false,
    loadingDetail: false,
    loadingSubmit: false,
    loadingImport: false,
    loadingExport: false,
    importResult: null,
    error: null,
  }),

  getters: {
    hasFiltersApplied: (state) => {
      return (
        state.filters.search !== '' ||
        state.filters.study_program_id !== null ||
        state.filters.graduation_year_id !== null ||
        state.filters.survey_status !== null ||
        state.filters.gender !== null
      )
    },

    paginationInfo: (state) => {
      if (!state.meta.total) return 'Tidak ada data'
      return `Menampilkan ${state.meta.from}–${state.meta.to} dari ${state.meta.total} alumni`
    },
  },

  actions: {
    /**
     * Fetch paginated alumni list (admin)
     * @param {number} page
     */
    async fetchList(page = 1) {
      this.loading = true
      this.error = null
      try {
        const params = { page, ...this.filters }
        // strip null/empty values
        Object.keys(params).forEach((k) => {
          if (params[k] === null || params[k] === '') delete params[k]
        })
        const { data } = await api.get('/admin/alumni', { params })
        this.list = data.data
        this.meta = data.meta
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Fetch single alumni detail (admin)
     * @param {number} id
     */
    async fetchDetail(id) {
      this.loadingDetail = true
      this.error = null
      try {
        const { data } = await api.get(`/admin/alumni/${id}`)
        this.current = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat detail alumni.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    /**
     * Create alumni (admin)
     * @param {object} payload
     */
    async create(payload) {
      this.loadingSubmit = true
      this.error = null
      try {
        const { data } = await api.post('/admin/alumni', payload)
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menyimpan data alumni.'
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    /**
     * Update alumni (admin)
     * @param {number} id
     * @param {object} payload
     */
    async update(id, payload) {
      this.loadingSubmit = true
      this.error = null
      try {
        const { data } = await api.put(`/admin/alumni/${id}`, payload)
        // update current if viewing the same alumni
        if (this.current?.id === id) {
          this.current = data.data
        }
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui data alumni.'
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    /**
     * Soft-delete alumni (superadmin only)
     * @param {number} id
     */
    async remove(id) {
      this.loadingSubmit = true
      this.error = null
      try {
        await api.delete(`/admin/alumni/${id}`)
        this.list = this.list.filter((a) => a.id !== id)
        this.meta.total = Math.max(0, this.meta.total - 1)
        if (this.current?.id === id) this.current = null
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus data alumni.'
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    /**
     * Import alumni from Excel/CSV (admin)
     * @param {FormData} formData
     */
    async importAlumni(formData) {
      this.loadingImport = true
      this.importResult = null
      this.error = null
      try {
        const { data } = await api.post('/admin/alumni/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.importResult = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengimpor data alumni.'
        throw err
      } finally {
        this.loadingImport = false
      }
    },

    /**
     * Download import template
     */
    async downloadTemplate() {
      try {
        const response = await api.get('/admin/alumni/import-template', {
          responseType: 'blob',
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'template_import_alumni.xlsx')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        this.error = 'Gagal mengunduh template.'
        throw err
      }
    },

    /**
     * Export alumni list to Excel
     */
    async exportAlumni() {
      this.loadingExport = true
      try {
        const params = { ...this.filters }
        Object.keys(params).forEach((k) => {
          if (params[k] === null || params[k] === '') delete params[k]
        })
        const response = await api.get('/admin/alumni/export', {
          params,
          responseType: 'blob',
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `alumni_export_${Date.now()}.xlsx`)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (err) {
        this.error = 'Gagal mengekspor data alumni.'
        throw err
      } finally {
        this.loadingExport = false
      }
    },

    /**
     * Send invitation to alumni (admin)
     * @param {number} alumniId
     * @param {object} payload { survey_period_id, questionnaire_id, channel }
     */
    async sendInvitation(alumniId, payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.post(`/admin/alumni/${alumniId}/send-invitation`, payload)
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengirim undangan.'
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    // --- Alumni self-service ---

    /**
     * Fetch own profile (alumni role)
     */
    async fetchOwnProfile() {
      this.loadingDetail = true
      this.error = null
      try {
        const { data } = await api.get('/alumni/profile')
        this.current = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat profil.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    /**
     * Update own profile (alumni role)
     * @param {object|FormData} payload
     */
    async updateOwnProfile(payload) {
      this.loadingSubmit = true
      this.error = null
      try {
        const isFormData = payload instanceof FormData
        const { data } = await api.post('/alumni/profile', payload, {
          headers: isFormData ? { 'Content-Type': 'multipart/form-data' } : {},
        })
        this.current = data.data
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui profil.'
        throw err
      } finally {
        this.loadingSubmit = false
      }
    },

    // --- Helpers ---

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
        sort_by: 'created_at',
        sort_dir: 'desc',
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
