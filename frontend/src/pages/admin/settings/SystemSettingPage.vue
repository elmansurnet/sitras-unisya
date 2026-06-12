<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

// ─── State ────────────────────────────────────────────────────────────────────
const settings    = ref([])
const loading     = ref(false)
const savingKey   = ref(null)   // key yang sedang disimpan
const editingKey  = ref(null)   // key yang sedang di-edit
const editValue   = ref('')
const maskedKeys  = ref(new Set())
const copiedKey   = ref(null)

// Definisi grup setting sesuai SystemSettingSeeder
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
]

// ─── Computed ─────────────────────────────────────────────────────────────────
const byGroup = computed(() => {
  const map = {}
  for (const g of GROUPS) map[g.key] = []
  for (const s of settings.value) {
    const groupKey = s.group ?? s.key?.split('_')[0]
    if (map[groupKey]) map[groupKey].push(s)
    else {
      // fallback: masukkan ke app
      if (!map['app']) map['app'] = []
      map['app'].push(s)
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

function isBool(val) {
  return val === 'true' || val === 'false' || val === true || val === false
}

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchSettings() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/settings')
    settings.value = data.data
  } catch {
    showToast('Gagal memuat pengaturan sistem.', 'error')
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
    showToast('Pengaturan berhasil disimpan.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menyimpan.', 'error')
  } finally {
    savingKey.value = null
  }
}

async function toggleBool(setting) {
  const newVal = setting.value === 'true' ? 'false' : 'true'
  savingKey.value = setting.key
  try {
    const { data } = await api.put(`/admin/settings/${setting.key}`, { value: newVal })
    const idx = settings.value.findIndex(s => s.key === setting.key)
    if (idx !== -1) settings.value[idx] = data.data
    showToast('Pengaturan berhasil diperbarui.', 'success')
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Gagal menyimpan.', 'error')
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
    showToast('Gagal menyalin ke clipboard.', 'error')
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
      <p class="mt-1 text-sm text-gray-500">Konfigurasi parameter sistem, WA Gateway, OTP, dan survei.</p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3">
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
          <svg class="h-4 w-4 flex-shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" :d="group.icon" />
          </svg>
          <span class="text-sm font-semibold text-gray-700">{{ group.label }}</span>
          <span class="ml-auto text-xs text-gray-400">{{ byGroup[group.key]?.length ?? 0 }} parameter</span>
        </div>

        <!-- Empty group -->
        <div v-if="!byGroup[group.key]?.length" class="px-5 py-6 text-sm text-gray-400 text-center">
          Tidak ada parameter di grup ini.
        </div>

        <!-- Settings rows -->
        <div v-else class="divide-y divide-gray-50">
          <div
            v-for="setting in byGroup[group.key]"
            :key="setting.key"
            class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/50 transition-colors"
          >
            <!-- Key & Description -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ setting.key }}</span>
                <span v-if="isSensitive(setting.key)" class="inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-xs text-amber-600 ring-1 ring-inset ring-amber-500/20">sensitif</span>
              </div>
              <p class="mt-0.5 text-xs text-gray-400">{{ setting.description || setting.key }}</p>
            </div>

            <!-- Value -->
            <div class="flex items-center gap-2 w-64 flex-shrink-0">
              <!-- Boolean toggle -->
              <template v-if="isBool(setting.value)">
                <button
                  @click="toggleBool(setting)"
                  :disabled="savingKey === setting.key"
                  class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-50"
                  :class="setting.value === 'true' ? 'bg-teal-600' : 'bg-gray-300'"
                >
                  <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                    :class="setting.value === 'true' ? 'translate-x-6' : 'translate-x-1'"
                  />
                </button>
                <span class="text-xs" :class="setting.value === 'true' ? 'text-teal-700' : 'text-gray-400'">{{ setting.value === 'true' ? 'Aktif' : 'Nonaktif' }}</span>
              </template>

              <!-- Text/edit mode -->
              <template v-else>
                <div v-if="editingKey === setting.key" class="flex flex-1 items-center gap-1">
                  <input
                    v-model="editValue"
                    class="flex-1 rounded-md border border-teal-400 px-2 py-1 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-teal-500"
                    :type="isSensitive(setting.key) ? 'text' : 'text'"
                    @keydown.enter="saveField(setting)"
                    @keydown.escape="cancelEdit"
                    autofocus
                  />
                  <button
                    @click="saveField(setting)"
                    :disabled="savingKey === setting.key"
                    class="rounded-md bg-teal-600 p-1 text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                    title="Simpan"
                  >
                    <svg v-if="savingKey === setting.key" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                  </button>
                  <button
                    @click="cancelEdit"
                    class="rounded-md p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    title="Batal"
                  >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <div v-else class="flex flex-1 items-center gap-1">
                  <span class="flex-1 truncate font-mono text-xs text-gray-700" :title="setting.value">{{ displayValue(setting) }}</span>
                  <!-- Reveal/mask sensitive -->
                  <button
                    v-if="isSensitive(setting.key)"
                    @click="toggleMask(setting.key)"
                    class="rounded p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    :title="maskedKeys.has(setting.key) ? 'Sembunyikan' : 'Tampilkan'"
                  >
                    <svg v-if="maskedKeys.has(setting.key)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </button>
                  <!-- Copy -->
                  <button
                    @click="copyValue(setting)"
                    class="rounded p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    :title="copiedKey === setting.key ? 'Tersalin!' : 'Salin'"
                  >
                    <svg v-if="copiedKey === setting.key" class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                    </svg>
                  </button>
                  <!-- Edit -->
                  <button
                    @click="startEdit(setting)"
                    class="rounded p-1 text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-colors"
                    title="Edit"
                  >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
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
