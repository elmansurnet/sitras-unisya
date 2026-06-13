/**
 * stores/dashboard.js — Pinia Dashboard Store (Sesi 5B Task 5B.3)
 *
 * State  : summary, employmentStats, mapData, trendData, reports, loading, error
 * Actions:
 *   fetchSummary()          → GET /api/v1/admin/dashboard/summary
 *   fetchEmploymentStats()  → GET /api/v1/admin/dashboard/employment-stats
 *   fetchAlumniMap()        → GET /api/v1/admin/dashboard/alumni-map
 *   fetchReports()          → GET /api/v1/admin/reports
 *   generateReport()        → POST /api/v1/admin/reports/generate-pdf|excel
 *   downloadReport()        → GET /api/v1/admin/reports/:id/download
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------

  /** Ringkasan utama dashboard (7.1) */
  const summary = ref({
    total_alumni: 0,
    total_employers: 0,
    active_survey_period: null,   // { id, name, response_rate, responses_completed, responses_pending, end_date }
    employment_stats: {
      employed: 0,
      self_employed: 0,
      continuing_study: 0,
      not_working: 0,
    },
    recent_activities: [],        // [{ action, description, created_at }]
  })

  /** Statistik ketenagakerjaan detail (7.2) */
  const employmentStats = ref({
    employment_rate: 0,
    average_waiting_months: 0,
    relevance_rate: 0,
    by_industry: [],              // [{ sector, count, percentage }]
    by_salary_range: [],          // [{ range, count, percentage }]
    by_graduation_year: [],       // [{ year, academic_year, employed, total, rate }]
    by_study_program: [],         // [{ id, name, employed, total, rate }]
  })

  /** Data peta sebaran alumni (7.3) */
  const mapData = ref([])         // [{ province, city, count, coordinates: { lat, lng } }]

  /**
   * Tren respons 12 bulan terakhir — dihitung dari data lokal setelah
   * fetchSummary + fetchEmploymentStats; bisa juga di-override jika ada endpoint.
   * Format: [{ month: 'Jan 2024', count: 42 }, ...]
   */
  const trendData = ref([])

  /** Daftar laporan tersimpan (8.3) */
  const reports = ref([])         // [{ id, type, format, filename, file_size_kb, generated_by, created_at, download_url }]

  /** Filter aktif untuk statistik (dipakai StatisticsPage) */
  const filters = ref({
    period_id: null,
    graduation_year_id: null,
    study_program_id: null,
  })

  /** State loading per-action */
  const loading = ref({
    summary: false,
    employmentStats: false,
    mapData: false,
    reports: false,
    generating: false,
  })

  /** Pesan error per-action */
  const error = ref({
    summary: null,
    employmentStats: null,
    mapData: null,
    reports: null,
    generating: null,
  })

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------

  /** Response rate periode aktif (0–100) */
  const responseRate = computed(() =>
    summary.value.active_survey_period?.response_rate ?? 0
  )

  /** Total alumni bekerja (employed + self_employed) */
  const totalWorking = computed(() => {
    const s = summary.value.employment_stats
    return s.employed + s.self_employed
  })

  /** Top 5 industri berdasarkan count */
  const topIndustries = computed(() =>
    [...employmentStats.value.by_industry]
      .sort((a, b) => b.count - a.count)
      .slice(0, 10)
  )

  /** Data donut: [employed, self_employed, continuing_study, not_working] */
  const donutSeries = computed(() => {
    const s = summary.value.employment_stats
    return [s.employed, s.self_employed, s.continuing_study, s.not_working]
  })

  const isAnyLoading = computed(() =>
    Object.values(loading.value).some(Boolean)
  )

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  /**
   * 7.1 — Ringkasan Dashboard
   * GET /api/v1/admin/dashboard/summary
   */
  async function fetchSummary() {
    loading.value.summary = true
    error.value.summary   = null
    try {
      const { data } = await api.get('/admin/dashboard/summary')
      summary.value = data.data
      _buildTrendFromActivities()
      return data.data
    } catch (err) {
      error.value.summary = err.response?.data?.message ?? 'Gagal memuat ringkasan dashboard.'
      throw err
    } finally {
      loading.value.summary = false
    }
  }

  /**
   * 7.2 — Statistik Ketenagakerjaan
   * GET /api/v1/admin/dashboard/employment-stats
   * @param {Object} overrideFilters - opsional override filter { period_id, graduation_year_id, study_program_id }
   */
  async function fetchEmploymentStats(overrideFilters = null) {
    loading.value.employmentStats = true
    error.value.employmentStats   = null
    const params = _buildFilterParams(overrideFilters)
    try {
      const { data } = await api.get('/admin/dashboard/employment-stats', { params })
      employmentStats.value = data.data
      return data.data
    } catch (err) {
      error.value.employmentStats = err.response?.data?.message ?? 'Gagal memuat statistik ketenagakerjaan.'
      throw err
    } finally {
      loading.value.employmentStats = false
    }
  }

  /**
   * 7.3 — Data Peta Sebaran Alumni
   * GET /api/v1/admin/dashboard/alumni-map
   * @param {Object} overrideFilters - opsional override filter
   */
  async function fetchAlumniMap(overrideFilters = null) {
    loading.value.mapData = true
    error.value.mapData   = null
    const params = _buildFilterParams(overrideFilters)
    try {
      const { data } = await api.get('/admin/dashboard/alumni-map', { params })
      mapData.value = data.data
      return data.data
    } catch (err) {
      error.value.mapData = err.response?.data?.message ?? 'Gagal memuat data peta alumni.'
      throw err
    } finally {
      loading.value.mapData = false
    }
  }

  /**
   * Fetch semua data dashboard sekaligus (untuk DashboardPage initial load)
   */
  async function fetchAll() {
    await Promise.allSettled([
      fetchSummary(),
      fetchEmploymentStats(),
      fetchAlumniMap(),
    ])
  }

  /**
   * Fetch semua data statistik dengan filter (untuk StatisticsPage)
   * @param {Object} newFilters
   */
  async function fetchStatistics(newFilters = {}) {
    if (Object.keys(newFilters).length > 0) {
      filters.value = { ...filters.value, ...newFilters }
    }
    await Promise.allSettled([
      fetchEmploymentStats(filters.value),
      fetchAlumniMap(filters.value),
    ])
  }

  /**
   * 8.3 — Daftar Laporan Tersimpan
   * GET /api/v1/admin/reports
   */
  async function fetchReports() {
    loading.value.reports = true
    error.value.reports   = null
    try {
      const { data } = await api.get('/admin/reports')
      reports.value = data.data
      return data.data
    } catch (err) {
      error.value.reports = err.response?.data?.message ?? 'Gagal memuat daftar laporan.'
      throw err
    } finally {
      loading.value.reports = false
    }
  }

  /**
   * 8.1/8.2 — Generate Laporan PDF atau Excel
   * POST /api/v1/admin/reports/generate-pdf  atau  /generate-excel
   * Response: file blob — langsung trigger download
   *
   * @param {Object} payload  { type, period_id, study_program_id, graduation_year_id, format: 'pdf'|'excel' }
   * @returns {{ filename: string }} nama file yang di-download
   */
  async function generateReport(payload) {
    loading.value.generating = true
    error.value.generating   = null
    const endpoint = payload.format === 'excel'
      ? '/admin/reports/generate-excel'
      : '/admin/reports/generate-pdf'
    try {
      const response = await api.post(endpoint, {
        type:                payload.type ?? 'tracerstudy',
        period_id:           payload.period_id ?? null,
        study_program_id:    payload.study_program_id ?? null,
        graduation_year_id:  payload.graduation_year_id ?? null,
      }, {
        responseType: 'blob',
      })

      const contentDisposition = response.headers['content-disposition'] ?? ''
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=(['"]?)([^'";\n]*)\1/)
      const filename = filenameMatch
        ? decodeURIComponent(filenameMatch[2])
        : `laporan-tracer-study.${payload.format === 'excel' ? 'xlsx' : 'pdf'}`

      _triggerBlobDownload(response.data, filename)

      // Refresh daftar laporan setelah generate
      await fetchReports()

      return { filename }
    } catch (err) {
      // Blob error response perlu di-parse manual
      if (err.response?.data instanceof Blob) {
        const text  = await err.response.data.text()
        const parsed = JSON.parse(text)
        error.value.generating = parsed?.message ?? 'Gagal generate laporan.'
      } else {
        error.value.generating = err.response?.data?.message ?? 'Gagal generate laporan.'
      }
      throw err
    } finally {
      loading.value.generating = false
    }
  }

  /**
   * 8.4 — Download Laporan Tersimpan
   * GET /api/v1/admin/reports/:id/download
   * @param {number} reportId
   * @param {string} filename  - nama file untuk disimpan
   */
  async function downloadReport(reportId, filename) {
    try {
      const response = await api.get(`/admin/reports/${reportId}/download`, {
        responseType: 'blob',
      })
      const resolvedFilename = filename || `laporan-${reportId}.pdf`
      _triggerBlobDownload(response.data, resolvedFilename)
    } catch (err) {
      throw err
    }
  }

  /**
   * Reset semua filter ke default
   */
  function resetFilters() {
    filters.value = {
      period_id: null,
      graduation_year_id: null,
      study_program_id: null,
    }
  }

  // ---------------------------------------------------------------------------
  // Helpers (private)
  // ---------------------------------------------------------------------------

  /**
   * Bangun tren bulanan dari recent_activities sebagai fallback sederhana.
   * Jika backend menyediakan endpoint tren terpisah, action ini bisa diganti.
   */
  function _buildTrendFromActivities() {
    const activities = summary.value.recent_activities ?? []
    const monthMap   = {}

    activities.forEach(({ created_at }) => {
      if (!created_at) return
      const d   = new Date(created_at)
      const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
      monthMap[key] = (monthMap[key] ?? 0) + 1
    })

    trendData.value = Object.entries(monthMap)
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([ym, count]) => {
        const [year, month] = ym.split('-')
        const label = new Date(Number(year), Number(month) - 1, 1)
          .toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })
        return { month: label, count }
      })
  }

  /**
   * Bangun query params dari filters.value atau override
   * Hanya sertakan key yang tidak null
   */
  function _buildFilterParams(override = null) {
    const source = override ?? filters.value
    return Object.fromEntries(
      Object.entries(source).filter(([, v]) => v !== null && v !== undefined)
    )
  }

  /**
   * Trigger download file Blob di browser
   */
  function _triggerBlobDownload(blob, filename) {
    const url  = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href     = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  }

  // ---------------------------------------------------------------------------
  // Expose
  // ---------------------------------------------------------------------------
  return {
    // state
    summary,
    employmentStats,
    mapData,
    trendData,
    reports,
    filters,
    loading,
    error,
    // getters
    responseRate,
    totalWorking,
    topIndustries,
    donutSeries,
    isAnyLoading,
    // actions
    fetchSummary,
    fetchEmploymentStats,
    fetchAlumniMap,
    fetchAll,
    fetchStatistics,
    fetchReports,
    generateReport,
    downloadReport,
    resetFilters,
  }
})
