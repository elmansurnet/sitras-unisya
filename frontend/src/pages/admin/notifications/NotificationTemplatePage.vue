<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Template Notifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola template pesan untuk berbagai event notifikasi sistem.</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
        @click="openCreate"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat Template
      </button>
    </div>

    <!-- Filter Row -->
    <div class="flex flex-wrap gap-3">
      <select
        v-model="filterType"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
        @change="applyFilter"
      >
        <option value="">Semua Channel</option>
        <option value="whatsapp">WhatsApp</option>
        <option value="email">Email</option>
      </select>

      <select
        v-model="filterEvent"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
        @change="applyFilter"
      >
        <option value="">Semua Event</option>
        <option value="survey_invitation">Undangan Survei</option>
        <option value="survey_reminder">Reminder Survei</option>
        <option value="otp_login">OTP Login</option>
        <option value="employer_survey_invitation">Undangan Employer</option>
      </select>

      <button
        v-if="filterType || filterEvent"
        type="button"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 transition-colors"
        @click="resetFilter"
      >
        Reset
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="notificationStore.templateLoading && !templates.length" class="space-y-3">
      <div v-for="n in 5" :key="n" class="h-14 rounded-xl bg-gray-100 animate-pulse" />
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!notificationStore.templateLoading && !templates.length"
      class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center"
    >
      <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z" />
      </svg>
      <h3 class="mt-4 text-sm font-semibold text-gray-900">Belum ada template notifikasi</h3>
      <p class="mt-1 text-sm text-gray-500">Buat template pesan untuk undangan survei, OTP, dan reminder.</p>
      <button
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors"
        @click="openCreate"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat Template Pertama
      </button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-card">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama Template</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Channel</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Event</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Diperbarui</th>
            <th scope="col" class="relative px-4 py-3"><span class="sr-only">Aksi</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-for="template in templates"
            :key="template.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ template.name }}</div>
              <div class="mt-0.5 text-xs text-gray-400">
                {{ template.variables?.length ?? 0 }} variabel
              </div>
            </td>
            <td class="px-4 py-4">
              <span :class="channelBadgeClass(template.type)" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium">
                <span v-if="template.type === 'whatsapp'">💬</span>
                <span v-else>✉️</span>
                {{ template.type === 'whatsapp' ? 'WhatsApp' : 'Email' }}
              </span>
            </td>
            <td class="px-4 py-4">
              <span class="text-sm text-gray-600">{{ eventLabel(template.event) }}</span>
            </td>
            <td class="px-4 py-4">
              <span :class="template.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-4 py-4 text-sm text-gray-500">
              {{ formatDate(template.updated_at) }}
            </td>
            <td class="px-4 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                  title="Lihat / Edit"
                  @click="openEdit(template)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <span class="sr-only">Edit</span>
                </button>
                <button
                  type="button"
                  class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                  title="Hapus"
                  @click="confirmDelete(template)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <span class="sr-only">Hapus</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ─── Form Modal (Create / Edit) ─── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showForm"
          class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 px-4 py-8"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="formMode === 'create' ? 'modal-create-title' : 'modal-edit-title'"
          @mousedown.self="closeForm"
        >
          <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
              <h2 :id="formMode === 'create' ? 'modal-create-title' : 'modal-edit-title'" class="text-base font-semibold text-gray-900">
                {{ formMode === 'create' ? 'Buat Template Baru' : 'Edit Template' }}
              </h2>
              <button
                type="button"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                aria-label="Tutup form"
                @click="closeForm"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <form class="space-y-5 px-6 py-5" @submit.prevent="submitForm">
              <!-- Nama -->
              <div>
                <label for="f-name" class="block text-sm font-medium text-gray-700">Nama Template <span class="text-red-500">*</span></label>
                <input
                  id="f-name"
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm"
                  :class="formErrors.name ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'"
                  placeholder="cth. Undangan Survei Alumni — WhatsApp"
                  required
                />
                <p v-if="formErrors.name" class="mt-1 text-xs text-red-600">{{ formErrors.name[0] }}</p>
              </div>

              <!-- Channel + Event -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="f-type" class="block text-sm font-medium text-gray-700">Channel <span class="text-red-500">*</span></label>
                  <select id="f-type" v-model="form.type" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    <option value="">Pilih channel</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                  </select>
                  <p v-if="formErrors.type" class="mt-1 text-xs text-red-600">{{ formErrors.type[0] }}</p>
                </div>
                <div>
                  <label for="f-event" class="block text-sm font-medium text-gray-700">Event <span class="text-red-500">*</span></label>
                  <select id="f-event" v-model="form.event" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    <option value="">Pilih event</option>
                    <option value="survey_invitation">Undangan Survei</option>
                    <option value="survey_reminder">Reminder Survei</option>
                    <option value="otp_login">OTP Login</option>
                    <option value="employer_survey_invitation">Undangan Employer</option>
                  </select>
                  <p v-if="formErrors.event" class="mt-1 text-xs text-red-600">{{ formErrors.event[0] }}</p>
                </div>
              </div>

              <!-- Subject (email only) -->
              <div v-if="form.type === 'email'">
                <label for="f-subject" class="block text-sm font-medium text-gray-700">Subject Email <span class="text-red-500">*</span></label>
                <input
                  id="f-subject"
                  v-model="form.subject"
                  type="text"
                  class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="cth. Undangan Survei Tracer Study {{period_name}}"
                />
                <p v-if="formErrors.subject" class="mt-1 text-xs text-red-600">{{ formErrors.subject[0] }}</p>
              </div>

              <!-- Body -->
              <div>
                <div class="flex items-center justify-between">
                  <label for="f-body" class="block text-sm font-medium text-gray-700">Isi Pesan <span class="text-red-500">*</span></label>
                  <button
                    type="button"
                    class="text-xs text-primary-600 hover:underline"
                    @click="togglePreview"
                  >
                    {{ showPreview ? 'Tutup Preview' : 'Lihat Preview' }}
                  </button>
                </div>

                <textarea
                  v-if="!showPreview"
                  id="f-body"
                  v-model="form.body"
                  rows="8"
                  class="mt-1 block w-full rounded-lg border px-3 py-2 font-mono text-sm"
                  :class="formErrors.body ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'"
                  placeholder="Assalamu'alaikum {{alumni_name}},\n\nAnda diundang mengisi survei..."
                  required
                />
                <!-- Preview rendered -->
                <div
                  v-else
                  class="mt-1 min-h-[200px] rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap"
                  v-html="renderedPreview"
                />

                <p v-if="formErrors.body" class="mt-1 text-xs text-red-600">{{ formErrors.body[0] }}</p>
              </div>

              <!-- Variabel tersedia -->
              <div v-if="availableVars.length">
                <p class="text-xs font-medium text-gray-500 mb-1.5">Variabel tersedia — klik untuk sisipkan ke body:</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="v in availableVars"
                    :key="v.key"
                    type="button"
                    class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-mono text-primary-700 hover:bg-primary-100 transition-colors"
                    :title="v.description"
                    @click="insertVariable(v.key)"
                  >
                    {{ '{{' + v.key + '}}' }}
                  </button>
                </div>
              </div>

              <!-- Status aktif -->
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form.is_active"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                  :class="form.is_active ? 'bg-primary-600' : 'bg-gray-200'"
                  @click="form.is_active = !form.is_active"
                >
                  <span
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform"
                    :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
                  />
                </button>
                <span class="text-sm text-gray-700">Template aktif</span>
              </div>

              <!-- Footer -->
              <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button
                  type="button"
                  class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                  @click="closeForm"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  :disabled="notificationStore.templateLoading"
                  class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <svg v-if="notificationStore.templateLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                  </svg>
                  {{ formMode === 'create' ? 'Simpan Template' : 'Perbarui Template' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ─── Confirm Delete Modal ─── -->
    <ConfirmModal
      v-model="showDeleteConfirm"
      title="Hapus Template"
      :message="`Hapus template &quot;${deleteTarget?.name}&quot;? Tindakan ini tidak dapat dibatalkan.`"
      confirm-text="Ya, Hapus"
      confirm-variant="danger"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

// ─── Store & Composables ───────────────────────────────────────────────────
const notificationStore = useNotificationStore()
const { showToast }     = useToast()

// ─── Filter ───────────────────────────────────────────────────────────────
const filterType  = ref('')
const filterEvent = ref('')

const templates = computed(() => notificationStore.templates)

async function applyFilter() {
  const params = {}
  if (filterType.value)  params.type  = filterType.value
  if (filterEvent.value) params.event = filterEvent.value
  await notificationStore.fetchTemplates(params)
}

async function resetFilter() {
  filterType.value  = ''
  filterEvent.value = ''
  await notificationStore.fetchTemplates()
}

// ─── Form State ───────────────────────────────────────────────────────────
const showForm   = ref(false)
const formMode   = ref('create')   // 'create' | 'edit'
const formEditId = ref(null)
const showPreview = ref(false)

const form = ref({
  name      : '',
  type      : '',
  event     : '',
  subject   : '',
  body      : '',
  variables : {},
  is_active : true,
})

const formErrors = ref({})

// ─── Variabel tersedia per event ──────────────────────────────────────────
const EVENT_VARS = {
  survey_invitation: [
    { key: 'alumni_name',  description: 'Nama lengkap alumni' },
    { key: 'period_name',  description: 'Nama periode survei' },
    { key: 'survey_url',   description: 'URL link survei alumni' },
  ],
  survey_reminder: [
    { key: 'alumni_name',  description: 'Nama lengkap alumni' },
    { key: 'period_name',  description: 'Nama periode survei' },
    { key: 'survey_url',   description: 'URL link survei alumni' },
    { key: 'end_date',     description: 'Tanggal akhir periode' },
  ],
  otp_login: [
    { key: 'alumni_name',        description: 'Nama lengkap alumni' },
    { key: 'otp_code',           description: 'Kode OTP 6 digit' },
    { key: 'expires_in_minutes', description: 'Masa berlaku OTP (menit)' },
  ],
  employer_survey_invitation: [
    { key: 'company_name',          description: 'Nama perusahaan' },
    { key: 'contact_person_name',   description: 'Nama kontak employer' },
    { key: 'survey_url',            description: 'URL link survei employer' },
    { key: 'expires_at',            description: 'Tanggal kadaluwarsa link' },
  ],
}

const availableVars = computed(() => EVENT_VARS[form.value.event] ?? [])

// ─── Preview ──────────────────────────────────────────────────────────────
const SAMPLE_DATA = {
  alumni_name          : 'Ahmad Fauzi',
  period_name          : 'Tracer Study 2024',
  survey_url           : 'https://tracer.unisya.ac.id/alumni/survey',
  end_date             : '31 Maret 2024',
  otp_code             : '847291',
  expires_in_minutes   : '5',
  company_name         : 'PT Maju Bersama',
  contact_person_name  : 'Budi Santoso',
  expires_at           : '15 Februari 2024',
}

const renderedPreview = computed(() => {
  let text = form.value.body || ''
  for (const [key, val] of Object.entries(SAMPLE_DATA)) {
    text = text.replaceAll(`{{${key}}}`, `<strong class="text-primary-700">${val}</strong>`)
  }
  // highlight variabel yang tidak dikenal
  text = text.replace(/\{\{(\w+)\}\}/g, '<span class="bg-yellow-100 text-yellow-800 rounded px-1">{{$1}}</span>')
  return text
})

function togglePreview() {
  showPreview.value = !showPreview.value
}

function insertVariable(key) {
  form.value.body += `{{${key}}}`
  showPreview.value = false
}

// ─── Open / Close ─────────────────────────────────────────────────────────
function openCreate() {
  formMode.value   = 'create'
  formEditId.value = null
  formErrors.value = {}
  showPreview.value = false
  form.value = { name: '', type: '', event: '', subject: '', body: '', variables: {}, is_active: true }
  showForm.value = true
}

function openEdit(template) {
  formMode.value   = 'edit'
  formEditId.value = template.id
  formErrors.value = {}
  showPreview.value = false
  form.value = {
    name      : template.name,
    type      : template.type,
    event     : template.event,
    subject   : template.subject ?? '',
    body      : template.body ?? '',
    variables : template.variables ?? {},
    is_active : template.is_active,
  }
  showForm.value = true
}

function closeForm() {
  showForm.value   = false
  formErrors.value = {}
  showPreview.value = false
}

// ─── Submit ───────────────────────────────────────────────────────────────
async function submitForm() {
  formErrors.value = {}

  // Bangun variables object dari availableVars
  const variablesMap = {}
  availableVars.value.forEach((v) => {
    variablesMap[v.key] = v.description
  })

  const payload = { ...form.value, variables: variablesMap }
  if (form.value.type !== 'email') delete payload.subject

  try {
    if (formMode.value === 'create') {
      await notificationStore.createTemplate(payload)
      showToast('Template notifikasi berhasil dibuat', 'success')
    } else {
      await notificationStore.updateTemplate(formEditId.value, payload)
      showToast('Template notifikasi berhasil diperbarui', 'success')
    }
    closeForm()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors ?? {}
    } else {
      showToast(err.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.', 'error')
    }
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false)
const deleteTarget      = ref(null)

function confirmDelete(template) {
  deleteTarget.value      = template
  showDeleteConfirm.value = true
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await notificationStore.deleteTemplate(deleteTarget.value.id)
    showToast('Template berhasil dihapus', 'success')
  } catch {
    showToast('Gagal menghapus template', 'error')
  } finally {
    deleteTarget.value      = null
    showDeleteConfirm.value = false
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function channelBadgeClass(type) {
  return type === 'whatsapp'
    ? 'bg-green-100 text-green-700'
    : 'bg-blue-100 text-blue-700'
}

const EVENT_LABELS = {
  survey_invitation          : 'Undangan Survei',
  survey_reminder            : 'Reminder Survei',
  otp_login                  : 'OTP Login',
  employer_survey_invitation : 'Undangan Employer',
}

function eventLabel(event) {
  return EVENT_LABELS[event] ?? event
}

function formatDate(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// ─── Init ─────────────────────────────────────────────────────────────────
onMounted(() => {
  notificationStore.fetchTemplates()
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 150ms ease;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 150ms ease, opacity 150ms ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .relative {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to .relative {
  opacity: 0;
  transform: scale(0.95);
}
</style>
