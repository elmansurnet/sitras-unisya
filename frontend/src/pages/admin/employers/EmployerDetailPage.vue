<template>
  <div class="space-y-6">
    <!-- Back + actions -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <button
        @click="$router.push({ name: 'admin.employer.index' })"
        class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Kembali
      </button>
      <div class="flex flex-wrap gap-2">
        <button
          @click="$router.push({ name: 'admin.employer.edit', params: { id: route.params.id } })"
          class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
          </svg>
          Edit
        </button>
        <button
          v-if="authStore.isSuperadmin"
          @click="confirmDelete"
          class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
          </svg>
          Hapus
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loadingDetail" class="space-y-4">
      <div class="h-32 animate-pulse rounded-xl bg-gray-100" />
      <div class="h-48 animate-pulse rounded-xl bg-gray-100" />
    </div>

    <template v-else-if="store.current">
      <!-- Profile card -->
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
          <!-- Logo placeholder -->
          <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
          </div>
          <div class="flex-1">
            <div class="flex flex-wrap items-start gap-3">
              <h2 class="text-lg font-semibold text-gray-900">{{ store.current.company_name }}</h2>
              <span :class="statusBadge(store.current.survey_status)" class="rounded-full px-2.5 py-0.5 text-xs font-medium">
                {{ statusLabel(store.current.survey_status) }}
              </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ [store.current.company_type, store.current.industry_sector, store.current.company_scale].filter(Boolean).map(capitalize).join(' · ') || 'Informasi perusahaan belum lengkap' }}
            </p>
            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
              <span v-if="store.current.address_city">
                <span class="text-gray-400">Kota:</span> {{ store.current.address_city }}, {{ store.current.address_province }}
              </span>
              <span v-if="store.current.phone">
                <span class="text-gray-400">Telp:</span> {{ store.current.phone }}
              </span>
              <span v-if="store.current.email">
                <span class="text-gray-400">Email:</span> {{ store.current.email }}
              </span>
              <a v-if="store.current.website" :href="store.current.website" target="_blank" rel="noopener noreferrer" class="text-teal-600 hover:underline">
                {{ store.current.website }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <!-- Tab header -->
        <div class="border-b border-gray-200">
          <nav class="flex gap-1 px-4 pt-3" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-teal-600 text-teal-700'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                'border-b-2 pb-3 pt-1 px-3 text-sm font-medium transition-colors',
              ]"
            >
              {{ tab.label }}
              <span v-if="tab.id === 'alumni'" class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                {{ store.current.alumni?.length ?? 0 }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Tab: PIC & Kontak -->
        <div v-if="activeTab === 'info'" class="p-6">
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Nama PIC</dt>
              <dd class="mt-1 text-sm text-gray-800">{{ store.current.contact_person_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Jabatan PIC</dt>
              <dd class="mt-1 text-sm text-gray-800">{{ store.current.contact_person_position || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Email PIC</dt>
              <dd class="mt-1 text-sm text-gray-800">{{ store.current.contact_person_email || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">WA PIC</dt>
              <dd class="mt-1 text-sm text-gray-800">{{ store.current.contact_person_phone || '—' }}</dd>
            </div>
            <div v-if="store.current.notes" class="sm:col-span-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Catatan Internal</dt>
              <dd class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">{{ store.current.notes }}</dd>
            </div>
          </dl>
        </div>

        <!-- Tab: Alumni Terkait -->
        <div v-else-if="activeTab === 'alumni'" class="p-6">
          <div v-if="!store.current.alumni?.length" class="py-8 text-center text-sm text-gray-400">
            Belum ada alumni yang terhubung dengan employer ini.
          </div>
          <ul v-else class="divide-y divide-gray-100">
            <li
              v-for="alumni in store.current.alumni"
              :key="alumni.id"
              class="flex items-center justify-between py-3"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">{{ alumni.full_name }}</p>
                <p class="text-xs text-gray-400">NIM: {{ alumni.nim }}</p>
              </div>
              <span
                :class="alumni.pivot.is_verified ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="rounded-full px-2 py-0.5 text-xs font-medium"
              >
                {{ alumni.pivot.is_verified ? 'Terverifikasi' : 'Belum Verifikasi' }}
              </span>
            </li>
          </ul>
        </div>

        <!-- Tab: Status Token Survei -->
        <div v-else-if="activeTab === 'token'" class="p-6 space-y-5">
          <!-- Token status info -->
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="flex flex-wrap gap-4 text-sm">
              <div>
                <span class="text-gray-400 text-xs uppercase tracking-wide font-medium">Status Survei</span>
                <p class="mt-1">
                  <span :class="statusBadge(store.current.survey_status)" class="rounded-full px-2.5 py-0.5 text-xs font-medium">
                    {{ statusLabel(store.current.survey_status) }}
                  </span>
                </p>
              </div>
              <div v-if="store.current.survey_token_expires_at">
                <span class="text-gray-400 text-xs uppercase tracking-wide font-medium">Token Berlaku Sampai</span>
                <p class="mt-1 text-gray-800">{{ formatDate(store.current.survey_token_expires_at) }}</p>
              </div>
              <div v-if="store.current.survey_token_used_at">
                <span class="text-gray-400 text-xs uppercase tracking-wide font-medium">Pertama Diakses</span>
                <p class="mt-1 text-gray-800">{{ formatDate(store.current.survey_token_used_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Token actions -->
          <div v-if="store.current.survey_status !== 'selesai'" class="flex flex-wrap gap-3">
            <!-- Kirim Token -->
            <div class="flex items-center gap-2">
              <button
                @click="openSendModal"
                :disabled="store.loadingToken"
                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                Kirim Token
              </button>
            </div>

            <!-- Regenerate Token -->
            <button
              v-if="store.current.survey_status === 'terkirim'"
              @click="handleRegenerate"
              :disabled="store.loadingToken"
              class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-50"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
              </svg>
              Regenerate Token
            </button>
          </div>

          <div v-else class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            Employer ini sudah menyelesaikan survei. Token tidak dapat dikirim ulang.
          </div>
        </div>
      </div>
    </template>

    <!-- Send Token Modal -->
    <div v-if="showSendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40" @click="showSendModal = false" />
      <div class="relative rounded-xl bg-white p-6 shadow-xl w-full max-w-sm">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Kirim Token Survei</h3>
        <p class="text-sm text-gray-600 mb-4">Pilih saluran pengiriman token survei ke employer.</p>
        <div class="flex gap-3 mb-6">
          <label
            v-for="ch in channels"
            :key="ch.value"
            class="flex flex-1 cursor-pointer flex-col items-center gap-2 rounded-lg border-2 p-3 transition-colors"
            :class="selectedChannel === ch.value ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'"
          >
            <input v-model="selectedChannel" type="radio" :value="ch.value" class="sr-only" />
            <span class="text-lg">{{ ch.icon }}</span>
            <span class="text-xs font-medium">{{ ch.label }}</span>
          </label>
        </div>
        <div class="flex gap-3">
          <button @click="showSendModal = false" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Batal</button>
          <button @click="handleSendToken" :disabled="store.loadingToken" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">
            {{ store.loadingToken ? 'Mengirim...' : 'Kirim' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEmployerStore } from '@/stores/employer'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const route      = useRoute()
const router     = useRouter()
const store      = useEmployerStore()
const authStore  = useAuthStore()
const { toast }  = useToast()

const activeTab  = ref('info')
const showSendModal   = ref(false)
const selectedChannel = ref('whatsapp')

const tabs = [
  { id: 'info',  label: 'Info & PIC' },
  { id: 'alumni', label: 'Alumni Terkait' },
  { id: 'token', label: 'Status Token Survei' },
]

const channels = [
  { value: 'whatsapp', label: 'WhatsApp', icon: '💬' },
  { value: 'email',    label: 'Email',    icon: '✉️' },
]

function statusLabel(s) {
  return { belum_disurvei: 'Belum Disurvei', terkirim: 'Terkirim', selesai: 'Selesai' }[s] ?? s
}
function statusBadge(s) {
  return {
    belum_disurvei: 'bg-gray-100 text-gray-600',
    terkirim:       'bg-amber-100 text-amber-700',
    selesai:        'bg-green-100 text-green-700',
  }[s] ?? 'bg-gray-100 text-gray-600'
}
function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : '' }
function formatDate(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(iso))
}

function openSendModal() { showSendModal.value = true }

async function handleSendToken() {
  try {
    await store.sendSurveyToken(route.params.id, selectedChannel.value)
    toast.success('Token survei berhasil dikirim.')
    showSendModal.value = false
  } catch {
    toast.error(store.error ?? 'Gagal mengirim token.')
  }
}

async function handleRegenerate() {
  if (!confirm('Regenerate token akan membatalkan token lama yang sudah dikirim. Lanjutkan?')) return
  try {
    await store.regenerateToken(route.params.id)
    toast.success('Token berhasil di-regenerate.')
  } catch {
    toast.error(store.error ?? 'Gagal regenerate token.')
  }
}

async function confirmDelete() {
  if (!confirm(`Hapus employer "${store.current?.company_name}"? Tindakan ini tidak dapat dibatalkan.`)) return
  try {
    await store.destroy(route.params.id)
    toast.success('Employer berhasil dihapus.')
    router.push({ name: 'admin.employer.index' })
  } catch {
    toast.error(store.error ?? 'Gagal menghapus employer.')
  }
}

onMounted(() => store.fetchById(route.params.id))
</script>
