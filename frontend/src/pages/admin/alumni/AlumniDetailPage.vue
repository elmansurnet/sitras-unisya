<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const alumniStore = useAlumniStore()
const { toast } = useToast()

const alumniId = computed(() => route.params.id)
const activeTab = ref('profil')
// BUG #2 FIX: showDeleteModal dipakai sebagai v-model ke ConfirmModal
const showDeleteModal = ref(false)

const tabs = [
  { key: 'profil',    label: 'Data Profil' },
  { key: 'pekerjaan', label: 'Riwayat Pekerjaan' },
  { key: 'survei',    label: 'Respons Survei' },
]

onMounted(async () => {
  await alumniStore.fetchDetail(alumniId.value)
})

const alumni = computed(() => alumniStore.current)

function goToEdit() {
  router.push({ name: 'admin.alumni.edit', params: { id: alumniId.value } })
}

// BUG #1 FIX: 'admin.alumni' → 'admin.alumni.index'
function goBack() {
  router.push({ name: 'admin.alumni.index' })
}

async function handleSendInvitation() {
  try {
    await alumniStore.sendInvitation(alumniId.value)
    toast.success('Undangan survei berhasil dikirim.')
  } catch {
    toast.error('Gagal mengirim undangan.')
  }
}

async function handleDelete() {
  showDeleteModal.value = false
  try {
    await alumniStore.remove(alumniId.value)
    toast.success('Alumni berhasil dihapus.')
    // BUG #1 FIX: konsisten dengan goBack()
    router.push({ name: 'admin.alumni.index' })
  } catch {
    toast.error('Gagal menghapus alumni.')
  }
}
</script>

<template>
  <div>
    <div v-if="alumniStore.loadingDetail" class="flex items-center justify-center py-24">
      <svg class="h-8 w-8 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
    </div>

    <template v-else-if="alumni">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <button
            class="rounded p-1 text-gray-500 hover:text-gray-700"
            aria-label="Kembali"
            @click="goBack"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ alumni.full_name }}</h1>
            <p class="text-sm text-gray-500">NIM: {{ alumni.nim }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="btn-secondary" @click="handleSendInvitation">Kirim Undangan</button>
          <button class="btn-secondary" @click="goToEdit">Edit</button>
          <button class="btn-danger" @click="showDeleteModal = true">Hapus</button>
        </div>
      </div>

      <div class="mb-1 border-b border-gray-200">
        <nav class="-mb-px flex gap-1" aria-label="Detail tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              'px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
              activeTab === tab.key
                ? 'border-teal-600 text-teal-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <div v-show="activeTab === 'profil'" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card p-6">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Identitas</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">NIK</dt>
              <dd class="font-medium text-gray-900">{{ alumni.nik ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Jenis Kelamin</dt>
              <dd class="font-medium text-gray-900">
                {{ alumni.gender === 'L' ? 'Laki-laki' : alumni.gender === 'P' ? 'Perempuan' : '—' }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Tempat Lahir</dt>
              <dd class="font-medium text-gray-900">{{ alumni.birth_place ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Tanggal Lahir</dt>
              <dd class="font-medium text-gray-900">{{ alumni.birth_date ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <div class="card p-6">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Akademik</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Program Studi</dt>
              <dd class="font-medium text-gray-900">{{ alumni.study_program?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Angkatan</dt>
              <dd class="font-medium text-gray-900">{{ alumni.graduation_year?.year ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">IPK</dt>
              <dd class="font-medium text-gray-900">{{ alumni.gpa ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Predikat</dt>
              <dd class="font-medium text-gray-900">{{ alumni.graduation_predicate ?? '—' }}</dd>
            </div>
            <div v-if="alumni.thesis_title" class="flex flex-col gap-1">
              <dt class="text-gray-500">Judul Skripsi/Tesis</dt>
              <dd class="font-medium text-gray-900">{{ alumni.thesis_title }}</dd>
            </div>
          </dl>
        </div>

        <div class="card p-6">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Alamat</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Jalan</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_street ?? alumni.address?.street ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Desa/Kelurahan</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_village ?? alumni.address?.village ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Kecamatan</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_district ?? alumni.address?.district ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Kota/Kab</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_city ?? alumni.address?.city ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Provinsi</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_province ?? alumni.address?.province ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Kode Pos</dt>
              <dd class="font-medium text-gray-900">{{ alumni.address_postal_code ?? alumni.address?.postal_code ?? '—' }}</dd>
            </div>
          </dl>
        </div>

        <div class="card p-6">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Kontak</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">No. HP</dt>
              <dd class="font-medium text-gray-900">{{ alumni.phone ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Email</dt>
              <dd class="font-medium text-gray-900">{{ alumni.email ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">LinkedIn</dt>
              <dd class="font-medium text-gray-900">
                <a
                  v-if="alumni.linkedin_url"
                  :href="alumni.linkedin_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-teal-600 hover:underline"
                >Lihat Profil</a>
                <span v-else>—</span>
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div v-show="activeTab === 'pekerjaan'" class="mt-4">
        <div v-if="alumni.work_histories?.length" class="space-y-3">
          <div
            v-for="wh in alumni.work_histories"
            :key="wh.id"
            class="card flex items-start justify-between p-4"
          >
            <div>
              <p class="font-medium text-gray-900">{{ wh.position }}</p>
              <p class="text-sm text-gray-500">{{ wh.company_name }}</p>
            </div>
            <Badge v-if="wh.is_current" label="Saat ini" variant="success" />
          </div>
        </div>
        <div v-else class="card p-12 text-center text-sm text-gray-400">
          Belum ada riwayat pekerjaan yang tercatat.
        </div>
      </div>

      <div v-show="activeTab === 'survei'" class="mt-4">
        <div v-if="alumni.survey_responses?.length" class="space-y-3">
          <div
            v-for="sr in alumni.survey_responses"
            :key="sr.id"
            class="card flex items-center justify-between p-4"
          >
            <span class="text-sm text-gray-700">Respons #{{ sr.id }}</span>
            <div class="flex items-center gap-3">
              <Badge :label="sr.status" variant="info" />
              <span class="text-xs text-gray-400">{{ sr.submitted_at ?? '—' }}</span>
            </div>
          </div>
        </div>
        <div v-else class="card p-12 text-center text-sm text-gray-400">
          Belum ada respons survei dari alumni ini.
        </div>
      </div>
    </template>

    <div v-else class="card p-12 text-center text-sm text-gray-400">
      Data alumni tidak ditemukan.
    </div>

    <!--
      BUG #2 FIX:
      1. v-if → v-model="showDeleteModal" (ConfirmModal mengontrol visibilitasnya sendiri)
      2. variant="danger" → :danger="true" (prop yg benar di ConfirmModal)
      3. @cancel tidak perlu lagi karena ConfirmModal emit update:modelValue false
    -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Alumni"
      message="Tindakan ini tidak dapat dibatalkan. Yakin ingin menghapus alumni ini?"
      confirm-label="Ya, Hapus"
      :danger="true"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.btn-danger    { @apply bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors; }
.card          { @apply bg-white rounded-xl shadow-sm border border-gray-200; }
</style>