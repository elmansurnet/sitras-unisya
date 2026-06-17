import { defineStore } from 'pinia'
import api from '@/services/api'

export const useMasterDataStore = defineStore('masterData', {
  state: () => ({
    faculties: [],
    loadingFaculties: false,

    studyPrograms: [],
    studyProgramFilters: { faculty_id: null },
    loadingStudyPrograms: false,

    graduationYears: [],
    loadingGraduationYears: false,

    industrySectors: [],
    loadingIndustrySectors: false,

    publicFaculties: [],
    publicStudyPrograms: [],
    publicGraduationYears: [],
    loadingPublic: false,

    saving: false,
    error: null,
  }),

  getters: {
    studyProgramsByFaculty: (state) => (facultyId) =>
      state.studyPrograms.filter((sp) => sp.faculty_id === facultyId),

    facultyOptions: (state) =>
      state.faculties.map((f) => ({ value: f.id, label: `${f.code} — ${f.name}` })),

    studyProgramOptions: (state) =>
      state.studyPrograms.map((sp) => ({ value: sp.id, label: `${sp.code} — ${sp.name}` })),

    graduationYearOptions: (state) =>
      state.graduationYears
        .slice()
        .sort((a, b) => b.year - a.year)
        .map((gy) => ({ value: gy.id, label: String(gy.year) })),

    // industrySectorOptions: bind ke s.name karena employers.industry_sector = VARCHAR
    industrySectorOptions: (state) =>
      state.industrySectors.map((s) => ({ value: s.name, label: s.name })),
  },

  actions: {
    // ═══ FACULTIES ═══════════════════════════════════════════════════════════

    async fetchFaculties() {
      this.loadingFaculties = true
      this.error = null
      try {
        const { data } = await api.get('/admin/faculties')
        this.faculties = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat fakultas'
      } finally {
        this.loadingFaculties = false
      }
    },

    async createFaculty(payload) {
      return this._mutate('post', '/admin/faculties', payload, this.fetchFaculties)
    },

    async updateFaculty(id, payload) {
      return this._mutate('put', `/admin/faculties/${id}`, payload, this.fetchFaculties)
    },

    async deleteFaculty(id) {
      return this._mutate('delete', `/admin/faculties/${id}`, null, this.fetchFaculties)
    },

    // ═══ STUDY PROGRAMS ══════════════════════════════════════════════════════

    async fetchStudyPrograms(facultyId = null) {
      this.loadingStudyPrograms = true
      this.error = null
      try {
        const params = facultyId ? { faculty_id: facultyId } : {}
        const { data } = await api.get('/admin/study-programs', { params })
        this.studyPrograms = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat program studi'
      } finally {
        this.loadingStudyPrograms = false
      }
    },

    async createStudyProgram(payload) {
      return this._mutate('post', '/admin/study-programs', payload, this.fetchStudyPrograms)
    },

    async updateStudyProgram(id, payload) {
      return this._mutate('put', `/admin/study-programs/${id}`, payload, this.fetchStudyPrograms)
    },

    async deleteStudyProgram(id) {
      return this._mutate('delete', `/admin/study-programs/${id}`, null, this.fetchStudyPrograms)
    },

    // ═══ GRADUATION YEARS ════════════════════════════════════════════════════

    async fetchGraduationYears() {
      this.loadingGraduationYears = true
      this.error = null
      try {
        const { data } = await api.get('/admin/graduation-years')
        this.graduationYears = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat tahun kelulusan'
      } finally {
        this.loadingGraduationYears = false
      }
    },

    async createGraduationYear(payload) {
      return this._mutate('post', '/admin/graduation-years', payload, this.fetchGraduationYears)
    },

    async updateGraduationYear(id, payload) {
      return this._mutate('put', `/admin/graduation-years/${id}`, payload, this.fetchGraduationYears)
    },

    async deleteGraduationYear(id) {
      return this._mutate('delete', `/admin/graduation-years/${id}`, null, this.fetchGraduationYears)
    },

    // ═══ INDUSTRY SECTORS ════════════════════════════════════════════════════
    // Endpoint: GET /api/v1/admin/industry-sectors?status=1
    // Hanya ambil sektor aktif (is_active=1) untuk keperluan dropdown

    async fetchIndustrySectors() {
      if (this.industrySectors.length > 0) return // cache sederhana
      this.loadingIndustrySectors = true
      this.error = null
      try {
        const { data } = await api.get('/admin/industry-sectors', { params: { status: 1 } })
        this.industrySectors = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat sektor industri'
      } finally {
        this.loadingIndustrySectors = false
      }
    },

    async createIndustrySector(payload) {
      return this._mutate('post', '/admin/industry-sectors', payload, () => {
        this.industrySectors = [] // reset cache agar refetch
        return this.fetchIndustrySectors()
      })
    },

    async updateIndustrySector(id, payload) {
      return this._mutate('put', `/admin/industry-sectors/${id}`, payload, () => {
        this.industrySectors = []
        return this.fetchIndustrySectors()
      })
    },

    async deleteIndustrySector(id) {
      return this._mutate('delete', `/admin/industry-sectors/${id}`, null, () => {
        this.industrySectors = []
        return this.fetchIndustrySectors()
      })
    },

    // ═══ PUBLIC (no-auth) ════════════════════════════════════════════════════

    async fetchPublicAll() {
      this.loadingPublic = true
      this.error = null
      try {
        const [fRes, spRes, gyRes] = await Promise.all([
          api.get('/public/faculties'),
          api.get('/public/study-programs'),
          api.get('/public/graduation-years'),
        ])
        this.publicFaculties = fRes.data.data ?? []
        this.publicStudyPrograms = spRes.data.data ?? []
        this.publicGraduationYears = gyRes.data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat data referensi'
      } finally {
        this.loadingPublic = false
      }
    },

    // ═══ INTERNAL HELPER ═════════════════════════════════════════════════════

    async _mutate(method, url, payload, refetch) {
      this.saving = true
      this.error = null
      try {
        const { data } = payload !== null
          ? await api[method](url, payload)
          : await api[method](url)
        await refetch.call(this)
        return { success: true, data: data?.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Terjadi kesalahan'
        this.error = msg
        return { success: false, error: msg, errors: err.response?.data?.errors }
      } finally {
        this.saving = false
      }
    },
  },
})
