<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

// ─── Store & Composables ────────────────────────────────────────────────────
const store     = useNotificationStore()
const { showToast } = useToast()
const { confirm }   = useConfirm()

// ─── State Lokal ────────────────────────────────────────────────────────────
const filterType  = ref('')
const filterEvent = ref('')

// Modal CRUD
const showModal     = ref(false)
const modalMode     = ref('create') // 'create' | 'edit'
const modalLoading  = ref(false)
const formErrors    = ref({})
const showPreview   = ref(false)

// Modal detail (view)
const showDetailModal = ref(false)
const detailTemplate  = ref(null)

// Form
const emptyForm = () => ({
  id          : null,
  name        : '',
  type        : 'whatsapp',
  event       : '',
  subject     : '',
  body        : '',
  variables   : '',   // teks JSON atau key: desc, satu per baris
  is_active   : true,
})
const form = reactive(emptyForm())

// ─── Computed ────────────────────────────────────────────────────────────────
const EVENT_OPTIONS = [
  { value: 'survey_invitation',          label: 'Undangan Survei' },
  { value: 'survey_reminder',            label: 'Reminder Survei' },
  { value: 'otp_login',                  label: 'OTP Login' },
  { value: 'employer_survey_invitation', label: 'Undangan Survei Employer' },
]

const TYPE_OPTIONS = [
  { value: '',          label: 'Semua Channel' },
  { value: 'whatsapp',  label: 'WhatsApp' },
  { value: 'email',     label: 'Email' },
]

const EVENT_FILTER_OPTIONS = [
  { value: '',                             label: 'Semua Event' },
  ...EVENT_OPTIONS,
]

/** Render template body dengan placeholder data contoh */
const previewRendered = computed(() => {
  const sampleData = {
    '{{alumni_name}}'          : 'Ahmad Fauzi',
    '{{period_name}}'          : 'Tracer Study 2024',
    '{{survey_url}}'           : 'https://tracer.unisya.ac.id/alumni/survey',
    '{{otp_code}}'             : '847291',
    '{{expires_in_minutes}}'   : '5',
    '{{company_name}}'         : 'PT Maju Bersama',
    '{{contact_person_name}}'  : 'Budi Santoso',
  }
  let rendered = form.body
  for (const [key, val] of Object.entries(sampleData)) {
    rendered = rendered.replaceAll(key, `<strong class="text-primary-600">${val}</strong>`)
  }
  // highlight sisa variabel yang belum diisi
  rendered = rendered.replace(
    /\{\{[^}]+\}\}/g,
    (m) => `<span class="bg-yellow-100 text-yellow-800 rounded px-0.5">${m}</span>`
  )
  return rendered.replace(/\n/g, '<br>')
})

/** Highlight variabel di editor body */
function highlightVariables(text) {
  return text.replace(
    /\{\{[^}]+\}\}/g,
    (m) => `<mark class="bg-primary-100 text-primary-700 rounded">${m}</mark>`
  )
}

// ─── Kolom DataTable ─────────────────────────────────────────────────────────
const columns = [
  { key: 'name',       label: 'Nama Template',   sortable: true },
  { key: 'type',       label: 'Channel',          sortable: true, width: '120px' },
  { key: 'event',      label: 'Event',            sortable: true },
  { key: 'is_active',  label: 'Status',           width: '100px' },
  { key: 'updated_at', label: 'Diperbarui',       sortable: true, width: '160px' },
  { key: 'actions',    label: 'Aksi',             width: '140px' },
]

// ─── Fetch ───────────────────────────────────────────────────────────────────
async function loadTemplates() {
  const params = {}
  if (filterType.value)  params.type  = filterType.value
  if (filterEvent.value) params.event = filterEvent.value
  try {
    await store.fetchTemplates(params)
  } catch {
    showToast('Gagal memuat template notifikasi.', 'error')
  }
}

onMounted(loadTemplates)

// ─── Filter ──────────────────────────────────────────────────────────────────
function applyFilter() { loadTemplates() }
function resetFilter() {
  filterType.value  = ''
  filterEvent.value = ''
  loadTemplates()
}

// ─── Modal CRUD ──────────────────────────────────────────────────────────────
function openCreate() {
  modalMode.value = 'create'
  Object.assign(form, emptyForm())
  formErrors.value  = {}
  showPreview.value = false
  showModal.value   = true
}

function openEdit(tpl) {
  modalMode.value = 'edit'
  Object.assign(form, {
    id        : tpl.id,
    name      : tpl.name,
    type      : tpl.type,
    event     : tpl.event,
    subject   : tpl.subject ?? '',
    body      : tpl.body ?? '',
    variables : tpl.variables
      ? Object.entries(tpl.variables).map(([k, v]) => `${k}: ${v}`).join('\n')
      : '',
    is_active : tpl.is_active,
  })
  formErrors.value  = {}
  showPreview.value = false
  showModal.value   = true
}

function openDetail(tpl) {
  detailTemplate.value = tpl
  showDetailModal.value = true
}

function closeModal() {
  showModal.value = false
}

/** Parse textarea variables → object { key: description } */
function parseVariables(raw) {
  const result = {}
  const lines  = (raw || '').split('\n').map((l) => l.trim()).filter(Boolean)
  for (const line of lines) {
    const idx = line.indexOf(':')
    if (idx > -1) {
      result[line.slice(0, idx).trim()] = line.slice(idx + 1).trim()
    }
  }
  return result
}

async function submitForm() {
  formErrors.value  = {}
  modalLoading.value = true
  try {
    const payload = {
      name      : form.name,
      type      : form.type,
      event     : form.event,
      subject   : form.type === 'email' ? form.subject : null,
      body      : form.body,
      variables : parseVariables(form.variables),
      is_active : form.is_active,
    }
    if (modalMode.value === 'create') {
      await store.createTemplate(payload)
      showToast('Template berhasil dibuat.', 'success')
    } else {
      await store.updateTemplate(form.id, payload)
      showToast('Template berhasil diperbarui.', 'success')
    }
    closeModal()
  } catch (err) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      showToast(err.response?.data?.message ?? 'Terjadi kesalahan.', 'error')
    }
  } finally {
    modalLoading.value = false
  }
}

async function handleDelete(tpl) {
  const ok = await confirm({
    title          : 'Hapus Template',
    message        : `Yakin ingin menghapus template "${tpl.name}"? Tindakan ini tidak dapat dibatalkan.`,
    confirmText    : 'Ya, Hapus',
    confirmVariant : 'danger',
  })
  if (!ok) return
  try {
    await store.deleteTemplate(tpl.id)
    showToast('Template berhasil dihapus.', 'success')
  } catch {
    showToast('Gagal menghapus template.', 'error')
  }
}

// ─── Helper ──────────────────────────────────────────────────────────────────
function formatDate(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

function eventLabel(event) {
  return EVENT_OPTIONS.find((e) => e.value === event)?.label ?? event
}
</script>

<template>
  <AdminLayout>
    <template #default>
      <!-- ───────────── PAGE HEADER ───────────── -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Template Notifikasi</h1>
          <p class="mt-1 text-sm text-gray-500">Kelola template pesan WhatsApp dan Email untuk notifikasi sistem.</p>
        </div>
        <button
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors"
          @click="openCreate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Buat Template
        </button>
      </div>

      <!-- ───────────── FILTER BAR ───────────── -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
            <select
              v-model="filterType"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
            <select
              v-model="filterEvent"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="opt in EVENT_FILTER_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="flex gap-2">
            <button
              class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition-colors"
              @click="applyFilter"
            >Filter</button>
            <button
              class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors"
              @click="resetFilter"
            >Reset</button>
          </div>
        </div>
      </div>

      <!-- ───────────── TABEL ───────────── -->
      <div class="bg-white rounded-xl shadow-card border border-gray-100">
        <DataTable
          :columns="columns"
          :data="store.templates"
          :loading="store.templateLoading"
          empty-text="Belum ada template notifikasi. Klik '+ Buat Template' untuk memulai."
        >
          <!-- Channel -->
          <template #cell-type="{ row }">
            <Badge
              :label="row.type === 'whatsapp' ? 'WhatsApp' : 'Email'"
              :variant="row.type === 'whatsapp' ? 'success' : 'info'"
            />
          </template>

          <!-- Event -->
          <template #cell-event="{ row }">
            <span class="text-sm text-gray-700">{{ eventLabel(row.event) }}</span>
          </template>

          <!-- Status Aktif -->
          <template #cell-is_active="{ row }">
            <Badge
              :label="row.is_active ? 'Aktif' : 'Nonaktif'"
              :variant="row.is_active ? 'active' : 'inactive'"
            />
          </template>

          <!-- Tanggal diperbarui -->
          <template #cell-updated_at="{ row }">
            <span class="text-sm text-gray-500">{{ formatDate(row.updated_at) }}</span>
          </template>

          <!-- Aksi -->
          <template #cell-actions="{ row }">
            <div class="flex items-center gap-2">
              <button
                class="text-gray-500 hover:text-primary-600 transition-colors"
                title="Lihat Detail"
                @click="openDetail(row)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button
                class="text-gray-500 hover:text-primary-600 transition-colors"
                title="Edit"
                @click="openEdit(row)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                class="text-gray-500 hover:text-red-600 transition-colors"
                title="Hapus"
                @click="handleDelete(row)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </template>
        </DataTable>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           MODAL BUAT / EDIT TEMPLATE
      ════════════════════════════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            :aria-label="modalMode === 'create' ? 'Buat Template Notifikasi' : 'Edit Template Notifikasi'"
          >
            <!-- Overlay -->
            <div class="absolute inset-0 bg-gray-900/50" @click="closeModal" />

            <!-- Panel -->
            <div class="relative bg-white rounded-2xl shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col">
              <!-- Header -->
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                  {{ modalMode === 'create' ? 'Buat Template Notifikasi' : 'Edit Template Notifikasi' }}
                </h2>
                <button class="text-gray-400 hover:text-gray-600" @click="closeModal" aria-label="Tutup">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <!-- Nama -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Nama Template <span class="text-red-500">*</span></label>
                  <input
                    v-model="form.name"
                    type="text"
                    placeholder="Contoh: Undangan Survei Alumni – WhatsApp"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    :class="{ 'border-red-500': formErrors.name }"
                  />
                  <p v-if="formErrors.name" class="text-xs text-red-600 mt-1">{{ formErrors.name[0] }}</p>
                </div>

                <!-- Channel + Event (2 kolom) -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-red-500">*</span></label>
                    <select
                      v-model="form.type"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      :class="{ 'border-red-500': formErrors.type }"
                    >
                      <option value="whatsapp">WhatsApp</option>
                      <option value="email">Email</option>
                    </select>
                    <p v-if="formErrors.type" class="text-xs text-red-600 mt-1">{{ formErrors.type[0] }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Trigger <span class="text-red-500">*</span></label>
                    <select
                      v-model="form.event"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      :class="{ 'border-red-500': formErrors.event }"
                    >
                      <option value="" disabled>Pilih event</option>
                      <option v-for="opt in EVENT_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <p v-if="formErrors.event" class="text-xs text-red-600 mt-1">{{ formErrors.event[0] }}</p>
                  </div>
                </div>

                <!-- Subject (email only) -->
                <Transition name="fade">
                  <div v-if="form.type === 'email'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Email <span class="text-red-500">*</span></label>
                    <input
                      v-model="form.subject"
                      type="text"
                      placeholder="Contoh: Reminder: Mohon Lengkapi Survei {{period_name}}"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      :class="{ 'border-red-500': formErrors.subject }"
                    />
                    <p v-if="formErrors.subject" class="text-xs text-red-600 mt-1">{{ formErrors.subject[0] }}</p>
                  </div>
                </Transition>

                <!-- Body -->
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="text-sm font-medium text-gray-700">Body Pesan <span class="text-red-500">*</span></label>
                    <button
                      type="button"
                      class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                      @click="showPreview = !showPreview"
                    >
                      {{ showPreview ? 'Sembunyikan Preview' : 'Tampilkan Preview' }}
                    </button>
                  </div>
                  <textarea
                    v-model="form.body"
                    rows="7"
                    placeholder="Tulis isi pesan. Gunakan {{nama_variabel}} untuk variabel dinamis."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
                    :class="{ 'border-red-500': formErrors.body }"
                  />
                  <p v-if="formErrors.body" class="text-xs text-red-600 mt-1">{{ formErrors.body[0] }}</p>

                  <!-- Preview -->
                  <Transition name="fade">
                    <div v-if="showPreview" class="mt-3 rounded-lg border border-primary-200 bg-primary-50 p-4">
                      <p class="text-xs font-semibold text-primary-700 mb-2">Preview (data contoh):</p>
                      <!-- eslint-disable-next-line vue/no-v-html -->
                      <div class="text-sm text-gray-800 leading-relaxed" v-html="previewRendered" />
                    </div>
                  </Transition>
                </div>

                <!-- Variabel tersedia -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Daftar Variabel
                    <span class="text-xs text-gray-400 ml-1">(format: <code>{{'{{'}}nama{{'}}'}}</code>: deskripsi, satu per baris)</span>
                  </label>
                  <textarea
                    v-model="form.variables"
                    rows="3"
                    placeholder="{{alumni_name}}: Nama lengkap alumni&#10;{{period_name}}: Nama periode survei"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
                  />
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center gap-3">
                  <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <label for="is_active" class="text-sm text-gray-700">Template aktif (akan digunakan sistem)</label>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
                <button
                  class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  :disabled="modalLoading"
                  @click="closeModal"
                >Batal</button>
                <button
                  class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                  :disabled="modalLoading"
                  @click="submitForm"
                >
                  <svg v-if="modalLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ modalMode === 'create' ? 'Buat Template' : 'Simpan Perubahan' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ═══════════════════════════════════════════════════════════════════
           MODAL DETAIL TEMPLATE
      ════════════════════════════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="showDetailModal && detailTemplate"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Detail Template Notifikasi"
          >
            <div class="absolute inset-0 bg-gray-900/50" @click="showDetailModal = false" />
            <div class="relative bg-white rounded-2xl shadow-lg w-full max-w-lg max-h-[85vh] flex flex-col">
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ detailTemplate.name }}</h2>
                <button class="text-gray-400 hover:text-gray-600" @click="showDetailModal = false" aria-label="Tutup">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div class="flex gap-2">
                  <Badge :label="detailTemplate.type === 'whatsapp' ? 'WhatsApp' : 'Email'" :variant="detailTemplate.type === 'whatsapp' ? 'success' : 'info'" />
                  <Badge :label="detailTemplate.is_active ? 'Aktif' : 'Nonaktif'" :variant="detailTemplate.is_active ? 'active' : 'inactive'" />
                  <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ eventLabel(detailTemplate.event) }}</span>
                </div>
                <div v-if="detailTemplate.subject">
                  <p class="text-xs font-medium text-gray-500 mb-1">Subject</p>
                  <p class="text-sm text-gray-800">{{ detailTemplate.subject }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500 mb-1">Body Pesan</p>
                  <pre class="whitespace-pre-wrap text-sm text-gray-800 bg-gray-50 rounded-lg p-3 border border-gray-200 font-mono">{{ detailTemplate.body }}</pre>
                </div>
                <div v-if="detailTemplate.variables && Object.keys(detailTemplate.variables).length">
                  <p class="text-xs font-medium text-gray-500 mb-2">Variabel Tersedia</p>
                  <div class="space-y-1">
                    <div
                      v-for="(desc, key) in detailTemplate.variables"
                      :key="key"
                      class="flex items-start gap-2 text-sm"
                    >
                      <code class="bg-primary-50 text-primary-700 px-1.5 py-0.5 rounded text-xs shrink-0">{{ key }}</code>
                      <span class="text-gray-600">{{ desc }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
                <button
                  class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                  @click="showDetailModal = false"
                >Tutup</button>
                <button
                  class="px-4 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                  @click="() => { showDetailModal = false; openEdit(detailTemplate) }"
                >Edit Template</button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ConfirmModal (dari useConfirm) -->
      <ConfirmModal />
    </template>
  </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 150ms ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }

.fade-enter-active,
.fade-leave-active { transition: opacity 200ms, transform 200ms; }
.fade-enter-from,
.fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
