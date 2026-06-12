<script setup>
/**
 * frontend/src/pages/admin/survey-periods/SurveyPeriodDetailPage.vue
 * Halaman create / edit periode survei — admin panel.
 * Route  :
 *   admin.survey-periods.create — /admin/survey-periods/create  (params.id = undefined)
 *   admin.survey-periods.detail — /admin/survey-periods/:id     (params.id = angka)
 * Layout : AdminLayout (wraps via router)
 *
 * Form fields (sesuai 02_DATABASE.md survey_periods):
 *   name, description, start_date, end_date,
 *   target_graduation_years (multi-select checkboxes),
 *   quota (opsional)
 *
 * Saat mode edit:
 *   - Tampilkan statistik respons (read-only) di panel samping
 *   - Tombol Aktifkan / Tutup tersedia jika status memungkinkan
 *
 * Sesuai 04_ARCHITECTURE.md §2, 05_API.md §admin-survey-periods, 02_DATABASE.md
 */
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSurveyPeriodStore } from '@/stores/surveyPeriod'

const route  = useRoute()
const router = useRouter()
const store  = useSurveyPeriodStore()

// ── Mode ─────────────────────────────────────────────────────────────────────
const isEdit    = computed(() => !!route.params.id && route.params.id !== 'create')
const periodId  = computed(() => isEdit.value ? Number(route.params.id) : null)
const pageTitle = computed(() => isEdit.value ? 'Edit Periode Survei' : 'Buat Periode Survei')

// ── Form state ────────────────────────────────────────────────────────────────
const form = reactive({
  name                    : '',
  description             : '',
  start_date              : '',
  end_date                : '',
  target_graduation_years : [],  // array of number
  quota                   : '',
})

const errors       = ref({})
const saveSuccess  = ref(false)
const fetchError   = ref(null)

// ── Tahun lulus untuk multi-select ─────────────────────────────────────────
// Buat pilihan: 10 tahun ke belakang sampai tahun sekarang
const CURRENT_YEAR   = new Date().getFullYear()
const YEAR_OPTIONS   = Array.from({ length: 15 }, (_, i) => CURRENT_YEAR - i)

function toggleYear(yr) {
  const idx = form.target_graduation_years.indexOf(yr)
  if (idx === -1) form.target_graduation_years.push(yr)
  else form.target_graduation_years.splice(idx, 1)
}

function isYearSelected(yr) {
  return form.target_graduation_years.includes(yr)
}

function selectAllYears()  { form.target_graduation_years = [...YEAR_OPTIONS] }
function clearAllYears()   { form.target_graduation_years = [] }

// ── Computed dari store ───────────────────────────────────────────────────────
const current    = computed(() => store.current)
const submitting = computed(() => store.submitting)
const loading    = computed(() => store.loading)

// ── Hydrate form dari data yang diambil ──────────────────────────────────────
function hydrate(data) {
  form.name                    = data.name ?? ''
  form.description             = data.description ?? ''
  form.start_date              = data.start_date ? data.start_date.slice(0, 10) : ''
  form.end_date                = data.end_date   ? data.end_date.slice(0, 10)   : ''
  form.target_graduation_years = data.target_graduation_years ?? []
  form.quota                   = data.quota ?? ''
}

// ── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  store.clearCurrent()
  if (isEdit.value) {
    try {
      const data = await store.fetchDetail(periodId.value)
      hydrate(data)
    } catch {
      fetchError.value = store.error ?? 'Gagal memuat data periode survei.'
    }
  }
})

// ── Validasi ─────────────────────────────────────────────────────────────────
function validate() {
  const e = {}
  if (!form.name.trim())       e.name       = 'Nama periode wajib diisi.'
  if (!form.start_date)        e.start_date = 'Tanggal mulai wajib diisi.'
  if (!form.end_date)          e.end_date   = 'Tanggal selesai wajib diisi.'
  if (form.start_date && form.end_date && form.end_date < form.start_date) {
    e.end_date = 'Tanggal selesai harus setelah tanggal mulai.'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

// ── Submit ───────────────────────────────────────────────────────────────────
async function handleSubmit() {
  saveSuccess.value = false
  if (!validate()) return

  const payload = {
    name                    : form.name.trim(),
    description             : form.description.trim() || null,
    start_date              : form.start_date,
    end_date                : form.end_date,
    target_graduation_years : form.target_graduation_years,
    quota                   : form.quota ? Number(form.quota) : null,
  }

  try {
    if (isEdit.value) {
      await store.update(periodId.value, payload)
      saveSuccess.value = true
      setTimeout(() => (saveSuccess.value = false), 3000)
    } else {
      const created = await store.create(payload)
      // Redirect ke halaman detail yang baru dibuat
      router.replace({
        name  : 'admin.survey-periods.detail',
        params: { id: created.id },
      })
    }
  } catch {
    // Error sudah ada di store.error
  }
}

// ── Aksi status dari halaman detail ──────────────────────────────────────────
const confirmModal = reactive({
  show: false, title: '', message: '',
  confirmLabel: '', confirmClass: 'bg-teal-600 hover:bg-teal-700',
  action: () => {},
})

function openConfirm({ title, message, confirmLabel, confirmClass, action }) {
  Object.assign(confirmModal, { title, message, confirmLabel, confirmClass, action, show: true })
}

function doActivate() {
  openConfirm({
    title        : 'Aktifkan Periode Survei?',
    message      : 'Periode akan diaktifkan. Alumni yang sesuai dapat mulai mengisi survei.',
    confirmLabel : 'Aktifkan',
    confirmClass : 'bg-green-600 hover:bg-green-700',
    action       : async () => {
      await store.activate(periodId.value)
      if (!store.error) {
        confirmModal.show = false
        await store.fetchDetail(periodId.value)
        hydrate(store.current)
      }
    },
  })
}

function doClose() {
  openConfirm({
    title        : 'Tutup Periode Survei?',
    message      : 'Periode akan ditutup. Alumni tidak dapat mengisi survei setelah ini.',
    confirmLabel : 'Tutup',
    confirmClass : 'bg-amber-600 hover:bg-amber-700',
    action       : async () => {
      await store.close(periodId.value)
      if (!store.error) {
        confirmModal.show = false
        await store.fetchDetail(periodId.value)
        hydrate(store.current)
      }
    },
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const STATUS_MAP = {
  draft   : { label: 'Draft',   cls: 'bg-gray-100 text-gray-600'   },
  active  : { label: 'Aktif',   cls: 'bg-green-100 text-green-700' },
  closed  : { label: 'Ditutup', cls: 'bg-red-100 text-red-700'     },
  expired : { label: 'Expired', cls: 'bg-amber-100 text-amber-700' },
}
const statusLabel = (s) => STATUS_MAP[s]?.label ?? s
const statusClass = (s) => STATUS_MAP[s]?.cls   ?? 'bg-gray-100 text-gray-600'

function formatDatetime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <div class="space-y-6">

    <!-- ── BREADCRUMB / BACK ──────────────────────────────────────────── -->
    <div class="flex items-center gap-3">
      <button
        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
        @click="router.push({ name: 'admin.survey-periods.index' })"
      >
        &larr; Kembali
      </button>
      <h1 class="text-xl font-semibold text-gray-900">{{ pageTitle }}</h1>
      <!-- Status badge (edit mode) -->
      <span
        v-if="isEdit && current"
        :class="statusClass(current.status)"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
      >
        {{ statusLabel(current.status) }}
      </span>
    </div>

    <!-- ── LOADING ───────────────────────────────────────────────────── -->
    <div v-if="loading && !current && isEdit" class="space-y-4">
      <div v-for="i in 4" :key="i" class="h-10 animate-pulse rounded-lg bg-gray-100" />
    </div>

    <!-- ── FETCH ERROR ────────────────────────────────────────────────── -->
    <div
      v-else-if="fetchError"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-6 text-center text-sm text-red-700"
    >
      {{ fetchError }}
    </div>

    <!-- ── MAIN CONTENT ──────────────────────────────────────────────── -->
    <template v-else>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- ── FORM (kiri 2/3) ───────────────────────────────────────── -->
        <div class="lg:col-span-2">
          <form class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="handleSubmit">

            <!-- Success banner -->
            <div
              v-if="saveSuccess"
              class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
            >
              ✅ Perubahan berhasil disimpan.
            </div>

            <!-- Server error -->
            <div
              v-if="store.error && !submitting"
              class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
              {{ store.error }}
            </div>

            <!-- Nama Periode -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">
                Nama Periode <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Contoh: Tracer Study Angkatan 2021"
                :class="[
                  'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1',
                  errors.name
                    ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
                    : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500',
                ]"
              />
              <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
            </div>

            <!-- Deskripsi -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Penjelasan singkat tentang periode survei ini…"
                class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              />
            </div>

            <!-- Tanggal Mulai & Selesai -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                  Tanggal Mulai <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.start_date"
                  type="date"
                  :class="[
                    'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1',
                    errors.start_date
                      ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
                      : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500',
                  ]"
                />
                <p v-if="errors.start_date" class="mt-1 text-xs text-red-600">{{ errors.start_date }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                  Tanggal Selesai <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.end_date"
                  type="date"
                  :class="[
                    'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-1',
                    errors.end_date
                      ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
                      : 'border-gray-300 focus:border-teal-500 focus:ring-teal-500',
                  ]"
                />
                <p v-if="errors.end_date" class="mt-1 text-xs text-red-600">{{ errors.end_date }}</p>
              </div>
            </div>

            <!-- Target Angkatan Lulus -->
            <div>
              <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Target Angkatan Lulus</label>
                <div class="flex gap-2">
                  <button type="button" class="text-xs text-teal-600 hover:underline" @click="selectAllYears">Pilih Semua</button>
                  <span class="text-gray-300">|</span>
                  <button type="button" class="text-xs text-gray-500 hover:underline" @click="clearAllYears">Hapus Semua</button>
                </div>
              </div>
              <p class="mb-3 text-xs text-gray-400">Kosongkan untuk menargetkan semua angkatan.</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="yr in YEAR_OPTIONS"
                  :key="yr"
                  type="button"
                  :class="[
                    'rounded-full px-3 py-1 text-xs font-medium transition-colors',
                    isYearSelected(yr)
                      ? 'bg-teal-600 text-white hover:bg-teal-700'
                      : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50',
                  ]"
                  @click="toggleYear(yr)"
                >
                  {{ yr }}
                </button>
              </div>
            </div>

            <!-- Kuota (opsional) -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Kuota Responden</label>
              <p class="mb-2 text-xs text-gray-400">Opsional. Kosongkan jika tidak ada batasan kuota.</p>
              <input
                v-model="form.quota"
                type="number"
                min="1"
                placeholder="Contoh: 500"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 sm:w-48"
              />
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
              <button
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                @click="router.push({ name: 'admin.survey-periods.index' })"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
              >
                <svg v-if="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                {{ submitting ? 'Menyimpan…' : (isEdit ? 'Simpan Perubahan' : 'Buat Periode') }}
              </button>
            </div>
          </form>
        </div>

        <!-- ── PANEL KANAN (statistik + aksi status) ──────────────────── -->
        <div class="space-y-4">

          <!-- Aksi Status (hanya edit mode) -->
          <div v-if="isEdit && current" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Aksi Periode</h3>
            <div class="space-y-2">
              <button
                v-if="current.status === 'draft'"
                class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                :disabled="submitting"
                @click="doActivate"
              >
                ▶ Aktifkan Periode
              </button>
              <button
                v-if="current.status === 'active'"
                class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50"
                :disabled="submitting"
                @click="doClose"
              >
                ■ Tutup Periode
              </button>
              <p
                v-if="current.status === 'closed'"
                class="text-xs text-gray-400 text-center py-2"
              >
                Periode sudah ditutup dan tidak dapat diubah.
              </p>
            </div>
          </div>

          <!-- Statistik Respons (hanya edit mode) -->
          <div v-if="isEdit && current" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Statistik Respons</h3>
            <dl class="space-y-3">
              <div class="flex justify-between text-sm">
                <dt class="text-gray-500">Total Alumni Target</dt>
                <dd class="font-medium text-gray-900 tabular-nums">
                  {{ current.total_alumni_count ?? '—' }}
                </dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-500">Diundang</dt>
                <dd class="font-medium text-gray-900 tabular-nums">
                  {{ current.invited_count ?? '—' }}
                </dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-500">Sudah Submit</dt>
                <dd class="font-medium text-green-700 tabular-nums">
                  {{ current.submitted_count ?? '—' }}
                </dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-500">Belum Submit</dt>
                <dd class="font-medium text-amber-600 tabular-nums">
                  {{ current.in_progress_count ?? '—' }}
                </dd>
              </div>

              <!-- Progress bar -->
              <div v-if="current.submitted_count != null && current.total_alumni_count">
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                  <div
                    class="h-full rounded-full bg-teal-500 transition-all"
                    :style="{ width: `${Math.round((current.submitted_count / current.total_alumni_count) * 100)}%` }"
                  />
                </div>
                <p class="mt-1 text-right text-xs text-gray-400">
                  {{ Math.round((current.submitted_count / current.total_alumni_count) * 100) }}% selesai
                </p>
              </div>
            </dl>

            <!-- Meta -->
            <div class="mt-4 border-t border-gray-100 pt-3 space-y-1 text-xs text-gray-400">
              <p>Dibuat: {{ formatDatetime(current.created_at) }}</p>
              <p v-if="current.updated_at">Diperbarui: {{ formatDatetime(current.updated_at) }}</p>
            </div>
          </div>

          <!-- Info card (create mode) -->
          <div v-if="!isEdit" class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <h3 class="mb-2 text-sm font-semibold text-blue-800">Petunjuk</h3>
            <ul class="space-y-1.5 text-xs text-blue-700">
              <li>• Isi nama dan tanggal periode survei.</li>
              <li>• Pilih angkatan lulus yang menjadi target, atau kosongkan untuk semua angkatan.</li>
              <li>• Setelah disimpan, aktifkan periode untuk memulai pengumpulan respons.</li>
              <li>• Kirim undangan via menu <strong>Kirim Undangan</strong> di halaman daftar.</li>
            </ul>
          </div>

        </div>
      </div>
    </template>

    <!-- ── CONFIRM MODAL ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="confirmModal.show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="confirmModal.show = false"
      >
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
          <h2 class="mb-2 text-base font-semibold text-gray-900">{{ confirmModal.title }}</h2>
          <p class="mb-6 text-sm text-gray-500">{{ confirmModal.message }}</p>
          <p v-if="store.error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ store.error }}
          </p>
          <div class="flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
              @click="confirmModal.show = false"
            >
              Batal
            </button>
            <button
              :disabled="submitting"
              :class="confirmModal.confirmClass"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
              @click="confirmModal.action"
            >
              <span v-if="submitting" class="inline-flex items-center gap-1">
                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                Memproses…
              </span>
              <span v-else>{{ confirmModal.confirmLabel }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>
