<script setup>
/**
 * frontend/src/pages/admin/survey-periods/SurveyPeriodIndexPage.vue
 * Halaman daftar periode survei — admin panel.
 * Route  : admin.survey-periods.index — /admin/survey-periods
 * Layout : AdminLayout (wraps via router)
 *
 * Fitur:
 *  - Tabel periode survei dengan filter status & search
 *  - Aksi per baris: Lihat Detail, Aktifkan, Tutup, Kirim Undangan, Hapus
 *  - Modal konfirmasi untuk aksi destructive
 *  - Modal kirim undangan: pilih questionnaire
 *  - Pagination
 *
 * Sesuai 04_ARCHITECTURE.md §2, 05_API.md §admin-survey-periods, 06_UI_UX.md §8
 */
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyPeriodStore } from '@/stores/surveyPeriod'
import { useQuestionnaireStore } from '@/stores/questionnaire'

const router = useRouter()
const store  = useSurveyPeriodStore()
const qStore = useQuestionnaireStore()

// ── Filters ───────────────────────────────────────────────────────────────────
const filters = reactive({ ...store.filters })

async function applyFilters() {
  store.setFilters({ ...filters })
  await store.fetchList(1)
}

async function resetFilters() {
  store.resetFilters()
  Object.assign(filters, store.filters)
  await store.fetchList(1)
}

async function changePage(page) {
  await store.fetchList(page)
}

// ── Confirm modal (Activate / Close / Delete) ─────────────────────────────────
const modal = reactive({
  show         : false,
  title        : '',
  message      : '',
  confirmLabel : 'Ya',
  confirmClass : 'bg-teal-600 hover:bg-teal-700',
  action       : () => {},
})

function openModal({ title, message, confirmLabel, confirmClass, action }) {
  Object.assign(modal, { title, message, confirmLabel, confirmClass, action, show: true })
}

// ── Invitation modal ──────────────────────────────────────────────────────────
const invModal = reactive({
  show            : false,
  periodId        : null,
  periodName      : '',
  questionnaireId : null,
  sending         : false,
  error           : '',
  successMsg      : '',
})

const publishedQuestionnaires = computed(() =>
  qStore.list.filter((q) => q.status === 'published')
)

function openInvModal(period) {
  invModal.periodId        = period.id
  invModal.periodName      = period.name
  invModal.questionnaireId = null
  invModal.error           = ''
  invModal.successMsg      = ''
  invModal.show            = true
}

async function sendInvitations() {
  if (!invModal.questionnaireId) {
    invModal.error = 'Pilih kuesioner terlebih dahulu.'
    return
  }
  invModal.sending = true
  invModal.error   = ''
  try {
    const result = await store.sendInvitations(invModal.periodId, invModal.questionnaireId)
    invModal.successMsg = `Undangan berhasil dikirim ke ${result?.sent ?? 0} alumni.`
    await store.fetchList(store.pagination.currentPage)
  } catch {
    invModal.error = store.error ?? 'Gagal mengirim undangan.'
  } finally {
    invModal.sending = false
  }
}

// ── Row actions ───────────────────────────────────────────────────────────────
function goToDetail(id) {
  router.push({ name: 'admin.survey-periods.detail', params: { id } })
}

function confirmActivate(period) {
  openModal({
    title        : 'Aktifkan Periode Survei?',
    message      : `"${period.name}" akan diaktifkan. Alumni yang sesuai kriteria dapat mulai mengisi survei.`,
    confirmLabel : 'Aktifkan',
    confirmClass : 'bg-green-600 hover:bg-green-700',
    action       : async () => {
      await store.activate(period.id)
      if (!store.error) modal.show = false
    },
  })
}

function confirmClose(period) {
  openModal({
    title        : 'Tutup Periode Survei?',
    message      : `"${period.name}" akan ditutup. Alumni tidak dapat mengisi survei setelah ditutup.`,
    confirmLabel : 'Tutup',
    confirmClass : 'bg-amber-600 hover:bg-amber-700',
    action       : async () => {
      await store.close(period.id)
      if (!store.error) modal.show = false
    },
  })
}

function confirmDelete(period) {
  openModal({
    title        : 'Hapus Periode Survei?',
    message      : `"${period.name}" akan dihapus permanen. Aksi ini tidak dapat dibatalkan.`,
    confirmLabel : 'Hapus',
    confirmClass : 'bg-red-600 hover:bg-red-700',
    action       : async () => {
      await store.destroy(period.id)
      if (!store.error) {
        modal.show = false
        await store.fetchList(store.pagination.currentPage)
      }
    },
  })
}

// ── Badge helpers ─────────────────────────────────────────────────────────────
const STATUS_MAP = {
  draft   : { label: 'Draft',   cls: 'bg-gray-100 text-gray-600'   },
  active  : { label: 'Aktif',   cls: 'bg-green-100 text-green-700' },
  closed  : { label: 'Ditutup', cls: 'bg-red-100 text-red-700'     },
  expired : { label: 'Expired', cls: 'bg-amber-100 text-amber-700' },
}

function statusLabel(s) { return STATUS_MAP[s]?.label ?? s }
function statusClass(s) { return STATUS_MAP[s]?.cls  ?? 'bg-gray-100 text-gray-600' }

function formatDate(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
  }).format(new Date(iso))
}

// ── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  await store.fetchList(1)
  // Muat questionnaire published untuk keperluan dropdown kirim undangan
  if (!qStore.list.length) await qStore.fetchList(1)
})
</script>

<template>
  <div class="space-y-6">

    <!-- ── PAGE HEADER ───────────────────────────────────────────────── -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Periode Survei</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola periode tracer study alumni dan employer.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700"
        @click="router.push({ name: 'admin.survey-periods.create' })"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Buat Periode
      </button>
    </div>

    <!-- ── FILTERS ────────────────────────────────────────────────────── -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Search -->
        <div class="sm:col-span-2">
          <label class="mb-1 block text-xs font-medium text-gray-600">Cari</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Nama periode…"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            @keyup.enter="applyFilters"
          />
        </div>

        <!-- Status -->
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          >
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="active">Aktif</option>
            <option value="closed">Ditutup</option>
          </select>
        </div>

        <!-- Actions -->
        <div class="flex items-end gap-2">
          <button
            class="flex-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
            @click="applyFilters"
          >
            Filter
          </button>
          <button
            v-if="store.hasFilters"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
            title="Reset filter"
            @click="resetFilters"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ── TABLE ──────────────────────────────────────────────────────── -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

      <!-- Skeleton -->
      <div v-if="store.loading" class="space-y-3 p-4">
        <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded bg-gray-100" />
      </div>

      <!-- Empty -->
      <div
        v-else-if="!store.list.length"
        class="flex flex-col items-center justify-center py-16 text-gray-400"
      >
        <svg class="mb-3 h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
        <p class="font-medium">Belum ada periode survei</p>
        <p class="mt-1 text-sm">Klik <strong>Buat Periode</strong> untuk memulai.</p>
      </div>

      <!-- Data -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Nama Periode</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Periode</th>
              <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Target Angkatan</th>
              <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Respons</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="period in store.list"
              :key="period.id"
              class="transition-colors hover:bg-gray-50"
            >
              <!-- Nama -->
              <td class="max-w-xs px-4 py-3">
                <button
                  class="text-left font-medium text-teal-700 hover:underline text-sm"
                  @click="goToDetail(period.id)"
                >
                  {{ period.name }}
                </button>
                <p v-if="period.description" class="mt-0.5 truncate text-xs text-gray-400">
                  {{ period.description }}
                </p>
              </td>

              <!-- Tanggal -->
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                {{ formatDate(period.start_date) }} — {{ formatDate(period.end_date) }}
              </td>

              <!-- Angkatan -->
              <td class="px-4 py-3 text-center">
                <div v-if="period.target_graduation_years?.length" class="flex flex-wrap justify-center gap-1">
                  <span
                    v-for="yr in period.target_graduation_years.slice(0, 3)"
                    :key="yr"
                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700"
                  >
                    {{ yr }}
                  </span>
                  <span
                    v-if="period.target_graduation_years.length > 3"
                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500"
                  >
                    +{{ period.target_graduation_years.length - 3 }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">Semua</span>
              </td>

              <!-- Respons -->
              <td class="px-4 py-3 text-center">
                <span v-if="period.submitted_count != null" class="tabular-nums text-sm text-gray-700">
                  {{ period.submitted_count }}
                  <span v-if="period.total_alumni_count != null" class="text-gray-400">
                    / {{ period.total_alumni_count }}
                  </span>
                </span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>

              <!-- Status -->
              <td class="px-4 py-3">
                <span
                  :class="statusClass(period.status)"
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                >
                  {{ statusLabel(period.status) }}
                </span>
              </td>

              <!-- Aksi -->
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <!-- Detail -->
                  <button
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="Lihat Detail"
                    @click="goToDetail(period.id)"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.574-3.007-9.964-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </button>

                  <!-- Kirim Undangan (hanya active) -->
                  <button
                    v-if="period.status === 'active'"
                    class="rounded p-1.5 text-gray-400 hover:bg-teal-50 hover:text-teal-600"
                    title="Kirim Undangan"
                    @click="openInvModal(period)"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                  </button>

                  <!-- Aktifkan (hanya draft) -->
                  <button
                    v-if="period.status === 'draft'"
                    class="rounded p-1.5 text-gray-400 hover:bg-green-50 hover:text-green-600"
                    title="Aktifkan"
                    @click="confirmActivate(period)"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                  </button>

                  <!-- Tutup (hanya active) -->
                  <button
                    v-if="period.status === 'active'"
                    class="rounded p-1.5 text-gray-400 hover:bg-amber-50 hover:text-amber-600"
                    title="Tutup Periode"
                    @click="confirmClose(period)"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" />
                    </svg>
                  </button>

                  <!-- Hapus (hanya draft) -->
                  <button
                    v-if="period.status === 'draft'"
                    class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                    title="Hapus"
                    @click="confirmDelete(period)"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="store.pagination.total > 0"
        class="flex items-center justify-between border-t border-gray-200 px-4 py-3"
      >
        <p class="text-sm text-gray-500">
          Menampilkan
          {{ (store.pagination.currentPage - 1) * store.pagination.perPage + 1 }}–{{
            Math.min(store.pagination.currentPage * store.pagination.perPage, store.pagination.total)
          }}
          dari {{ store.pagination.total }} periode
        </p>
        <div class="flex gap-2">
          <button
            :disabled="store.pagination.currentPage <= 1"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="changePage(store.pagination.currentPage - 1)"
          >
            &laquo; Prev
          </button>
          <button
            :disabled="store.pagination.currentPage >= store.pagination.lastPage"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="changePage(store.pagination.currentPage + 1)"
          >
            Next &raquo;
          </button>
        </div>
      </div>
    </div>

    <!-- ── CONFIRM MODAL ──────────────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="modal.show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="modal.show = false"
      >
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
          <h2 class="mb-2 text-base font-semibold text-gray-900">{{ modal.title }}</h2>
          <p class="mb-6 text-sm text-gray-500">{{ modal.message }}</p>
          <p v-if="store.error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ store.error }}
          </p>
          <div class="flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
              @click="modal.show = false"
            >
              Batal
            </button>
            <button
              :disabled="store.submitting"
              :class="modal.confirmClass"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
              @click="modal.action"
            >
              <span v-if="store.submitting" class="inline-flex items-center gap-1">
                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                Memproses…
              </span>
              <span v-else>{{ modal.confirmLabel }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── SEND INVITATION MODAL ──────────────────────────────────────── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="invModal.show"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
          @click.self="invModal.show = false"
        >
          <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="inv-modal-title"
          >
            <h2 id="inv-modal-title" class="mb-1 text-base font-semibold text-gray-900">
              Kirim Undangan Survei
            </h2>
            <p class="mb-4 text-sm text-gray-500">
              Periode: <strong>{{ invModal.periodName }}</strong>
            </p>

            <!-- Success -->
            <div
              v-if="invModal.successMsg"
              class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
            >
              {{ invModal.successMsg }}
            </div>

            <template v-else>
              <!-- Pilih Kuesioner -->
              <div class="mb-4">
                <label class="mb-1 block text-xs font-medium text-gray-700">
                  Kuesioner <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="invModal.questionnaireId"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                  <option :value="null" disabled>Pilih kuesioner yang akan digunakan…</option>
                  <option
                    v-for="q in publishedQuestionnaires"
                    :key="q.id"
                    :value="q.id"
                  >
                    {{ q.title }} ({{ q.type }})
                  </option>
                </select>
                <p v-if="!publishedQuestionnaires.length" class="mt-1 text-xs text-amber-600">
                  Tidak ada kuesioner berstatus Published. Publish kuesioner terlebih dahulu.
                </p>
              </div>

              <!-- Error -->
              <p v-if="invModal.error" class="mb-4 text-sm font-medium text-red-600">
                {{ invModal.error }}
              </p>

              <div class="flex justify-end gap-3">
                <button
                  class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                  @click="invModal.show = false"
                >
                  Batal
                </button>
                <button
                  :disabled="invModal.sending || !invModal.questionnaireId"
                  class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
                  @click="sendInvitations"
                >
                  <span v-if="invModal.sending" class="inline-flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                    </svg>
                    Mengirim…
                  </span>
                  <span v-else>Kirim Undangan</span>
                </button>
              </div>
            </template>

            <!-- Tombol tutup jika sukses -->
            <div v-if="invModal.successMsg" class="flex justify-end">
              <button
                class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                @click="invModal.show = false"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>
