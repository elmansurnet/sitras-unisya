import { defineStore } from 'pinia'
import api from '@/services/api'

/**
 * Store untuk modul Alumni (sisi alumni sendiri):
 * - Profile (GET/PUT /alumni/profile, POST /alumni/profile/photo)
 * - Work Histories (GET/POST/PUT/DELETE /alumni/work-histories)
 *
 * Berbeda dari stores/alumni.js yang digunakan oleh admin.
 * Store ini HANYA untuk alumni yang sedang login.
 */
export const useAlumniProfileStore = defineStore('alumniProfile', {
  state: () => ({
    // ─── Profile ──────────────────────────────────────────────────────────────
    /**
     * @type {{
     *   id: number, nim: string, name: string, email: string, phone: string,
     *   birth_date: string, gender: string, address: string,
     *   study_program_id: number, graduation_year_id: number,
     *   gpa: number, graduation_date: string,
     *   photo_url: string|null, survey_status: string,
     *   study_program: object, graduation_year: object
     * }|null}
     */
    profile: null,
    loadingProfile: false,
    savingProfile: false,
    uploadingPhoto: false,

    // ─── Work Histories ───────────────────────────────────────────────────────
    /**
     * @type {Array<{
     *   id: number, company_name: string, position: string,
     *   employment_type: string, start_date: string, end_date: string|null,
     *   is_current: boolean, salary_range_id: number|null,
     *   industry_sector_id: number|null, city: string, province: string,
     *   is_relevant: boolean, employer_id: number|null,
     *   employer: object|null
     * }>}
     */
    workHistories: [],
    loadingWorkHistories: false,
    savingWorkHistory: false,

    error: null,
  }),

  getters: {
    currentJob: (state) =>
      state.workHistories.find((wh) => wh.is_current) ?? null,

    profileCompletion: (state) => {
      if (!state.profile) return 0
      const fields = [
        'name', 'email', 'phone', 'birth_date', 'gender',
        'address', 'study_program_id', 'graduation_year_id', 'gpa',
      ]
      const filled = fields.filter((f) => !!state.profile[f]).length
      return Math.round((filled / fields.length) * 100)
    },
  },

  actions: {
    // ═══ PROFILE ═════════════════════════════════════════════════════════════

    async fetchProfile() {
      this.loadingProfile = true
      this.error = null
      try {
        const { data } = await api.get('/alumni/profile')
        this.profile = data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat profil'
      } finally {
        this.loadingProfile = false
      }
    },

    /**
     * Update profil alumni (tanpa foto — foto via uploadPhoto).
     * @param {object} payload
     */
    async updateProfile(payload) {
      this.savingProfile = true
      this.error = null
      try {
        const { data } = await api.put('/alumni/profile', payload)
        this.profile = data.data
        return { success: true, data: data.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal memperbarui profil'
        this.error = msg
        return { success: false, error: msg, errors: err.response?.data?.errors }
      } finally {
        this.savingProfile = false
      }
    },

    /**
     * Upload foto profil.
     * @param {File} file
     */
    async uploadPhoto(file) {
      this.uploadingPhoto = true
      this.error = null
      try {
        const formData = new FormData()
        formData.append('photo', file)
        const { data } = await api.post('/alumni/profile/photo', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        // Update photo_url di state tanpa refetch penuh
        if (this.profile) this.profile.photo_url = data.data?.photo_url ?? null
        return { success: true, photoUrl: data.data?.photo_url }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mengunggah foto'
        this.error = msg
        return { success: false, error: msg }
      } finally {
        this.uploadingPhoto = false
      }
    },

    // ═══ WORK HISTORIES ══════════════════════════════════════════════════════

    async fetchWorkHistories() {
      this.loadingWorkHistories = true
      this.error = null
      try {
        const { data } = await api.get('/alumni/work-histories')
        this.workHistories = data.data ?? []
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat riwayat kerja'
      } finally {
        this.loadingWorkHistories = false
      }
    },

    async createWorkHistory(payload) {
      return this._mutateWorkHistory('post', '/alumni/work-histories', payload)
    },

    async updateWorkHistory(id, payload) {
      return this._mutateWorkHistory('put', `/alumni/work-histories/${id}`, payload)
    },

    async deleteWorkHistory(id) {
      this.savingWorkHistory = true
      this.error = null
      try {
        await api.delete(`/alumni/work-histories/${id}`)
        this.workHistories = this.workHistories.filter((wh) => wh.id !== id)
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal menghapus riwayat kerja'
        this.error = msg
        return { success: false, error: msg }
      } finally {
        this.savingWorkHistory = false
      }
    },

    // ─── Internal helper ─────────────────────────────────────────────────────
    async _mutateWorkHistory(method, url, payload) {
      this.savingWorkHistory = true
      this.error = null
      try {
        const { data } = await api[method](url, payload)
        await this.fetchWorkHistories()
        return { success: true, data: data.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal menyimpan riwayat kerja'
        this.error = msg
        return { success: false, error: msg, errors: err.response?.data?.errors }
      } finally {
        this.savingWorkHistory = false
      }
    },
  },
})
