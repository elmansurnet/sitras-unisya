<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

// useToast() mengekspor { toast } — BUKAN { showToast }
const { toast } = useToast()

// ─── State ────────────────────────────────────────────────────────────────────
const settings    = ref([])
const loading     = ref(false)
const savingKey   = ref(null)
const editingKey  = ref(null)
const editValue   = ref('')
const maskedKeys  = ref(new Set())
const copiedKey   = ref(null)

// Definisi grup setting sesuai SystemSettingSeeder (field `group` di tabel)
const GROUPS = [
  {
    key: 'wa_gateway',
    label: 'WhatsApp Gateway',
    icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z',
    sensitive: ['wa_api_key'],
  },
  {
    key: 'otp',
    label: 'Konfigurasi OTP',
    icon: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
    sensitive: [],
  },
  {
    key: 'app',
    label: 'Pengaturan Aplikasi',
    icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    sensitive: [],
  },
  {
    key: 'survey',
    label: 'Pengaturan Survei',
    icon: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4',
    sensitive: [],
  },
  {
    key: 'security',
    label: 'Keamanan',
    icon: 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
    sensitive: [],
  },
]

// ─── Computed ─────────────────────────────────────────────────────────────────
const byGroup = computed(() => {
  const map = {}
  for (const g of GROUPS) map[g.key] = []
  for (const s of settings.value) {
    if (s.group && map[s.group] !== undefined) {
      map[s.group].push(s)
    }
  }
  return map
})

function isSensitive(key) {
  return GROUPS.some(g => g.sensitive?.includes(key))
}

function displayValue(s) {
  if (isSensitive(s.key) && !maskedKeys.value.has(s.key)) {
    return '••••••••••••'
  }
  return s.value ?? ''
}

/**
 * Hanya nilai boolean literal true/false yang dirender sebagai toggle.
 * String 'true'/'false' di field teks (mis. wa_api_key) TIDAK dianggap boolean
 * untuk menghindari field teks berubah menjadi toggle.
 * Deteksi boolean hanya berlaku jika kolom tipe-nya memang boolean (cek key).
 */
const BOOL_KEYS = ['otp_enabled', 'maintenance_mode', 'registration_open', 'email_notification']

function isBool(s) {
  return BOOL_KEYS.includes(s.key) ||
    s.value === true || s.value === false
}

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchSettings() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/settings')
    settings.value = data.data
  } catch {
    toast.error('Gagal memuat pengaturan sistem.')
  } finally {
    loading.value = false
  }
}

async function saveField(setting) {
  savingKey.value = setting.key
  try {
    const payload = { value: editValue.value }
    const { data } = await api.put(`/admin/settings/${setting.key}`, payload)
    const idx = settings.value.findIndex(s => s.key === setting.key)
    if (idx !== -1) settings.value[idx] = data.data
    editingKey.value = null
    toast.success('Pengaturan berhasil disimpan.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan.')
  } finally {
    savingKey.value = null
  }
}

async function toggleBool(setting) {
  const newVal = setting.value === 'true' || setting.value === true ? 'false' : 'true'
  savingKey.value = setting.key
  try {
    const { data } = await api.put(`/admin/settings/${setting.key}`, { value: newVal })
    const idx = settings.value.findIndex(s => s.key === setting.key)
    if (idx !== -1) settings.value[idx] = data.data
    toast.success('Pengaturan berhasil diperbarui.')
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan.')
  } finally {
    savingKey.value = null
  }
}

function startEdit(setting) {
  editingKey.value = setting.key
  editValue.value  = setting.value ?? ''
}

function cancelEdit() {
  editingKey.value = null
  editValue.value  = ''
}

async function copyValue(setting) {
  const val = setting.value ?? ''
  try {
    await navigator.clipboard.writeText(val)
    copiedKey.value = setting.key
    setTimeout(() => { copiedKey.value = null }, 2000)
  } catch {
    toast.error('Gagal menyalin ke clipboard.')
  }
}

function toggleMask(key) {
  if (maskedKeys.value.has(key)) maskedKeys.value.delete(key)
  else maskedKeys.value.add(key)
}

onMounted(fetchSettings)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-gray-900">Pengaturan Sistem</h1>
      <p class="mt-1 text-sm text-gray-500">Konfigurasi parameter sistem, WA Gateway, OTP, keamanan, dan survei.</p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3">
        <div class="h-5 w-40 animate-pulse rounded bg-gray-100" />
        <div v-for="j in 3" :key="j" class="h-10 animate-pulse rounded bg-gray-100" />
      </div>
    </div>

    <!-- Groups -->
    <template v-else>
      <div
        v-for="group in GROUPS"
        :key="group.key"
        class="overflow-hidden rounded-xl border border-gray-200 bg-white"
      >
        <!-- Group Header -->
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-5 py-3">
          <svg
            class="h-4 w-4 flex-shrink-0 text-teal-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.75"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" :d="group.icon" />
          </svg>
          <h2 class="text-sm font-semibold text-gray-800">{{ group.label }}</h2>
          <span class="ml-auto text-xs text-gray-400">
            {{ byGroup[group.key]?.length ?? 0 }} pengaturan
          </span>
        </div>

        <!-- Empty group -->
        <div
          v-if="!byGroup[group.key]?.length"
          class="px-5 py-6 text-center text-sm text-gray-400"
        >
          Tidak ada pengaturan pada grup ini.
        </div>

        <!-- Setting rows -->
        <div v-else class="divide-y divide-gray-50">
          <div
            v-for="s in byGroup[group.key]"
            :key="s.key"
            class="flex flex-col gap-1.5 px-5 py-4 sm:flex-row sm:items-start sm:gap-4"
          >
            <!-- Label & description -->
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-800">{{ s.label ?? s.key }}</p>
              <p v-if="s.description" class="mt-0.5 text-xs text-gray-400">{{ s.description }}</p>
              <p class="mt-0.5 font-mono text-xs text-gray-400">{{ s.key }}</p>
            </div>

            <!-- Value / edit area -->
            <div class="flex min-w-0 flex-1 flex-col gap-2">
              <!-- Bool toggle -->
              <template v-if="isBool(s) && editingKey !== s.key">
                <button
                  type="button"
                  :disabled="savingKey === s.key"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50',
                    (s.value === 'true' || s.value === true) ? 'bg-teal-600' : 'bg-gray-200'
                  ]"
                  @click="toggleBool(s)"
                  :aria-label="`Toggle ${s.key}`"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      (s.value === 'true' || s.value === true) ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </template>

              <!-- Text edit mode -->
              <template v-else-if="editingKey === s.key">
                <input
                  v-model="editValue"
                  class="w-full rounded-lg border border-teal-400 px-3 py-1.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                  :type="isSensitive(s.key) ? 'text' : 'text'"
                  :placeholder="s.key"
                  @keydown.enter="saveField(s)"
                  @keydown.escape="cancelEdit"
                />
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="rounded-md bg-teal-600 px-3 py-1 text-xs font-medium text-white hover:bg-teal-700 disabled:opacity-50"
                    :disabled="savingKey === s.key"
                    @click="saveField(s)"
                  >
                    {{ savingKey === s.key ? 'Menyimpan...' : 'Simpan' }}
                  </button>
                  <button
                    type="button"
                    class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50"
                    @click="cancelEdit"
                  >
                    Batal
                  </button>
                </div>
              </template>

              <!-- Display mode -->
              <template v-else>
                <div class="flex items-center gap-2">
                  <span class="min-w-0 flex-1 break-all font-mono text-sm text-gray-700">
                    {{ displayValue(s) }}
                  </span>

                  <!-- Mask toggle for sensitive -->
                  <button
                    v-if="isSensitive(s.key)"
                    type="button"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600"
                    @click="toggleMask(s.key)"
                    :aria-label="maskedKeys.has(s.key) ? 'Sembunyikan nilai' : 'Tampilkan nilai'"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        v-if="maskedKeys.has(s.key)"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                      />
                      <path
                        v-else
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                  </button>

                  <!-- Copy -->
                  <button
                    type="button"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600"
                    @click="copyValue(s)"
                    :aria-label="`Salin nilai ${s.key}`"
                  >
                    <svg v-if="copiedKey === s.key" class="h-4 w-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                  </button>

                  <!-- Edit -->
                  <button
                    type="button"
                    class="flex-shrink-0 text-gray-400 hover:text-teal-600"
                    @click="startEdit(s)"
                    :aria-label="`Edit ${s.key}`"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
