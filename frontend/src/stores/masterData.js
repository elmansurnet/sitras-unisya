import { defineStore } from 'pinia'
import api from '@/services/api'

/**
 * Store untuk Master Data Akademik:
 * - Faculties (GET/POST/PUT/DELETE /admin/faculties)
 * - Study Programs (GET/POST/PUT/DELETE /admin/study-programs)
 * - Graduation Years (GET/POST/PUT/DELETE /admin/graduation-years)
 * - Public endpoints (GET /public/faculties, /public/study-programs, /public/graduation-years)
 */
export const useMasterDataStore = defineStore('masterData', {
  state: () => ({
    // ─── Faculties ────────────────────────────────────────────────────────────
    /** @type {Array<{id:number,name:string,code:string}>} */
    faculties: [],
    loadingFaculties: false,

    // ─── Study Programs ───────────────────────────────────────────────────────
    /** @type {Array<{id:number,name:string,code:string,degree_level:string,faculty_id:number,faculty:object}>} */
    studyPrograms: [],
    studyProgramFilters: { faculty_id: null },
    loadingStudyPrograms: false,

    // ─── Graduation Years ─────────────────────────────────────────────────────
    /** @type {Array<{id:number,year:number,alumni_count:number}>} */
    graduationYears: [],
    loadingGraduationYears: false,

    // ─── Public (untuk form alumni/survey — tidak perlu auth) ─────────────────
    /** @type {Array<{id:number,name:string,code:string}>} */
    publicFaculties: [],
    /** @type {Array<{id:number,name:string,code:string,degree_level:string,faculty_id:number}>} */
    publicStudyPrograms: [],
    /** @type {Array<{id:number,year:number}>} */
    publicGraduationYears: [],
    loadingPublic: false,

    saving: false,
    error: null,
  }),

  getters: {
    // Study programs difilter by faculty untuk keperluan form
    studyProgramsByFaculty: (state) => (facultyId) =>
      state.studyPrograms.filter((sp) => sp.faculty_id === facultyId),

    // Opsi select untuk form (value/label format)
    facultyOptions: (state) =>
      state.faculties.map((f) => ({ value: f.id, label: `${f.code} — ${f.name}` })),

    studyProgramOptions: (state) =>
      state.studyPrograms.map((sp) => ({ value: sp.id, label: `${sp.code} — ${sp.name}` })),

    graduationYearOptions: (state) =>
      state.graduationYears
        .slice()
        .sort((a, b) => b.year - a.year)
        .map((gy) => ({ value: gy.id, label: String(gy.year) })),
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

    // ═══ PUBLIC (no-auth) ════════════════════════════════════════════════════

    /** Fetch semua data publik sekaligus (untuk form alumni/survei) */
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
