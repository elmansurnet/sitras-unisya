import { defineStore } from 'pinia'
import api from '@/services/api'

export const useQuestionnaireStore = defineStore('questionnaire', {
  state: () => ({
    // ── Daftar kuesioner (index) ──────────────────────────────────────────────
    list: [],
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 15,
      total: 0,
    },
    filters: {
      search: '',
      type: '',        // alumni | employer
      status: '',      // draft | aktif | arsip
      sort_by: 'created_at',
      sort_dir: 'desc',
    },

    // ── Detail kuesioner + builder ────────────────────────────────────────────
    current: null,     // payload dari GET /admin/questionnaires/{id}
    sections: [],      // current.sections (disalin terpisah agar reaktif di builder)
    questions: [],     // flat list seluruh pertanyaan kuesioner aktif (untuk QuestionnaireBuilder)

    // ── Loading flags ─────────────────────────────────────────────────────────
    loading: false,
    loadingDetail: false,
    loadingSection: false,
    loadingQuestion: false,
    loadingPublish: false,

    // ── Error ─────────────────────────────────────────────────────────────────
    error: null,
  }),

  // ─── Getters ───────────────────────────────────────────────────────────────
  getters: {
    totalPages: (state) => state.pagination.lastPage,

    hasFilters: (state) =>
      !!(state.filters.search || state.filters.type || state.filters.status),

    isDraft: (state) => state.current?.status === 'draft',

    isPublished: (state) => state.current?.status === 'aktif',

    isArchived: (state) => state.current?.status === 'arsip',

    /**
     * Mengembalikan pertanyaan yang dikelompokkan per section_id.
     * Format: { [section_id]: Question[] }
     */
    questionsBySection: (state) => {
      return state.questions.reduce((acc, q) => {
        const sid = q.section_id
        if (!acc[sid]) acc[sid] = []
        acc[sid].push(q)
        return acc
      }, {})
    },

    totalQuestions: (state) => state.questions.length,
  },

  // ─── Actions ───────────────────────────────────────────────────────────────
  actions: {
    // ── Fetch list ─────────────────────────────────────────────────────────────
    async fetchList(page = 1) {
      this.loading = true
      this.error = null
      try {
        const params = {
          ...this.filters,
          page,
          per_page: this.pagination.perPage,
        }
        const { data } = await api.get('/admin/questionnaires', { params })
        this.list = data.data
        if (data.meta) {
          this.pagination = {
            currentPage: data.meta.current_page,
            lastPage:    data.meta.last_page,
            perPage:     data.meta.per_page,
            total:       data.meta.total,
          }
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat daftar kuesioner.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchById(id) {
      this.loadingDetail = true
      this.error = null
      try {
        const { data } = await api.get(`/admin/questionnaires/${id}`)
        this.current  = data.data
        this.sections = data.data.sections ?? []
        // Ekstrak flat list pertanyaan dari semua seksi
        this.questions = (data.data.sections ?? []).flatMap(
          (s) => (s.questions ?? []).map((q) => ({ ...q, section_id: s.id }))
        )
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat detail kuesioner.'
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    // ── CRUD Kuesioner ─────────────────────────────────────────────────────────
    async create(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/admin/questionnaires', payload)
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal membuat kuesioner.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async update(id, payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.put(`/admin/questionnaires/${id}`, payload)
        if (this.current?.id === id) {
          this.current = { ...this.current, ...data.data }
        }
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui kuesioner.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async destroy(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/admin/questionnaires/${id}`)
        this.list = this.list.filter((q) => q.id !== id)
        if (this.current?.id === id) this.clearCurrent()
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus kuesioner.'
        throw err
      } finally {
        this.loading = false
      }
    },

    // ── Lifecycle: Publish & Archive ───────────────────────────────────────────
    async publish(id) {
      this.loadingPublish = true
      this.error = null
      try {
        const { data } = await api.post(`/admin/questionnaires/${id}/publish`)
        if (this.current?.id === id) {
          this.current.status       = data.data.status
          this.current.published_at = data.data.published_at
        }
        const item = this.list.find((q) => q.id === id)
        if (item) item.status = data.data.status
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mempublikasikan kuesioner.'
        throw err
      } finally {
        this.loadingPublish = false
      }
    },

    async archive(id) {
      this.loadingPublish = true
      this.error = null
      try {
        const { data } = await api.post(`/admin/questionnaires/${id}/archive`)
        if (this.current?.id === id) {
          this.current.status = data.data?.status ?? 'arsip'
        }
        const item = this.list.find((q) => q.id === id)
        if (item) item.status = 'arsip'
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal mengarsipkan kuesioner.'
        throw err
      } finally {
        this.loadingPublish = false
      }
    },

    // ── Seksi ──────────────────────────────────────────────────────────────────
    async addSection(questionnaireId, payload) {
      this.loadingSection = true
      this.error = null
      try {
        const { data } = await api.post(
          `/admin/questionnaires/${questionnaireId}/sections`,
          payload
        )
        const newSection = { ...data.data, questions: [] }
        this.sections.push(newSection)
        if (this.current) this.current.sections = this.sections
        return newSection
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menambah seksi.'
        throw err
      } finally {
        this.loadingSection = false
      }
    },

    async updateSection(questionnaireId, sectionId, payload) {
      this.loadingSection = true
      this.error = null
      try {
        const { data } = await api.put(
          `/admin/questionnaires/${questionnaireId}/sections/${sectionId}`,
          payload
        )
        const idx = this.sections.findIndex((s) => s.id === sectionId)
        if (idx !== -1) {
          this.sections[idx] = { ...this.sections[idx], ...data.data }
        }
        if (this.current) this.current.sections = this.sections
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui seksi.'
        throw err
      } finally {
        this.loadingSection = false
      }
    },

    async removeSection(questionnaireId, sectionId) {
      this.loadingSection = true
      this.error = null
      try {
        await api.delete(
          `/admin/questionnaires/${questionnaireId}/sections/${sectionId}`
        )
        this.sections = this.sections.filter((s) => s.id !== sectionId)
        this.questions = this.questions.filter((q) => q.section_id !== sectionId)
        if (this.current) this.current.sections = this.sections
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus seksi.'
        throw err
      } finally {
        this.loadingSection = false
      }
    },

    // ── Pertanyaan ─────────────────────────────────────────────────────────────
    /**
     * Memuat ulang pertanyaan dari server (dipanggil setelah reorder atau
     * perubahan massal).
     */
    async fetchQuestions(questionnaireId) {
      this.loadingQuestion = true
      this.error = null
      try {
        const { data } = await api.get(`/admin/questionnaires/${questionnaireId}`)
        this.sections = data.data.sections ?? []
        this.questions = (data.data.sections ?? []).flatMap(
          (s) => (s.questions ?? []).map((q) => ({ ...q, section_id: s.id }))
        )
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat pertanyaan.'
        throw err
      } finally {
        this.loadingQuestion = false
      }
    },

    async addQuestion(questionnaireId, payload) {
      this.loadingQuestion = true
      this.error = null
      try {
        const { data } = await api.post(
          `/admin/questionnaires/${questionnaireId}/questions`,
          payload
        )
        const newQ = { ...data.data, section_id: payload.section_id }
        this.questions.push(newQ)
        // Sisipkan juga ke dalam this.sections agar QuestionnaireBuilder langsung reaktif
        const sec = this.sections.find((s) => s.id === payload.section_id)
        if (sec) {
          if (!sec.questions) sec.questions = []
          sec.questions.push(newQ)
        }
        return newQ
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menambah pertanyaan.'
        throw err
      } finally {
        this.loadingQuestion = false
      }
    },

    async updateQuestion(questionnaireId, questionId, payload) {
      this.loadingQuestion = true
      this.error = null
      try {
        const { data } = await api.put(
          `/admin/questionnaires/${questionnaireId}/questions/${questionId}`,
          payload
        )
        // Update flat list
        const idx = this.questions.findIndex((q) => q.id === questionId)
        if (idx !== -1) {
          this.questions[idx] = { ...this.questions[idx], ...data.data }
        }
        // Update di dalam sections
        for (const sec of this.sections) {
          const qi = (sec.questions ?? []).findIndex((q) => q.id === questionId)
          if (qi !== -1) {
            sec.questions[qi] = { ...sec.questions[qi], ...data.data }
            break
          }
        }
        return data.data
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memperbarui pertanyaan.'
        throw err
      } finally {
        this.loadingQuestion = false
      }
    },

    async removeQuestion(questionnaireId, questionId) {
      this.loadingQuestion = true
      this.error = null
      try {
        await api.delete(
          `/admin/questionnaires/${questionnaireId}/questions/${questionId}`
        )
        this.questions = this.questions.filter((q) => q.id !== questionId)
        for (const sec of this.sections) {
          if (sec.questions) {
            sec.questions = sec.questions.filter((q) => q.id !== questionId)
          }
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menghapus pertanyaan.'
        throw err
      } finally {
        this.loadingQuestion = false
      }
    },

    /**
     * Reorder pertanyaan dalam satu seksi.
     * @param {number} questionnaireId
     * @param {number[]} orderedIds - array ID pertanyaan sesuai urutan baru
     */
    async reorderQuestions(questionnaireId, orderedIds) {
      this.loadingQuestion = true
      this.error = null
      try {
        await api.put(
          `/admin/questionnaires/${questionnaireId}/questions/reorder`,
          { order: orderedIds }
        )
        // Terapkan urutan baru ke flat list secara optimistis
        const map = Object.fromEntries(this.questions.map((q) => [q.id, q]))
        this.questions = orderedIds
          .filter((id) => map[id])
          .map((id, i) => ({ ...map[id], order_number: i + 1 }))
        // Sinkronisasi ke dalam sections
        for (const sec of this.sections) {
          if (!sec.questions?.length) continue
          const secIds = sec.questions.map((q) => q.id)
          sec.questions = orderedIds
            .filter((id) => secIds.includes(id))
            .map((id, i) => ({ ...map[id], order_number: i + 1 }))
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal menyimpan urutan pertanyaan.'
        throw err
      } finally {
        this.loadingQuestion = false
      }
    },

    // ── Filter & Reset ─────────────────────────────────────────────────────────
    setFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
    },

    resetFilters() {
      this.filters = {
        search:   '',
        type:     '',
        status:   '',
        sort_by:  'created_at',
        sort_dir: 'desc',
      }
    },

    clearCurrent() {
      this.current   = null
      this.sections  = []
      this.questions = []
    },

    /**
     * Reset state builder tanpa menyentuh list & pagination.
     * Dipanggil saat meninggalkan QuestionnaireBuilderPage.
     */
    clearBuilder() {
      this.sections  = []
      this.questions = []
    },
  },
})
