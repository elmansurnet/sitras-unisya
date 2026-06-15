import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAlumniStore = defineStore('alumni', {
  state: () => ({
    list: [],
    current: null,
    meta: { current_page: 1, per_page: 15, total: 0, last_page: 1, from: null, to: null },
    filters: { search: '', study_program_id: null, graduation_year_id: null, survey_status: null, gender: null, sort_by: 'created_at', sort_dir: 'desc' },
    studyProgramOptions: [],
    graduationYearOptions: [],
    salaryRangeOptions: [],
    loading: false,
    loadingDetail: false,
    loadingSubmit: false,
    loadingImport: false,
    loadingExport: false,
    importResult: null,
    error: null,
  }),
  actions: {
    // ── Master Data ─────────────────────────────────────────────────────────
    async fetchMasterData() {
      try {
        const [studyProgramsRes, graduationYearsRes, salaryRangesRes] = await Promise.all([
          api.get('/public/study-programs'),
          api.get('/public/graduation-years'),
          api.get('/public/salary-ranges'),
        ])
        const sp = studyProgramsRes.data?.data ?? []
        const gy = graduationYearsRes.data?.data ?? []
        const sr = salaryRangesRes.data?.data ?? []
        this.studyProgramOptions   = Array.isArray(sp) ? sp.map((i) => ({ value: i.id, label: i.name ?? i.program_name ?? `Prodi #${i.id}` })) : []
        this.graduationYearOptions = Array.isArray(gy) ? gy.map((i) => ({ value: i.id, label: i.year ?? i.label ?? String(i.id) })) : []
        this.salaryRangeOptions    = Array.isArray(sr) ? sr.map((i) => ({ value: i.id, label: i.label ?? i.name ?? `Range #${i.id}` })) : []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat master data alumni.'
        throw err
      }
    },

    // ── List + Pagination ────────────────────────────────────────────────────
    async fetchAlumni(page = 1) {
      this.loading = true
      this.error   = null
      try {
        const params = { page, per_page: this.meta.per_page }
        if (this.filters.search)             params.search             = this.filters.search
        if (this.filters.study_program_id)   params.study_program_id   = this.filters.study_program_id
        if (this.filters.graduation_year_id) params.graduation_year_id = this.filters.graduation_year_id
        if (this.filters.survey_status)      params.survey_status      = this.filters.survey_status
        if (this.filters.gender)             params.gender             = this.filters.gender
        if (this.filters.sort_by)            params.sort_by            = this.filters.sort_by
        if (this.filters.sort_dir)           params.sort_dir           = this.filters.sort_dir
        const { data } = await api.get('/admin/alumni', { params })
        this.list = data.data ?? []
        if (data.meta) {
          this.meta = {
            current_page : data.meta.current_page,
            per_page     : data.meta.per_page,
            total        : data.meta.total,
            last_page    : data.meta.last_page,
            from         : data.meta.from ?? null,
            to           : data.meta.to   ?? null,
          }
        }
        return data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data alumni.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // Alias untuk kompatibilitas kode lama jika ada
    async fetchList(page = 1) { return this.fetchAlumni(page) },

    setFilter(key, value) {
      if (key in this.filters) this.filters[key] = value
    },

    resetFilters() {
      this.filters = { search: '', study_program_id: null, graduation_year_id: null, survey_status: null, gender: null, sort_by: 'created_at', sort_dir: 'desc' }
    },

    // ── Detail ───────────────────────────────────────────────────────────────
    async fetchDetail(id) {
      this.loadingDetail = true
      try {
        const { data } = await api.get(`/admin/alumni/${id}`)
        this.current = data.data
        return data.data
      } finally {
        this.loadingDetail = false
      }
    },

    // ── Create / Update / Delete ─────────────────────────────────────────────
    async create(payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.post('/admin/alumni', payload)
        return data.data
      } finally { this.loadingSubmit = false }
    },

    async update(id, payload) {
      this.loadingSubmit = true
      try {
        const { data } = await api.put(`/admin/alumni/${id}`, payload)
        this.current = data.data
        return data.data
      } finally { this.loadingSubmit = false }
    },

    async remove(id) {
      const { data } = await api.delete(`/admin/alumni/${id}`)
      this.list = this.list.filter((a) => a.id !== id)
      return data
    },

    // ── Send Invitation ──────────────────────────────────────────────────────
    async sendInvitation(id) {
      const { data } = await api.post(`/admin/alumni/${id}/send-invitation`)
      return data
    },

    // ── Import / Export ──────────────────────────────────────────────────────
    async importAlumni(file) {
      this.loadingImport = true
      try {
        const fd = file instanceof FormData ? file : (() => { const x = new FormData(); x.append('file', file); return x })()
        const { data } = await api.post('/admin/alumni/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        this.importResult = data.data
        return data.data
      } finally { this.loadingImport = false }
    },

    async exportAlumni() {
      this.loadingExport = true
      try {
        const params = {}
        if (this.filters.search)             params.search             = this.filters.search
        if (this.filters.study_program_id)   params.study_program_id   = this.filters.study_program_id
        if (this.filters.graduation_year_id) params.graduation_year_id = this.filters.graduation_year_id
        if (this.filters.survey_status)      params.survey_status      = this.filters.survey_status
        const response = await api.get('/admin/alumni/export', { params, responseType: 'blob' })
        const blob = new Blob([response.data])
        const url  = window.URL.createObjectURL(blob)
        const a    = document.createElement('a')
        a.href     = url
        a.download = 'alumni_export.xlsx'
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
      } finally { this.loadingExport = false }
    },

    async downloadTemplate() {
      const response = await api.get('/admin/alumni/template', { responseType: 'blob' })
      const blob = new Blob([response.data])
      const url  = window.URL.createObjectURL(blob)
      const a    = document.createElement('a')
      a.href     = url
      a.download = 'template_import_alumni.xlsx'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
    },
  },
})
