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
const { showToast } = useToast()

const alumniId = computed(() => route.params.id)
const activeTab = ref('profil')
const showDeleteModal = ref(false)

const tabs = [
  { key: 'profil', label: 'Data Profil' },
  { key: 'pekerjaan', label: 'Riwayat Pekerjaan' },
  { key: 'survei', label: 'Respons Survei' },
]

onMounted(async () => {
  await alumniStore.fetchAlumniDetail(alumniId.value)
})

const alumni = computed(() => alumniStore.current)

function goToEdit() {
  router.push({ name: 'admin.alumni.edit', params: { id: alumniId.value } })
}

function goBack() {
  router.push({ name: 'admin.alumni' })
}

async function handleSendInvitation() {
  try {
    await alumniStore.sendInvitation(alumniId.value)
    showToast('Undangan survei berhasil dikirim.', 'success')
  } catch {
    showToast('Gagal mengirim undangan.', 'error')
  }
}

async function handleDelete() {
  try {
    await alumniStore.deleteAlumni(alumniId.value)
    showToast('Alumni berhasil dihapus.', 'success')
    router.push({ name: 'admin.alumni' })
  } catch {
    showToast('Gagal menghapus alumni.', 'error')
  }
}
</script>

<template>
  <div v-if="alumni">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <button class="text-gray-500 hover:text-gray-700 p-1 rounded" @click="goBack">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ alumni.full_name }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">NIM: {{ alumni.nim }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          v-if="alumni.survey_status !== 'selesai'"
          class="btn-secondary flex items-center gap-2"
          @click="handleSendInvitation"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          Kirim Undangan
        </button>
        <button class="btn-secondary flex items-center gap-2" @click="goToEdit">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Edit
        </button>
        <button
          class="btn-danger flex items-center gap-2"
          @click="showDeleteModal = true"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Hapus
        </button>
      </div>
    </div>

    <!-- Summary Card -->
    <div class="card p-6 mb-6 flex items-center gap-6">
      <img
        v-if="alumni.photo_url"
        :src="alumni.photo_url"
        :alt="alumni.full_name"
        class="w-20 h-20 rounded-full object-cover ring-2 ring-primary-100"
      />
      <div
        v-else
        class="w-20 h-20 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-2xl font-bold"
      >
        {{ alumni.full_name?.charAt(0)?.toUpperCase() }}
      </div>
      <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <p class="text-xs text-gray-500">Program Studi</p>
          <p class="font-semibold text-gray-800">{{ alumni.study_program?.name ?? '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Angkatan</p>
          <p class="font-semibold text-gray-800">{{ alumni.graduation_year?.academic_year ?? '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">IPK</p>
          <p class="font-semibold font-mono text-gray-800">{{ Number(alumni.gpa).toFixed(2) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Status Survei</p>
          <Badge :status="alumni.survey_status" />
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex gap-1">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="[
            'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.key
              ? 'border-primary-600 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700',
          ]"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Tab: Data Profil -->
    <div v-if="activeTab === 'profil'" class="card p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Data Pribadi</h3>
          <dl class="space-y-2">
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">NIK</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.nik ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Jenis Kelamin</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.gender === 'L' ? 'Laki-laki' : alumni.gender === 'P' ? 'Perempuan' : '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Tempat, Tgl Lahir</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.birthplace ?? '-' }}, {{ alumni.birthdate ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Alamat</dt>
              <dd class="text-sm font-medium text-gray-800">{{ [alumni.address_street, alumni.address_city, alumni.address_province].filter(Boolean).join(', ') || '-' }}</dd>
            </div>
          </dl>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Kontak</h3>
          <dl class="space-y-2">
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">No. WhatsApp</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.phone ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Email</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.email ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">LinkedIn</dt>
              <dd class="text-sm text-gray-800">
                <a
                  v-if="alumni.linkedin_url"
                  :href="alumni.linkedin_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary-600 hover:underline"
                >Lihat Profil</a>
                <span v-else>-</span>
              </dd>
            </div>
          </dl>
        </div>
        <div class="md:col-span-2">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Data Akademik</h3>
          <dl class="grid grid-cols-2 gap-2">
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Predikat Kelulusan</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.graduation_predicate ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="text-sm text-gray-500 w-40 flex-shrink-0">Judul Tugas Akhir</dt>
              <dd class="text-sm font-medium text-gray-800">{{ alumni.thesis_title ?? '-' }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <!-- Tab: Riwayat Pekerjaan -->
    <div v-if="activeTab === 'pekerjaan'" class="space-y-4">
      <div v-if="!alumni.work_histories?.length" class="card p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <p class="text-gray-500">Belum ada riwayat pekerjaan.</p>
      </div>
      <div
        v-for="job in alumni.work_histories"
        :key="job.id"
        class="card p-5"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="font-semibold text-gray-900">{{ job.position }}</p>
            <p class="text-sm text-gray-600">{{ job.company_name }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ job.start_date }} — {{ job.is_current ? 'Sekarang' : job.end_date }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Badge v-if="job.is_relevant_to_study" status="selesai" label="Relevan" />
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{{ job.employment_type }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Respons Survei -->
    <div v-if="activeTab === 'survei'" class="card p-6">
      <div v-if="alumni.survey_status === 'selesai' && alumni.survey_response">
        <p class="text-sm text-gray-500 mb-4">Survei diselesaikan pada: <span class="font-medium text-gray-700">{{ alumni.survey_response.submitted_at }}</span></p>
        <!-- Placeholder: tampilkan jawaban survei jika diperlukan -->
        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
          Detail jawaban survei tersedia setelah fitur kuesioner dinamis selesai.
        </div>
      </div>
      <div v-else class="text-center py-8">
        <p class="text-gray-500">Alumni belum menyelesaikan survei.</p>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Hapus Alumni"
      :message="`Apakah Anda yakin ingin menghapus data alumni ${alumni.full_name}?`"
      confirm-text="Ya, Hapus"
      confirm-variant="danger"
      @confirm="handleDelete"
    />
  </div>

  <!-- Loading state -->
  <div v-else class="space-y-4">
    <div class="skeleton h-10 w-64" />
    <div class="skeleton h-32 w-full rounded-xl" />
    <div class="skeleton h-64 w-full rounded-xl" />
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.btn-danger { @apply bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
.skeleton { @apply bg-gray-200 rounded animate-pulse; }
</style>
