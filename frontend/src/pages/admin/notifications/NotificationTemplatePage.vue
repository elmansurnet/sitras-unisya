<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const store = useNotificationStore()
const { toast } = useToast()
const { confirm } = useConfirm()

const filterType = ref('')
const filterEvent = ref('')
const showModal = ref(false)
const modalMode = ref('create')
const modalLoading = ref(false)
const formErrors = ref({})
const showPreview = ref(false)
const showDetailModal = ref(false)
const detailTemplate = ref(null)

const emptyForm = () => ({
  id: null,
  name: '',
  type: 'whatsapp',
  event: '',
  subject: '',
  body: '',
  variables: '',
  is_active: true,
})
const form = reactive(emptyForm())

const EVENT_OPTIONS = [
  { value: 'survey_invitation', label: 'Undangan Survei' },
  { value: 'survey_reminder', label: 'Reminder Survei' },
  { value: 'otp_login', label: 'OTP Login' },
  { value: 'employer_survey_invitation', label: 'Undangan Survei Employer' },
]

const TYPE_OPTIONS = [
  { value: '', label: 'Semua Channel' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'email', label: 'Email' },
]

const EVENT_FILTER_OPTIONS = [{ value: '', label: 'Semua Event' }, ...EVENT_OPTIONS]

const previewRendered = computed(() => {
  const sampleData = {
    '{{alumni_name}}': 'Ahmad Fauzi',
    '{{period_name}}': 'Tracer Study 2024',
    '{{survey_url}}': 'https://tracer.unisya.ac.id/alumni/survey',
    '{{otp_code}}': '847291',
    '{{expires_in_minutes}}': '5',
    '{{company_name}}': 'PT Maju Bersama',
    '{{contact_person_name}}': 'Budi Santoso',
  }
  let rendered = form.body
  for (const [key, val] of Object.entries(sampleData)) {
    rendered = rendered.replaceAll(key, `<strong class="text-primary-600">${val}</strong>`)
  }
  rendered = rendered.replace(/\{\{[^}]+\}\}/g, (m) => `<span class="bg-yellow-100 text-yellow-800 rounded px-0.5">${m}</span>`)
  return rendered.replace(/\n/g, '<br>')
})

const columns = [
  { key: 'name', label: 'Nama Template', sortable: true },
  { key: 'type', label: 'Channel', sortable: true, width: '120px' },
  { key: 'event', label: 'Event', sortable: true },
  { key: 'is_active', label: 'Status', width: '100px' },
  { key: 'updated_at', label: 'Diperbarui', sortable: true, width: '160px' },
  { key: 'actions', label: 'Aksi', width: '140px' },
]

async function loadTemplates() {
  const params = {}
  if (filterType.value) params.type = filterType.value
  if (filterEvent.value) params.event = filterEvent.value
  try {
    await store.fetchTemplates(params)
  } catch {
    toast.error('Gagal memuat template notifikasi.')
  }
}

onMounted(loadTemplates)

function applyFilter() { loadTemplates() }
function resetFilter() {
  filterType.value = ''
  filterEvent.value = ''
  loadTemplates()
}

function openCreate() {
  modalMode.value = 'create'
  Object.assign(form, emptyForm())
  formErrors.value = {}
  showPreview.value = false
  showModal.value = true
}

function parseVariables(raw) {
  const result = {}
  const lines = (raw || '').split('\n').map((l) => l.trim()).filter(Boolean)
  for (const line of lines) {
    const idx = line.indexOf(':')
    if (idx > -1) result[line.slice(0, idx).trim()] = line.slice(idx + 1).trim()
  }
  return result
}

async function submitForm() {
  formErrors.value = {}
  modalLoading.value = true
  try {
    const payload = {
      name: form.name,
      type: form.type,
      event: form.event,
      subject: form.type === 'email' ? form.subject : null,
      body: form.body,
      variables: parseVariables(form.variables),
      is_active: form.is_active,
    }
    if (modalMode.value === 'create') {
      await store.createTemplate(payload)
      toast.success('Template berhasil dibuat.')
    } else {
      await store.updateTemplate(form.id, payload)
      toast.success('Template berhasil diperbarui.')
    }
    showModal.value = false
  } catch (err) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      toast.error(err.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  } finally {
    modalLoading.value = false
  }
}

async function handleDelete(tpl) {
  const ok = await confirm({
    title: 'Hapus Template',
    message: `Yakin ingin menghapus template "${tpl.name}"? Tindakan ini tidak dapat dibatalkan.`,
    confirmText: 'Ya, Hapus',
    confirmVariant: 'danger',
  })
  if (!ok) return
  try {
    await store.deleteTemplate(tpl.id)
    toast.success('Template berhasil dihapus.')
  } catch {
    toast.error('Gagal menghapus template.')
  }
}
</script>

<template>
  <AdminLayout>
    <template #default>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Template Notifikasi</h1>
          <p class="mt-1 text-sm text-gray-500">Kelola template pesan WhatsApp dan Email untuk notifikasi sistem.</p>
        </div>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors" @click="openCreate">Buat Template</button>
      </div>
    </template>
  </AdminLayout>
</template>
