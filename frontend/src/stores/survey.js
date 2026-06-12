/**
 * stores/survey.js — Pinia Survey Store
 *
 * Digunakan oleh:
 *   - pages/alumni/SurveyPage.vue   → role alumni (Bearer token)
 *   - pages/employer/SurveyPage.vue → role employer (Bearer token)
 *
 * State  : questionnaire, response, answers, completion, status, loading, error
 * Getters: currentSection, totalSections, isLastSection, currentSectionQuestions,
 *          isCompleted, canSubmit
 * Actions: fetchSurvey, saveDraft (debounced 2 s), submit, setAnswer,
 *          goToSection, resetStore
 *
 * answers shape:
 *   {
 *     [question_id]: {
 *       answer_value   : string|number|null,
 *       answer_options : number[]|null,   // array option id
 *       answer_text    : string|null,
 *     }
 *   }
 *
 * API:
 *   GET  /api/v1/alumni/survey          → fetchSurvey (alumni)
 *   POST /api/v1/alumni/survey/save-draft
 *   POST /api/v1/alumni/survey/submit
 *   GET  /api/v1/employer/survey        → fetchSurvey (employer)
 *   POST /api/v1/employer/survey/save-draft
 *   POST /api/v1/employer/survey/submit
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSurveyStore = defineStore('survey', () => {
  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------
  const questionnaire = ref(null)   // { id, title, is_paginated, estimated_minutes, sections[] }
  const response      = ref(null)   // { id, status, completion_percentage, started_at, ... }
  const period        = ref(null)   // { id, name, end_date }
  const answers       = ref({})     // keyed by question_id
  const completion    = ref(0)      // 0-100
  const status        = ref(null)   // 'draft' | 'selesai' | null
  const loading       = ref(false)
  const submitting    = ref(false)
  const error         = ref(null)
  const currentSectionIndex = ref(0)

  // Debounce timer reference
  let _draftTimer = null

  // ---------------------------------------------------------------------------
  // Getters
  // ---------------------------------------------------------------------------
  const sections = computed(() => questionnaire.value?.sections ?? [])

  const totalSections = computed(() => sections.value.length)

  const currentSection = computed(() => sections.value[currentSectionIndex.value] ?? null)

  const isLastSection = computed(
    () => currentSectionIndex.value === totalSections.value - 1
  )

  const isFirstSection = computed(() => currentSectionIndex.value === 0)

  /** Pertanyaan pada seksi aktif, sudah diurutkan by order_number */
  const currentSectionQuestions = computed(() => {
    if (!currentSection.value) return []
    return [...(currentSection.value.questions ?? [])].sort(
      (a, b) => a.order_number - b.order_number
    )
  })

  const isCompleted = computed(() => status.value === 'selesai')

  /** Cek apakah semua pertanyaan is_required pada seksi aktif sudah terisi */
  const currentSectionValid = computed(() => {
    return currentSectionQuestions.value
      .filter((q) => q.is_required)
      .every((q) => _isAnswered(q.id))
  })

  const canSubmit = computed(() => {
    if (!questionnaire.value) return false
    // Validasi semua required question di semua seksi
    const allQuestions = sections.value.flatMap((s) => s.questions ?? [])
    return allQuestions
      .filter((q) => q.is_required)
      .every((q) => _isAnswered(q.id))
  })

  // ---------------------------------------------------------------------------
  // Helpers (private)
  // ---------------------------------------------------------------------------
  function _isAnswered(questionId) {
    const ans = answers.value[questionId]
    if (!ans) return false
    const { answer_value, answer_options, answer_text } = ans
    if (answer_options && answer_options.length > 0) return true
    if (answer_value !== null && answer_value !== undefined && answer_value !== '') return true
    if (answer_text !== null && answer_text !== undefined && answer_text !== '') return true
    return false
  }

  /** Konversi answers object ke array format yang diterima API */
  function _buildAnswersPayload() {
    return Object.entries(answers.value).map(([questionId, ans]) => ({
      question_id     : Number(questionId),
      answer_value    : ans.answer_value    ?? null,
      answer_options  : ans.answer_options  ?? null,
      answer_text     : ans.answer_text     ?? null,
    }))
  }

  /** Tentukan base URL sesuai role user (alumni / employer) */
  function _baseUrl(role = 'alumni') {
    return role === 'employer' ? '/employer/survey' : '/alumni/survey'
  }

  // ---------------------------------------------------------------------------
  // Actions
  // ---------------------------------------------------------------------------

  /**
   * Ambil data kuesioner + draft jawaban yang tersimpan
   * @param {'alumni'|'employer'} role
   */
  async function fetchSurvey(role = 'alumni') {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get(_baseUrl(role))
      const payload  = data.data

      questionnaire.value     = payload.questionnaire
      response.value          = payload.response
      period.value            = payload.period ?? null
      status.value            = payload.response?.status ?? null
      completion.value        = payload.response?.completion_percentage ?? 0
      currentSectionIndex.value = 0

      // Hydrate answers dari draft yang tersimpan
      const savedAnswers = payload.response?.answers ?? {}
      answers.value = {}
      Object.entries(savedAnswers).forEach(([questionId, ans]) => {
        answers.value[questionId] = {
          answer_value  : ans.answer_value   ?? null,
          answer_options: ans.answer_options ?? null,
          answer_text   : ans.answer_text    ?? null,
        }
      })

      return payload
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal memuat survei.' }
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Set / update jawaban satu pertanyaan.
   * Dipanggil setiap kali user mengisi form; otomatis trigger saveDraft (debounced).
   *
   * @param {number} questionId
   * @param {'answer_value'|'answer_options'|'answer_text'} field
   * @param {*} value
   * @param {'alumni'|'employer'} role
   */
  function setAnswer(questionId, field, value, role = 'alumni') {
    if (!answers.value[questionId]) {
      answers.value[questionId] = {
        answer_value  : null,
        answer_options: null,
        answer_text   : null,
      }
    }
    answers.value[questionId][field] = value
    // Trigger debounced auto-save
    _debouncedSaveDraft(role)
  }

  /**
   * Simpan draft ke server (debounced 2 detik).
   * @param {'alumni'|'employer'} role
   */
  function _debouncedSaveDraft(role = 'alumni') {
    if (_draftTimer) clearTimeout(_draftTimer)
    _draftTimer = setTimeout(() => saveDraft(role), 2000)
  }

  /**
   * Kirim draft langsung ke server (tanpa debounce).
   * Bisa dipanggil manual dari tombol [Simpan Draft].
   * @param {'alumni'|'employer'} role
   */
  async function saveDraft(role = 'alumni') {
    if (!response.value?.id) return
    // Tidak set loading global agar tidak mengganggu UI
    try {
      const { data } = await api.post(`${_baseUrl(role)}/save-draft`, {
        response_id: response.value.id,
        answers    : _buildAnswersPayload(),
      })
      completion.value = data.data?.completion_percentage ?? completion.value
    } catch {
      // Silent fail — auto-save bukan aksi kritis; tidak tampilkan error
    }
  }

  /**
   * Submit survei final (setelah konfirmasi modal).
   * @param {'alumni'|'employer'} role
   */
  async function submit(role = 'alumni') {
    submitting.value = true
    error.value      = null
    // Cancel pending debounced save
    if (_draftTimer) {
      clearTimeout(_draftTimer)
      _draftTimer = null
    }
    try {
      const { data } = await api.post(`${_baseUrl(role)}/submit`, {
        response_id: response.value.id,
        answers    : _buildAnswersPayload(),
      })
      status.value     = 'selesai'
      completion.value = data.data?.completion_percentage ?? 100
      if (response.value) {
        response.value.submitted_at = data.data?.submitted_at ?? null
      }
      return data.data
    } catch (err) {
      error.value = err.response?.data ?? { message: 'Gagal mengirim survei.' }
      throw err
    } finally {
      submitting.value = false
    }
  }

  /** Navigasi ke seksi berikutnya */
  function nextSection() {
    if (!isLastSection.value) {
      currentSectionIndex.value += 1
    }
  }

  /** Navigasi ke seksi sebelumnya */
  function prevSection() {
    if (!isFirstSection.value) {
      currentSectionIndex.value -= 1
    }
  }

  /** Navigasi ke seksi tertentu by index */
  function goToSection(index) {
    if (index >= 0 && index < totalSections.value) {
      currentSectionIndex.value = index
    }
  }

  /** Reset store (dipanggil saat component unmount atau logout) */
  function resetStore() {
    if (_draftTimer) clearTimeout(_draftTimer)
    questionnaire.value       = null
    response.value            = null
    period.value              = null
    answers.value             = {}
    completion.value          = 0
    status.value              = null
    loading.value             = false
    submitting.value          = false
    error.value               = null
    currentSectionIndex.value = 0
  }

  // ---------------------------------------------------------------------------
  // Expose
  // ---------------------------------------------------------------------------
  return {
    // state
    questionnaire, response, period, answers, completion, status,
    loading, submitting, error, currentSectionIndex,
    // getters
    sections, totalSections, currentSection, isLastSection, isFirstSection,
    currentSectionQuestions, isCompleted, currentSectionValid, canSubmit,
    // actions
    fetchSurvey, setAnswer, saveDraft, submit,
    nextSection, prevSection, goToSection, resetStore,
  }
})
