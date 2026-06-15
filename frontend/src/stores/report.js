import { defineStore } from 'pinia'
import api from '@/services/api'

export const useReportStore = defineStore('report', {
  state: () => ({
    /** @type {Array<{id:number,name:string,format:string,file_size:number,created_at:string,download_url:string}>} */
    reports: [],
    loading: false,
    isGenerating: false,
    error: null,
    generatedFileName: null,

    // Filter state untuk form generate
    filters: {
      period_id: null,
      graduation_year_id: null,
      study_program_id: null,
    },
  }),

  getters: {
    hasReports: (state) => state.reports.length > 0,
  },

  actions: {
    // ─── Fetch daftar laporan tersimpan ───────────────────────────────────────
    async fetchReports() {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.get('/admin/reports')
        this.reports = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat laporan'
      } finally {
        this.loading = false
      }
    },

    // ─── Generate PDF ─────────────────────────────────────────────────────────
    async generatePdf(params = {}) {
      return this._generate('pdf', params)
    },

    // ─── Generate Excel ───────────────────────────────────────────────────────
    async generateExcel(params = {}) {
      return this._generate('excel', params)
    },

    // ─── Internal: generate + auto-download blob ─────────────────────────────
    async _generate(format, params = {}) {
      this.isGenerating = true
      this.error = null
      this.generatedFileName = null
      try {
        const endpoint = format === 'pdf'
          ? '/admin/reports/generate-pdf'
          : '/admin/reports/generate-excel'

        const response = await api.post(endpoint, {
          period_id: this.filters.period_id,
          graduation_year_id: this.filters.graduation_year_id,
          study_program_id: this.filters.study_program_id,
          ...params,
        }, { responseType: 'blob' })

        // Baca nama file dari Content-Disposition header
        const disposition = response.headers['content-disposition'] ?? ''
        const match = disposition.match(/filename="?([^"]+)"?/)
        const fileName = match?.[1] ?? `laporan-tracer-study.${format}`
        this.generatedFileName = fileName

        // Trigger download
        this._triggerBlobDownload(response.data, fileName)

        // Refresh daftar laporan
        await this.fetchReports()

        return { success: true, fileName }
      } catch (err) {
        const msg = err.response?.data?.message ?? `Gagal generate laporan ${format.toUpperCase()}`
        this.error = msg
        return { success: false, error: msg }
      } finally {
        this.isGenerating = false
      }
    },

    // ─── Download laporan by ID ───────────────────────────────────────────────
    async downloadReport(reportId) {
      try {
        const response = await api.get(`/admin/reports/${reportId}/download`, {
          responseType: 'blob',
        })

        const disposition = response.headers['content-disposition'] ?? ''
        const match = disposition.match(/filename="?([^"]+)"?/)
        const fileName = match?.[1] ?? `laporan-${reportId}`

        this._triggerBlobDownload(response.data, fileName)
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mengunduh laporan'
        this.error = msg
        return { success: false, error: msg }
      }
    },

    // ─── Reset filters ────────────────────────────────────────────────────────
    resetFilters() {
      this.filters.period_id = null
      this.filters.graduation_year_id = null
      this.filters.study_program_id = null
    },

    // ─── Helper: blob → download ──────────────────────────────────────────────
    _triggerBlobDownload(blobData, fileName) {
      const url = URL.createObjectURL(new Blob([blobData]))
      const a = document.createElement('a')
      a.href = url
      a.download = fileName
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    },
  },
})
