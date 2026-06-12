<script setup>
/**
 * pages/admin/survey-periods/SurveyPeriodDetailPage.vue
 *
 * Halaman Create / Edit Periode Survei.
 *
 * Mode:
 *  - Create : route admin.survey-periods.create  (id = undefined)
 *  - Edit   : route admin.survey-periods.detail  (id = :id)
 *
 * Fitur:
 *  - Form: nama, tahun akademik, start_date, end_date, questionnaire, target angkatan
 *  - Load daftar kuesioner (store questionnaire atau endpoint GET /admin/questionnaires)
 *  - Load daftar graduation years untuk multi-select angkatan target
 *  - Simpan (create/update) → redirect ke index
 *  - Aktifkan / Tutup langsung dari halaman detail
 *  - Kirim Undangan Blast dengan konfirmasi
 *  - Readonly form saat status = ditutup
 *  - Tabel respons singkat (nama alumni, status, submitted_at)
 *
 * Store   : useSurveyAdminStore
 * Route   : /admin/survey-periods/create
 *           /admin/survey-periods/:id
 */
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter }             from 'vue-router'
import { useSurveyAdminStore }             from '@/stores/surveyAdmin'
import { useToast }                        from '@/composables/useToast'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Badge        from '@/components/common/Badge.vue'
import Pagination   from '@/components/common/Pagination.vue'
import api          from '@/services/api'

const route  = useRoute()
const router = useRouter()
const store  = useSurveyAdminStore()
const toast  = useToast()

// ---------------------------------------------------------------------------
// Mode
// ---------------------------------------------------------------------------
const isCreate = computed(() => !route.params.id)
const periodId = computed(() => route.params.id ? Number(route.params.id) : null)

// ---------------------------------------------------------------------------
// Form state
// ---------------------------------------------------------------------------
const form = ref({
  name                    : '',
  academic_year           : '',
  start_date              : '',
  end_date                : '',
  questionnaire_id        : '',
  target_graduation_years : [],   // array of graduation_year id
  description             : '',
})

const formErrors   = ref({})
const saving       = ref(false)
const pageLoading  = ref(false)
const currentPeriod = ref(null)

// Opsi dropdown
const questionnaires    = ref([])   // { id, title }
const graduationYears   = ref([])   // { id, year }

// Confirm modal
const confirmModal = ref({ open: false, title: '', message: '', onConfirm: null })

// Blast state
const blasting   = ref(false)
const activating = ref(false)
const closing    = ref(false)

// Responses mini-table
const responses     = ref([])
const responsesPage = ref(1)
const responsesTotal= ref(0)
const responsesLoading = ref(false)

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------
const isReadonly = computed(() => currentPeriod.value?.status === 'ditutup')
const periodStatus = computed(() => currentPeriod.value?.status ?? null)

const responseColumns = [
  { key: 'alumni_name',    label: 'Alumni' },
  { key: 'status',         label: 'Status' },
  { key: 'completion',     label: 'Progres', align: 'center' },
  { key: 'submitted_at',   label: 'Dikirim Pada' },
]

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function badgeVariant(status) {
  return { draft: 'secondary', aktif: 'success', ditutup: 'danger', selesai: 'primary' }[status] ?? 'secondary'
}

function clearError(field) {
  delete formErrors.value[field]
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  pageLoading.value = true
  try {
    await Promise.all([
      loadQuestionnaires(),
      loadGraduationYears(),
    ])
    if (!isCreate.value) {
      await loadPeriod()
      await loadResponses()
    }
  } finally {
    pageLoading.value = false
  }
})

async function loadQuestionnaires() {
  try {
    const { data } = await api.get('/admin/questionnaires', { params: { per_page: 100, status: 'aktif' } })
    questionnaires.value = data.data ?? []
  } catch {
    // silent — dropdown dikosongkan
  }
}

async function loadGraduationYears() {
  try {
    const { data } = await api.get('/admin/settings/graduation-years', { params: { per_page: 100 } })
    graduationYears.value = data.data ?? []
  } catch {
    // silent
  }
}

async function loadPeriod() {
  try {
    const data = await store.fetchPeriod(periodId.value)
    currentPeriod.value = data
    // Isi form dari data period
    form.value = {
      name                    : data.name ?? '',
      academic_year           : data.academic_year ?? '',
      start_date              : data.start_date?.substring(0, 10) ?? '',
      end_date                : data.end_date?.substring(0, 10) ?? '',
      questionnaire_id        : data.questionnaire_id ?? '',
      target_graduation_years : data.target_graduation_years ?? [],
      description             : data.description ?? '',
    }
  } catch {
    toast.error('Gagal memuat data periode survei.')
    router.push({ name: 'admin.survey-periods.index' })
  }
}

async function loadResponses(page = 1) {
  if (!periodId.value) return
  responsesLoading.value = true
  try {
    const { data } = await api.get(`/admin/survey-periods/${periodId.value}/responses`, {
      params: { page, per_page: 10 },
    })
    responses.value      = data.data ?? []
    responsesTotal.value = data.meta?.total ?? 0
    responsesPage.value  = page
  } catch {
    // silent — tabel dikosongkan
  } finally {
    responsesLoading.value = false
  }
}

// ---------------------------------------------------------------------------
// Validasi form sederhana
// ---------------------------------------------------------------------------
function validateForm() {
  const errors = {}
  if (!form.value.name.trim())           errors.name           = 'Nama periode wajib diisi.'
  if (!form.value.academic_year.trim())  errors.academic_year  = 'Tahun akademik wajib diisi.'
  if (!form.value.start_date)            errors.start_date     = 'Tanggal mulai wajib diisi.'
  if (!form.value.end_date)              errors.end_date       = 'Tanggal selesai wajib diisi.'
  if (!form.value.questionnaire_id)      errors.questionnaire_id = 'Pilih kuesioner yang akan digunakan.'
  if (form.value.start_date && form.value.end_date && form.value.start_date >= form.value.end_date) {
    errors.end_date = 'Tanggal selesai harus setelah tanggal mulai.'
  }
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

// ---------------------------------------------------------------------------
// Simpan (create / update)
// ---------------------------------------------------------------------------
async function handleSave() {
  if (!validateForm()) return
  saving.value = true
  try {
    const payload = { ...form.value }
    if (isCreate.value) {
      const created = await store.createPeriod(payload)
      toast.success('Periode survei berhasil dibuat.')
      router.push({ name: 'admin.survey-periods.detail', params: { id: created.id } })
    } else {
      await store.updatePeriod(periodId.value, payload)
      toast.success('Periode survei berhasil diperbarui.')
      await loadPeriod()
    }
  } catch (err) {
    const serverErrors = err.response?.data?.errors ?? {}
    if (Object.keys(serverErrors).length) {
      formErrors.value = serverErrors
    } else {
      toast.error(err.response?.data?.message ?? 'Gagal menyimpan periode survei.')
    }
  } finally {
    saving.value = false
  }
}

// ---------------------------------------------------------------------------
// Aksi status
// ---------------------------------------------------------------------------
function confirmActivate() {
  confirmModal.value = {
    open    : true,
    title   : 'Aktifkan Periode',
    message : `Aktifkan periode "${currentPeriod.value?.name}"? Alumni bisa diundang setelah periode aktif.`,
    onConfirm: doActivate,
  }
}

function confirmClose() {
  confirmModal.value = {
    open    : true,
    title   : 'Tutup Periode',
    message : 'Menutup periode ini akan menghentikan penerimaan jawaban. Tindakan tidak dapat dibatalkan.',
    onConfirm: doClose,
  }
}

function confirmBlast() {
  confirmModal.value = {
    open    : true,
    title   : 'Kirim Undangan Massal',
    message : `Kirim undangan survei ke semua alumni target periode ini? Proses berjalan di background queue dan mungkin memakan beberapa menit.`,
    onConfirm: doBlast,
  }
}

async function doActivate() {
  activating.value = true
  try {
    await store.activatePeriod(periodId.value)
    toast.success('Periode berhasil diaktifkan.')
    await loadPeriod()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengaktifkan periode.')
  } finally {
    activating.value = false
  }
}

async function doClose() {
  closing.value = true
  try {
    await store.closePeriod(periodId.value)
    toast.success('Periode berhasil ditutup.')
    await loadPeriod()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menutup periode.')
  } finally {
    closing.value = false
  }
}

async function doBlast() {
  blasting.value = true
  try {
    await store.blastInvitations(periodId.value, form.value.questionnaire_id)
    toast.success('Undangan massal berhasil diantrekan.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal mengirim undangan.')
  } finally {
    blasting.value = false
  }
}

function handleConfirm() {
  if (confirmModal.value.onConfirm) confirmModal.value.onConfirm()
  confirmModal.value.open = false
}

function handleCancelConfirm() {
  confirmModal.value.open = false
}

// ---------------------------------------------------------------------------
// Checkbox multi-select angkatan
// ---------------------------------------------------------------------------
function toggleGraduationYear(yearId) {
  const idx = form.value.target_graduation_years.indexOf(yearId)
  if (idx === -1) form.value.target_graduation_years.push(yearId)
  else            form.value.target_graduation_years.splice(idx, 1)
}

function isYearSelected(yearId) {
  return form.value.target_graduation_years.includes(yearId)
}
</script>

<template>
  <div class="survey-period-detail">
    <!-- Loading overlay -->
    <div v-if="pageLoading" class="page-loading">
      <span class="spinner spinner--lg" aria-label="Memuat data..."/>
    </div>

    <template v-else>
      <!-- ── Breadcrumb & Header ──────────────────────────────────────── -->
      <div class="page-header">
        <div class="page-header__left">
          <button class="back-btn" @click="router.push({ name: 'admin.survey-periods.index' })">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            Kembali
          </button>
          <h1 class="page-title">
            {{ isCreate ? 'Buat Periode Survei' : currentPeriod?.name ?? 'Detail Periode' }}
          </h1>
          <Badge v-if="periodStatus" :variant="badgeVariant(periodStatus)" class="status-badge">
            {{ { draft: 'Draft', aktif: 'Aktif', ditutup: 'Ditutup' }[periodStatus] ?? periodStatus }}
          </Badge>
        </div>

        <!-- Tombol aksi status (hanya mode edit) -->
        <div v-if="!isCreate" class="header-actions">
          <button
            v-if="periodStatus === 'draft'"
            class="btn btn-success"
            :disabled="activating"
            @click="confirmActivate"
          >
            <span v-if="activating" class="spinner spinner--xs" aria-hidden="true"/>
            <span v-else>Aktifkan</span>
          </button>

          <button
            v-if="periodStatus === 'aktif'"
            class="btn btn-primary"
            :disabled="blasting"
            @click="confirmBlast"
          >
            <span v-if="blasting" class="spinner spinner--xs" aria-hidden="true"/>
            <span v-else>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:inline;vertical-align:-2px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Kirim Undangan
            </span>
          </button>

          <button
            v-if="periodStatus === 'aktif'"
            class="btn btn-danger"
            :disabled="closing"
            @click="confirmClose"
          >
            <span v-if="closing" class="spinner spinner--xs" aria-hidden="true"/>
            <span v-else>Tutup Periode</span>
          </button>
        </div>
      </div>

      <!-- ── Form Card ────────────────────────────────────────────────── -->
      <div class="card form-card">
        <h2 class="card-section-title">Informasi Periode</h2>

        <fieldset :disabled="isReadonly" class="form-fieldset">
          <div class="form-grid">
            <!-- Nama -->
            <div class="form-group form-group--wide">
              <label class="form-label" for="sp-name">Nama Periode <span class="required">*</span></label>
              <input
                id="sp-name"
                v-model="form.name"
                type="text"
                class="form-input"
                :class="{ 'form-input--error': formErrors.name }"
                placeholder="Contoh: Tracer Study Angkatan 2020"
                maxlength="255"
                @input="clearError('name')"
              />
              <p v-if="formErrors.name" class="form-error">{{ formErrors.name }}</p>
            </div>

            <!-- Tahun Akademik -->
            <div class="form-group">
              <label class="form-label" for="sp-academic-year">Tahun Akademik <span class="required">*</span></label>
              <input
                id="sp-academic-year"
                v-model="form.academic_year"
                type="text"
                class="form-input"
                :class="{ 'form-input--error': formErrors.academic_year }"
                placeholder="2024/2025"
                maxlength="20"
                @input="clearError('academic_year')"
              />
              <p v-if="formErrors.academic_year" class="form-error">{{ formErrors.academic_year }}</p>
            </div>

            <!-- Start Date -->
            <div class="form-group">
              <label class="form-label" for="sp-start">Tanggal Mulai <span class="required">*</span></label>
              <input
                id="sp-start"
                v-model="form.start_date"
                type="date"
                class="form-input"
                :class="{ 'form-input--error': formErrors.start_date }"
                @change="clearError('start_date')"
              />
              <p v-if="formErrors.start_date" class="form-error">{{ formErrors.start_date }}</p>
            </div>

            <!-- End Date -->
            <div class="form-group">
              <label class="form-label" for="sp-end">Tanggal Selesai <span class="required">*</span></label>
              <input
                id="sp-end"
                v-model="form.end_date"
                type="date"
                class="form-input"
                :class="{ 'form-input--error': formErrors.end_date }"
                @change="clearError('end_date')"
              />
              <p v-if="formErrors.end_date" class="form-error">{{ formErrors.end_date }}</p>
            </div>

            <!-- Questionnaire -->
            <div class="form-group form-group--wide">
              <label class="form-label" for="sp-questionnaire">Kuesioner <span class="required">*</span></label>
              <select
                id="sp-questionnaire"
                v-model="form.questionnaire_id"
                class="form-input"
                :class="{ 'form-input--error': formErrors.questionnaire_id }"
                @change="clearError('questionnaire_id')"
              >
                <option value="" disabled>— Pilih kuesioner —</option>
                <option v-for="q in questionnaires" :key="q.id" :value="q.id">
                  {{ q.title }}
                </option>
              </select>
              <p v-if="formErrors.questionnaire_id" class="form-error">{{ formErrors.questionnaire_id }}</p>
              <p class="form-hint">Kuesioner yang ditampilkan hanya yang berstatus aktif.</p>
            </div>

            <!-- Deskripsi -->
            <div class="form-group form-group--full">
              <label class="form-label" for="sp-desc">Deskripsi <span class="optional">(opsional)</span></label>
              <textarea
                id="sp-desc"
                v-model="form.description"
                class="form-input form-textarea"
                rows="3"
                placeholder="Catatan atau keterangan tambahan tentang periode ini"
                maxlength="1000"
              />
            </div>
          </div>

          <!-- Target Angkatan -->
          <div class="form-group form-group--full" style="margin-top: var(--space-4)">
            <label class="form-label">Target Angkatan Lulus <span class="optional">(kosongkan = semua angkatan)</span></label>
            <div class="year-checkboxes">
              <label
                v-for="gy in graduationYears"
                :key="gy.id"
                class="year-checkbox"
              >
                <input
                  type="checkbox"
                  :checked="isYearSelected(gy.id)"
                  :disabled="isReadonly"
                  @change="toggleGraduationYear(gy.id)"
                />
                {{ gy.year }}
              </label>
            </div>
          </div>
        </fieldset>

        <!-- Tombol simpan -->
        <div v-if="!isReadonly" class="form-actions">
          <button
            class="btn btn-ghost"
            @click="router.push({ name: 'admin.survey-periods.index' })"
          >
            Batal
          </button>
          <button
            class="btn btn-primary"
            :disabled="saving"
            @click="handleSave"
          >
            <span v-if="saving" class="spinner spinner--xs" aria-hidden="true"/>
            <span v-else>{{ isCreate ? 'Buat Periode' : 'Simpan Perubahan' }}</span>
          </button>
        </div>

        <!-- Info readonly -->
        <div v-else class="readonly-notice">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          Periode ini sudah ditutup. Data tidak dapat diubah.
        </div>
      </div>

      <!-- ── Tabel Respons (hanya mode edit) ─────────────────────────── -->
      <div v-if="!isCreate" class="card responses-card">
        <div class="card-header">
          <h2 class="card-section-title">Respons Alumni</h2>
          <span class="response-meta">Total: {{ responsesTotal }} respons</span>
        </div>

        <div v-if="responsesLoading" class="loading-block">
          <span class="spinner" aria-label="Memuat respons..."/>
        </div>

        <template v-else>
          <div v-if="responses.length === 0" class="empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="empty-icon">
              <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
            <p>Belum ada alumni yang mengisi survei pada periode ini.</p>
          </div>

          <table v-else class="responses-table">
            <thead>
              <tr>
                <th>Alumni</th>
                <th>Status</th>
                <th style="text-align:center">Progres</th>
                <th>Dikirim Pada</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in responses" :key="r.id">
                <td>
                  <div class="alumni-name">{{ r.alumni?.name ?? '—' }}</div>
                  <div class="alumni-nim">{{ r.alumni?.nim ?? '' }}</div>
                </td>
                <td>
                  <Badge :variant="badgeVariant(r.status)">{{ r.status }}</Badge>
                </td>
                <td style="text-align:center">
                  <div class="progress-bar-wrap">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: (r.completion_percentage ?? 0) + '%' }"/>
                    </div>
                    <span class="progress-pct">{{ r.completion_percentage ?? 0 }}%</span>
                  </div>
                </td>
                <td class="date-cell">{{ formatDate(r.submitted_at) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination respons -->
          <Pagination
            v-if="Math.ceil(responsesTotal / 10) > 1"
            :current-page="responsesPage"
            :last-page="Math.ceil(responsesTotal / 10)"
            :total="responsesTotal"
            class="table-pagination"
            @change="loadResponses"
          />
        </template>
      </div>
    </template>

    <!-- ── Confirm Modal ──────────────────────────────────────────────── -->
    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      @confirm="handleConfirm"
      @cancel="handleCancelConfirm"
    />
  </div>
</template>

<style scoped>
.survey-period-detail { padding: var(--space-6); max-width: 900px; margin-inline: auto; }

/* Loading fullpage */
.page-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

/* Header */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
  flex-wrap: wrap;
}
.page-header__left { display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap; }
.page-title  { font-size: var(--text-xl); font-weight: 700; color: var(--color-text); margin: 0; }
.status-badge{ flex-shrink: 0; }

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  cursor: pointer;
  padding: var(--space-1) 0;
}
.back-btn:hover { color: var(--color-text); }

.header-actions { display: flex; gap: var(--space-2); flex-wrap: wrap; }

/* Card */
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  margin-bottom: var(--space-6);
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}
.card-section-title {
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--space-5) 0;
}
.response-meta { font-size: var(--text-sm); color: var(--color-text-muted); }

/* Form */
.form-fieldset { border: none; padding: 0; margin: 0; }
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-4) var(--space-6);
}
.form-group { display: flex; flex-direction: column; gap: var(--space-1); }
.form-group--wide { grid-column: span 2; }
.form-group--full { grid-column: 1 / -1; }

.form-label   { font-size: var(--text-sm); font-weight: 500; color: var(--color-text); }
.required     { color: var(--color-notification); margin-left: 2px; }
.optional     { font-size: var(--text-xs); color: var(--color-text-muted); font-weight: 400; }

.form-input {
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-2);
  color: var(--color-text);
  font-size: var(--text-sm);
  transition: border-color var(--transition-interactive);
}
.form-input:focus    { outline: none; border-color: var(--color-primary); }
.form-input--error   { border-color: var(--color-notification); }
.form-input:disabled { opacity: 0.6; cursor: not-allowed; }
.form-textarea       { resize: vertical; min-height: 80px; }

.form-error { font-size: var(--text-xs); color: var(--color-notification); margin: 0; }
.form-hint  { font-size: var(--text-xs); color: var(--color-text-muted); margin: 0; }

/* Graduation year checkboxes */
.year-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2) var(--space-4);
  margin-top: var(--space-2);
}
.year-checkbox {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  cursor: pointer;
  user-select: none;
}
.year-checkbox input { cursor: pointer; width: 15px; height: 15px; accent-color: var(--color-primary); }

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  margin-top: var(--space-6);
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border);
}

.readonly-notice {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-top: var(--space-4);
  padding: var(--space-3) var(--space-4);
  background: var(--color-surface-offset);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

/* Responses */
.responses-card { padding: 0; overflow: hidden; }
.responses-card .card-header { padding: var(--space-4) var(--space-6); border-bottom: 1px solid var(--color-border); margin: 0; }
.responses-card .card-section-title { margin: 0; }

.responses-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--text-sm);
}
.responses-table th {
  padding: var(--space-3) var(--space-4);
  text-align: left;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: var(--color-surface-offset);
  border-bottom: 1px solid var(--color-border);
}
.responses-table td {
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--color-divider);
  vertical-align: middle;
}
.responses-table tbody tr:last-child td { border-bottom: none; }
.responses-table tbody tr:hover td      { background: var(--color-surface-offset); }

.alumni-name { font-weight: 500; }
.alumni-nim  { font-size: var(--text-xs); color: var(--color-text-muted); }
.date-cell   { font-size: var(--text-xs); color: var(--color-text-muted); white-space: nowrap; }

.progress-bar-wrap { display: flex; align-items: center; gap: var(--space-2); justify-content: center; }
.progress-bar {
  width: 80px; height: 6px;
  background: var(--color-surface-offset-2);
  border-radius: var(--radius-full);
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: var(--color-primary);
  border-radius: var(--radius-full);
  transition: width 0.3s ease;
}
.progress-pct { font-size: var(--text-xs); color: var(--color-text-muted); min-width: 30px; text-align: right; }

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: var(--space-12) var(--space-8);
  color: var(--color-text-muted);
  gap: var(--space-3);
  font-size: var(--text-sm);
}
.empty-icon { color: var(--color-text-faint); }

.loading-block {
  display: flex;
  justify-content: center;
  padding: var(--space-8);
}

.table-pagination { padding: var(--space-4) var(--space-6); border-top: 1px solid var(--color-border); }

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 500;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background var(--transition-interactive), color var(--transition-interactive), border-color var(--transition-interactive);
  white-space: nowrap;
}
.btn:disabled { opacity: 0.55; cursor: not-allowed; }
.btn-primary { background: var(--color-primary); color: #fff; }
.btn-primary:hover:not(:disabled) { background: var(--color-primary-hover); }
.btn-success { background: var(--color-success); color: #fff; }
.btn-success:hover:not(:disabled) { background: var(--color-success-hover); }
.btn-danger  { background: var(--color-notification); color: #fff; }
.btn-danger:hover:not(:disabled)  { background: var(--color-notification-hover); }
.btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
  border-color: var(--color-border);
}
.btn-ghost:hover:not(:disabled) { background: var(--color-surface-offset); color: var(--color-text); }

.spinner {
  display: inline-block;
  width: 16px; height: 16px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.spinner--xs { width: 12px; height: 12px; }
.spinner--lg { width: 36px; height: 36px; border-width: 3px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-group--wide { grid-column: span 1; }
  .page-header { flex-direction: column; }
  .responses-table th:nth-child(4),
  .responses-table td:nth-child(4) { display: none; }
}
</style>
