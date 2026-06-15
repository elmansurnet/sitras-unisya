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
    async fetchMasterData() {
      try {
        const [studyProgramsRes, graduationYearsRes, salaryRangesRes] = await Promise.all([
          api.get('/public/study-programs'),
          api.get('/public/graduation-years'),
          api.get('/public/salary-ranges'),
        ])
        const studyPrograms = studyProgramsRes.data?.data ?? []
        const graduationYears = graduationYearsRes.data?.data ?? []
        const salaryRanges = salaryRangesRes.data?.data ?? []
        this.studyProgramOptions = Array.isArray(studyPrograms) ? studyPrograms.map((item) => ({ value: item.id, label: item.name ?? item.program_name ?? `Prodi #${item.id}` })) : []
        this.graduationYearOptions = Array.isArray(graduationYears) ? graduationYears.map((item) => ({ value: item.id, label: item.year ?? item.label ?? String(item.id) })) : []
        this.salaryRangeOptions = Array.isArray(salaryRanges) ? salaryRanges.map((item) => ({ value: item.id, label: item.label ?? item.name ?? `Range #${item.id}` })) : []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat master data alumni.'
        throw err
      }
    },
    async fetchDetail(id) { this.loadingDetail = true; try { const { data } = await api.get(`/admin/alumni/${id}`); this.current = data.data; return data.data } finally { this.loadingDetail = false } },
    async create(payload) { this.loadingSubmit = true; try { const { data } = await api.post('/admin/alumni', payload); return data.data } finally { this.loadingSubmit = false } },
    async update(id, payload) { this.loadingSubmit = true; try { const { data } = await api.put(`/admin/alumni/${id}`, payload); this.current = data.data; return data.data } finally { this.loadingSubmit = false } },
    async importAlumni(file) { this.loadingImport = true; try { const fd = file instanceof FormData ? file : (() => { const x = new FormData(); x.append('file', file); return x })(); const { data } = await api.post('/admin/alumni/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } }); this.importResult = data.data; return data.data } finally { this.loadingImport = false } },
    async downloadTemplate() {
      const response = await api.get('/admin/alumni/template', { responseType: 'blob' })
      const blob = new Blob([response.data])
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'template_import_alumni.xlsx'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },
  },
})
