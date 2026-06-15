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
    studyProgramOptions: [],
    graduationYearOptions: [],
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
    async fetchMasterData() {
      try {
        const [studyProgramsRes, graduationYearsRes] = await Promise.all([
          api.get('/admin/settings/study-programs', { params: { per_page: 100 } }),
          api.get('/admin/settings/graduation-years', { params: { per_page: 100 } }),
        ])

        const studyPrograms = studyProgramsRes.data?.data ?? studyProgramsRes.data ?? []
        const graduationYears = graduationYearsRes.data?.data ?? graduationYearsRes.data ?? []

        this.studyProgramOptions = Array.isArray(studyPrograms)
          ? studyPrograms.map((item) => ({
              value: item.id,
              label: item.name ?? item.program_name ?? `Prodi #${item.id}`,
            }))
          : []

        this.graduationYearOptions = Array.isArray(graduationYears)
          ? graduationYears.map((item) => ({
              value: item.id,
              label: item.year ?? item.label ?? String(item.id),
            }))
          : []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat master data alumni.'
        throw err
      }
    },

    async fetchList(page = 1) {
      this.loading = true
      this.error = null
      try {
        const params = { page, ...this.filters }
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

    async update(id, payload) {
      this.loadingSubmit = true
      this.error = null
      try {
        const { data } = await api.put(`/admin/alumni/${id}`, payload)
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

    async importAlumni(formData) {
      this.loadingImport = true
      this.importResult = null
      this.error = null
      try {
        const payload = formData instanceof FormData ? formData : (() => {
          const fd = new FormData()
          fd.append('file', formData)
          return fd
        })()
        const { data } = await api.post('/admin/alumni/import', payload, {
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
