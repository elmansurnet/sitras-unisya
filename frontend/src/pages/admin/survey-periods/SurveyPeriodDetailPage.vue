<script setup>
/**
 * pages/admin/survey-periods/SurveyPeriodDetailPage.vue
 *
 * Halaman Create / Edit Periode Survei.
 *
 * Mode:
 *  - Create : route admin.survey-periods.create  (id = undefined)
 *  - Edit   : route admin.survey-periods.detail  (id = :id)
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter }      from 'vue-router'
import { useSurveyAdminStore }      from '@/stores/surveyAdmin'
import { useToast }                 from '@/composables/useToast'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import Badge        from '@/components/common/Badge.vue'
import Pagination   from '@/components/common/Pagination.vue'
import api          from '@/services/api'

const route  = useRoute()
const router = useRouter()
const store  = useSurveyAdminStore()
const { toast } = useToast()

// ---------------------------------------------------------------------------
// Mode
// ---------------------------------------------------------------------------
const isCreate  = computed(() => !route.params.id)
const periodId  = computed(() => route.params.id ? Number(route.params.id) : null)

// ---------------------------------------------------------------------------
// Form state
// ---------------------------------------------------------------------------
const form = ref({
  name                    : '',
  academic_year           : '',
  start_date              : '',
  end_date                : '',
  questionnaire_id        : '',
  target_graduation_years : [],
  description             : '',
})

const formErrors    = ref({})
const saving        = ref(false)
const pageLoading   = ref(false)
const currentPeriod = ref(null)

const questionnaires  = ref([])
const graduationYears = ref([])

// Confirm modal — v-model binding
const showConfirm     = ref(false)
const confirmTitle    = ref('')
const confirmMessage  = ref('')
const confirmAction   = ref(null)

// Action loading states
const blasting   = ref(false)
const activating = ref(false)
const closing    = ref(false)

// Responses mini-table
const responses        = ref([])
const responsesPage    = ref(1)
const responsesTotal   = ref(0)
const responsesLoading = ref(false)

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------
const isReadonly   = computed(() => currentPeriod.value?.status === 'ditutup')
const periodStatus = computed(() => currentPeriod.value?.status ?? null)

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function badgeVariant(status) {
  return { draft: 'secondary', aktif: 'success', ditutup: 'danger', selesai: 'primary' }[status] ?? 'secondary'
}

function clearError(field) { delete formErrors.value[field] }

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  pageLoading.value = true
  try {
    await Promise.all([ loadQuestionnaires(), loadGraduationYears() ])
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
  } catch { /* silent */ }
}

async function loadGraduationYears() {
  try {
    // Fix: endpoint yang benar adalah /admin/graduation-years (bukan /admin/settings/graduation-years)
    const { data } = await api.get('/admin/graduation-years', { params: { per_page: 100 } })
    graduationYears.value = data.data ?? []
  } catch { /* silent */ }
}

async function loadPeriod() {
  try {
    const data = await store.fetchPeriod(periodId.value)
    currentPeriod.value = data
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
  } catch { /* silent */ } finally {
    responsesLoading.value = false
  }
}

// ---------------------------------------------------------------------------
// Validasi form
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
// Aksi status — buka ConfirmModal via v-model
// ---------------------------------------------------------------------------
function openConfirm(title, message, action) {
  confirmTitle.value   = title
  confirmMessage.value = message
  confirmAction.value  = action
  showConfirm.value    = true
}

function handleConfirm() {
  if (confirmAction.value) confirmAction.value()
  // v-model di ConfirmModal akan set false via emit update:modelValue
}

function confirmActivate() {
  openConfirm(
    'Aktifkan Periode',
    `Aktifkan periode "${currentPeriod.value?.name}"? Alumni bisa diundang setelah periode aktif.`,
    doActivate,
  )
}

function confirmClose() {
  openConfirm(
    'Tutup Periode',
    'Menutup periode ini akan menghentikan penerimaan jawaban. Tindakan tidak dapat dibatalkan.',
    doClose,
  )
}

function confirmBlast() {
  openConfirm(
    'Kirim Undangan Massal',
    'Kirim undangan survei ke semua alumni target periode ini? Proses berjalan di background queue dan mungkin memakan beberapa menit.',
    doBlast,
  )
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
  <div>
    <!-- Loading overlay -->
    <div v-if="pageLoading" class="flex items-center justify-center py-24">
      <svg class="h-8 w-8 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
    </div>

    <template v-else>
      <!-- ── Header ──────────────────────────────────────────────────── -->
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <button
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
            @click="router.push({ name: 'admin.survey-periods.index' })"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali
          </button>
          <h1 class="text-xl font-semibold text-gray-900">
            {{ isCreate ? 'Buat Periode Survei' : currentPeriod?.name ?? 'Detail Periode' }}
          </h1>
          <Badge v-if="periodStatus" :variant="badgeVariant(periodStatus)">
            {{ { draft: 'Draft', aktif: 'Aktif', ditutup: 'Ditutup' }[periodStatus] ?? periodStatus }}
          </Badge>
        </div>

        <!-- Tombol aksi status (hanya mode edit) -->
        <div v-if="!isCreate" class="flex flex-wrap gap-2">
          <button
            v-if="periodStatus === 'draft'"
            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 transition-colors"
            :disabled="activating"
            @click="confirmActivate"
          >
            <svg v-if="activating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            <span v-else>Aktifkan</span>
          </button>

          <button
            v-if="periodStatus === 'aktif'"
            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
            :disabled="blasting"
            @click="confirmBlast"
          >
            <svg v-if="blasting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            <template v-else>
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              Kirim Undangan
            </template>
          </button>

          <button
            v-if="periodStatus === 'aktif'"
            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors"
            :disabled="closing"
            @click="confirmClose"
          >
            <svg v-if="closing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            <span v-else>Tutup Periode</span>
          </button>
        </div>
      </div>

      <!-- ── Form Card ────────────────────────────────────────────────── -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mb-6">
        <h2 class="mb-5 text-base font-semibold text-gray-900">Informasi Periode</h2>

        <fieldset :disabled="isReadonly" class="border-0 p-0 m-0">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Nama -->
            <div class="sm:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-name">
                Nama Periode <span class="text-red-500">*</span>
              </label>
              <input
                id="sp-name"
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                :class="{ 'border-red-400': formErrors.name }"
                placeholder="Contoh: Tracer Study Angkatan 2020"
                maxlength="255"
                @input="clearError('name')"
              />
              <p v-if="formErrors.name" class="mt-1 text-xs text-red-500">{{ formErrors.name }}</p>
            </div>

            <!-- Tahun Akademik -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-academic-year">
                Tahun Akademik <span class="text-red-500">*</span>
              </label>
              <input
                id="sp-academic-year"
                v-model="form.academic_year"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                :class="{ 'border-red-400': formErrors.academic_year }"
                placeholder="2024/2025"
                maxlength="20"
                @input="clearError('academic_year')"
              />
              <p v-if="formErrors.academic_year" class="mt-1 text-xs text-red-500">{{ formErrors.academic_year }}</p>
            </div>

            <!-- Questionnaire -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-questionnaire">
                Kuesioner <span class="text-red-500">*</span>
              </label>
              <select
                id="sp-questionnaire"
                v-model="form.questionnaire_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                :class="{ 'border-red-400': formErrors.questionnaire_id }"
                @change="clearError('questionnaire_id')"
              >
                <option value="" disabled>— Pilih kuesioner —</option>
                <option v-for="q in questionnaires" :key="q.id" :value="q.id">
                  {{ q.title }}
                </option>
              </select>
              <p v-if="formErrors.questionnaire_id" class="mt-1 text-xs text-red-500">{{ formErrors.questionnaire_id }}</p>
              <p class="mt-1 text-xs text-gray-400">Hanya kuesioner berstatus aktif yang ditampilkan.</p>
            </div>

            <!-- Start Date -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-start">
                Tanggal Mulai <span class="text-red-500">*</span>
              </label>
              <input
                id="sp-start"
                v-model="form.start_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                :class="{ 'border-red-400': formErrors.start_date }"
                @change="clearError('start_date')"
              />
              <p v-if="formErrors.start_date" class="mt-1 text-xs text-red-500">{{ formErrors.start_date }}</p>
            </div>

            <!-- End Date -->
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-end">
                Tanggal Selesai <span class="text-red-500">*</span>
              </label>
              <input
                id="sp-end"
                v-model="form.end_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                :class="{ 'border-red-400': formErrors.end_date }"
                @change="clearError('end_date')"
              />
              <p v-if="formErrors.end_date" class="mt-1 text-xs text-red-500">{{ formErrors.end_date }}</p>
            </div>

            <!-- Deskripsi -->
            <div class="sm:col-span-2">
              <label class="mb-1.5 block text-sm font-medium text-gray-700" for="sp-desc">
                Deskripsi <span class="text-xs font-normal text-gray-400">(opsional)</span>
              </label>
              <textarea
                id="sp-desc"
                v-model="form.description"
                class="w-full resize-y rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none disabled:bg-gray-50 disabled:opacity-60"
                rows="3"
                placeholder="Catatan atau keterangan tambahan tentang periode ini"
                maxlength="1000"
              />
            </div>
          </div>

          <!-- Target Angkatan -->
          <div class="mt-5">
            <label class="mb-2 block text-sm font-medium text-gray-700">
              Target Angkatan Lulus
              <span class="text-xs font-normal text-gray-400">(kosongkan = semua angkatan)</span>
            </label>
            <div v-if="graduationYears.length" class="flex flex-wrap gap-x-6 gap-y-2">
              <label
                v-for="gy in graduationYears"
                :key="gy.id"
                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
              >
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded accent-teal-600"
                  :checked="isYearSelected(gy.id)"
                  :disabled="isReadonly"
                  @change="toggleGraduationYear(gy.id)"
                />
                {{ gy.year }}
              </label>
            </div>
            <p v-else class="text-sm text-gray-400">Data angkatan belum tersedia.</p>
          </div>
        </fieldset>

        <!-- Form actions -->
        <div v-if="!isReadonly" class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
          <button
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            @click="router.push({ name: 'admin.survey-periods.index' })"
          >
            Batal
          </button>
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
            :disabled="saving"
            @click="handleSave"
          >
            <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            {{ isCreate ? 'Buat Periode' : 'Simpan Perubahan' }}
          </button>
        </div>

        <!-- Readonly notice -->
        <div v-else class="mt-5 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
          <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>
          </svg>
          Periode ini sudah ditutup. Data tidak dapat diubah.
        </div>
      </div>

      <!-- ── Tabel Respons (hanya mode edit) ─────────────────────────── -->
      <div v-if="!isCreate" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Card header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
          <h2 class="text-base font-semibold text-gray-900">Respons Alumni</h2>
          <span class="text-sm text-gray-400">Total: {{ responsesTotal }} respons</span>
        </div>

        <!-- Loading -->
        <div v-if="responsesLoading" class="flex justify-center py-8">
          <svg class="h-6 w-6 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
        </div>

        <template v-else>
          <!-- Empty state -->
          <div v-if="responses.length === 0" class="flex flex-col items-center py-12 text-center">
            <svg class="mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm text-gray-500">Belum ada alumni yang mengisi survei pada periode ini.</p>
          </div>

          <!-- Table -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left">
                  <th class="px-4 py-3 font-medium text-gray-600">Alumni</th>
                  <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                  <th class="px-4 py-3 text-center font-medium text-gray-600">Progres</th>
                  <th class="px-4 py-3 font-medium text-gray-600">Dikirim Pada</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="r in responses" :key="r.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <p class="font-medium text-gray-900">{{ r.alumni?.name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ r.alumni?.nim ?? '' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <Badge :variant="badgeVariant(r.status)">{{ r.status }}</Badge>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                      <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-200">
                        <div
                          class="h-full rounded-full bg-teal-500 transition-[width]"
                          :style="{ width: (r.completion_percentage ?? 0) + '%' }"
                        />
                      </div>
                      <span class="min-w-[2.5rem] text-right text-xs tabular-nums text-gray-500">
                        {{ r.completion_percentage ?? 0 }}%
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                    {{ formatDate(r.submitted_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="Math.ceil(responsesTotal / 10) > 1" class="border-t border-gray-100 px-4 py-3">
            <Pagination
              :current-page="responsesPage"
              :last-page="Math.ceil(responsesTotal / 10)"
              :total="responsesTotal"
              @change="loadResponses"
            />
          </div>
        </template>
      </div>
    </template>

    <!-- ── Confirm Modal — v-model binding yang benar ─────────────────── -->
    <ConfirmModal
      v-model="showConfirm"
      :title="confirmTitle"
      :message="confirmMessage"
      @confirm="handleConfirm"
    />
  </div>
</template>
