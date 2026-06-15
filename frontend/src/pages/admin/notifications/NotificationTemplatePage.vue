<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'

const store = useNotificationStore()
const { toast } = useToast()

// ── Filter ──────────────────────────────────────────────────────────────────
const filterType  = ref('')
const filterEvent = ref('')

// ── Modal: Form Tambah/Edit ──────────────────────────────────────────────────
const showFormModal  = ref(false)
const editingId      = ref(null)
const savingTemplate = ref(false)
const formErrors     = ref({})

const EMPTY_FORM = () => ({
  name       : '',
  type       : 'whatsapp',   // 'whatsapp' | 'email'
  event      : '',
  subject    : '',           // hanya untuk type=email
  body       : '',
  variables  : '',           // comma-separated string, disimpan sebagai array
  is_active  : true,
})
const form = reactive(EMPTY_FORM())

function openCreate() {
  editingId.value = null
  Object.assign(form, EMPTY_FORM())
  formErrors.value = {}
  showFormModal.value = true
}

async function openEdit(tpl) {
  editingId.value = tpl.id
  Object.assign(form, {
    name      : tpl.name      ?? '',
    type      : tpl.type      ?? 'whatsapp',
    event     : tpl.event     ?? '',
    subject   : tpl.subject   ?? '',
    body      : tpl.body      ?? '',
    variables : Array.isArray(tpl.variables) ? tpl.variables.join(', ') : (tpl.variables ?? ''),
    is_active : tpl.is_active ?? true,
  })
  formErrors.value = {}
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  editingId.value     = null
  formErrors.value    = {}
}

async function handleSave() {
  savingTemplate.value = true
  formErrors.value     = {}
  try {
    const payload = {
      name      : form.name,
      type      : form.type,
      event     : form.event,
      subject   : form.subject || null,
      body      : form.body,
      variables : form.variables
        ? form.variables.split(',').map((v) => v.trim()).filter(Boolean)
        : [],
      is_active : form.is_active,
    }
    if (editingId.value) {
      await store.updateTemplate(editingId.value, payload)
      toast.success('Template berhasil diperbarui.')
    } else {
      await store.createTemplate(payload)
      toast.success('Template berhasil ditambahkan.')
    }
    closeFormModal()
  } catch (err) {
    formErrors.value = err.response?.data?.errors ?? {}
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan template.')
  } finally {
    savingTemplate.value = false
  }
}

// ── Toggle Aktif/Nonaktif ────────────────────────────────────────────────────
const togglingId = ref(null)
async function handleToggle(tpl) {
  togglingId.value = tpl.id
  try {
    await store.updateTemplate(tpl.id, { ...tpl, is_active: !tpl.is_active })
    toast.success(tpl.is_active ? 'Template dinonaktifkan.' : 'Template diaktifkan.')
  } catch {
    toast.error('Gagal mengubah status template.')
  } finally {
    togglingId.value = null
  }
}

// ── Hapus Template ───────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleteTarget    = ref(null)
const deletingId      = ref(null)

function confirmDelete(tpl) {
  deleteTarget.value    = tpl
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deleteTarget.value) return
  deletingId.value = deleteTarget.value.id
  try {
    await store.deleteTemplate(deleteTarget.value.id)
    toast.success('Template berhasil dihapus.')
    showDeleteModal.value = false
    deleteTarget.value    = null
  } catch {
    toast.error('Gagal menghapus template.')
  } finally {
    deletingId.value = null
  }
}

// ── Load ─────────────────────────────────────────────────────────────────────
async function loadTemplates() {
  try {
    await store.fetchTemplates({
      type  : filterType.value  || undefined,
      event : filterEvent.value || undefined,
    })
  } catch {
    toast.error('Gagal memuat template notifikasi.')
  }
}

onMounted(loadTemplates)

// Event options
const eventOptions = [
  { value: 'survey_invitation',  label: 'Undangan Survei'       },
  { value: 'survey_reminder',    label: 'Pengingat Survei'      },
  { value: 'survey_closed',      label: 'Survei Ditutup'        },
  { value: 'otp_login',          label: 'OTP Login'             },
  { value: 'registration',       label: 'Registrasi'            },
]

const filteredTemplates = computed(() => {
  let list = store.templates ?? []
  if (filterType.value)  list = list.filter((t) => t.type  === filterType.value)
  if (filterEvent.value) list = list.filter((t) => t.event === filterEvent.value)
  return list
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Template Notifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola template pesan WhatsApp dan Email untuk notifikasi sistem.</p>
      </div>
      <button class="btn-primary flex items-center gap-2" @click="openCreate">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Template
      </button>
    </div>

    <!-- Filter -->
    <div class="card p-4">
      <div class="flex flex-wrap gap-3">
        <select v-model="filterType" class="form-input w-40" @change="loadTemplates">
          <option value="">Semua Channel</option>
          <option value="whatsapp">WhatsApp</option>
          <option value="email">Email</option>
        </select>
        <select v-model="filterEvent" class="form-input w-52" @change="loadTemplates">
          <option value="">Semua Event</option>
          <option v-for="opt in eventOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <button class="btn-secondary text-sm" @click="filterType = ''; filterEvent = ''; loadTemplates()">Reset</button>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Template</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="store.templateLoading">
              <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                <div class="flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                  Memuat template...
                </div>
              </td>
            </tr>
            <tr v-else-if="!filteredTemplates.length">
              <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                Belum ada template notifikasi. Klik "Tambah Template" untuk membuat yang baru.
              </td>
            </tr>
            <tr v-for="tpl in filteredTemplates" :key="tpl.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ tpl.name }}</div>
                <div v-if="tpl.variables?.length" class="text-xs text-gray-400 mt-0.5">
                  Variabel: {{ Array.isArray(tpl.variables) ? tpl.variables.join(', ') : tpl.variables }}
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', tpl.type === 'whatsapp' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800']">
                  {{ tpl.type === 'whatsapp' ? 'WhatsApp' : 'Email' }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-600 text-xs">{{ tpl.event ?? '-' }}</td>
              <td class="px-4 py-3">
                <!-- Toggle switch is_active -->
                <button
                  type="button"
                  :disabled="togglingId === tpl.id"
                  :title="tpl.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                  :class="[
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50',
                    tpl.is_active ? 'bg-emerald-500' : 'bg-gray-300',
                  ]"
                  @click="handleToggle(tpl)"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200',
                      tpl.is_active ? 'translate-x-4' : 'translate-x-0',
                    ]"
                  />
                </button>
                <span class="ml-2 text-xs" :class="tpl.is_active ? 'text-emerald-700' : 'text-gray-400'">
                  {{ tpl.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                  <button title="Edit" class="p-1 text-gray-400 hover:text-blue-600 rounded" @click="openEdit(tpl)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button title="Hapus" class="p-1 text-gray-400 hover:text-red-600 rounded" @click="confirmDelete(tpl)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Form Modal Tambah/Edit ──────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showFormModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
          <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeFormModal" />
          <div class="relative z-10 w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900">{{ editingId ? 'Edit Template' : 'Tambah Template Baru' }}</h2>
              <button class="p-1 text-gray-400 hover:text-gray-600 rounded" @click="closeFormModal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
              <!-- Nama -->
              <div>
                <label class="form-label">Nama Template <span class="text-red-500">*</span></label>
                <input v-model="form.name" class="form-input" placeholder="Contoh: Undangan Survei Alumni" />
                <p v-if="formErrors.name" class="text-xs text-red-500 mt-1">{{ formErrors.name[0] }}</p>
              </div>

              <!-- Channel + Event -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Channel <span class="text-red-500">*</span></label>
                  <select v-model="form.type" class="form-input">
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Event <span class="text-red-500">*</span></label>
                  <select v-model="form.event" class="form-input">
                    <option value="">Pilih Event</option>
                    <option v-for="opt in eventOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                  <p v-if="formErrors.event" class="text-xs text-red-500 mt-1">{{ formErrors.event[0] }}</p>
                </div>
              </div>

              <!-- Subject (email only) -->
              <div v-if="form.type === 'email'">
                <label class="form-label">Subject Email</label>
                <input v-model="form.subject" class="form-input" placeholder="Contoh: Undangan Mengisi Tracer Study" />
              </div>

              <!-- Body -->
              <div>
                <label class="form-label">Isi Pesan <span class="text-red-500">*</span></label>
                <textarea v-model="form.body" class="form-input" rows="6" placeholder="Gunakan {{variable}} untuk variabel dinamis. Contoh: Halo {{nama}}, Anda diundang untuk mengisi survei."></textarea>
                <p v-if="formErrors.body" class="text-xs text-red-500 mt-1">{{ formErrors.body[0] }}</p>
              </div>

              <!-- Variables -->
              <div>
                <label class="form-label">Variabel Tersedia</label>
                <input v-model="form.variables" class="form-input" placeholder="nama, nim, link_survei, tanggal" />
                <p class="text-xs text-gray-400 mt-1">Pisahkan dengan koma. Contoh: nama, nim, link_survei</p>
              </div>

              <!-- Is Active -->
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  :class="['relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200', form.is_active ? 'bg-emerald-500' : 'bg-gray-300']"
                  @click="form.is_active = !form.is_active"
                >
                  <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200', form.is_active ? 'translate-x-4' : 'translate-x-0']" />
                </button>
                <label class="text-sm text-gray-700">Template Aktif</label>
              </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
              <button type="button" class="btn-secondary" @click="closeFormModal">Batal</button>
              <button type="button" class="btn-primary" :disabled="savingTemplate" @click="handleSave">
                {{ savingTemplate ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Tambah Template') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirm Delete -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Template"
      :message="`Apakah Anda yakin ingin menghapus template &quot;${deleteTarget?.name ?? ''}&quot;? Tindakan ini tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus"
      :danger="true"
      :loading="!!deletingId"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-primary   { @apply bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card          { @apply bg-white rounded-xl shadow-sm border border-gray-100; }
.form-label    { @apply block text-sm font-medium text-gray-700 mb-1.5; }
.form-input    { @apply w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none; }
</style>
