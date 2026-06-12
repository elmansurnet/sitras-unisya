<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAlumniStore } from '@/stores/alumni'
import Badge from '@/components/common/Badge.vue'

const router = useRouter()
const authStore = useAuthStore()
const alumniStore = useAlumniStore()

const alumni = computed(() => authStore.user?.alumni)
const profile = computed(() => alumniStore.currentProfile)

const surveyStatus = computed(() => alumni.value?.survey_status ?? 'belum_disurvei')

const surveyCard = computed(() => {
  const map = {
    belum_disurvei: {
      title: 'Isi Survei Tracer Study',
      description: 'Belum ada survei yang dikirimkan. Tunggu undangan dari admin atau hubungi kami.',
      btnLabel: null,
      btnRoute: null,
      color: 'gray',
    },
    terkirim: {
      title: 'Undangan Survei Telah Dikirim',
      description: 'Anda telah mendapatkan undangan survei. Klik tombol di bawah untuk mulai mengisi.',
      btnLabel: 'Mulai Isi Survei',
      btnRoute: 'alumni.survey',
      color: 'blue',
    },
    sedang_mengisi: {
      title: 'Lanjutkan Survei Anda',
      description: 'Anda sedang dalam proses pengisian survei. Jangan lewatkan kesempatan ini!',
      btnLabel: 'Lanjutkan Survei',
      btnRoute: 'alumni.survey',
      color: 'yellow',
    },
    selesai: {
      title: 'Survei Telah Selesai',
      description: 'Terima kasih! Anda telah menyelesaikan survei tracer study. Kontribusi Anda sangat berarti.',
      btnLabel: null,
      btnRoute: null,
      color: 'green',
    },
  }
  return map[surveyStatus.value] ?? map.belum_disurvei
})

const profileCompletion = computed(() => {
  if (!alumni.value) return 0
  const fields = [
    alumni.value.nik, alumni.value.full_name, alumni.value.gender,
    alumni.value.birthdate, alumni.value.phone, alumni.value.email,
    alumni.value.address_city, alumni.value.photo_url, alumni.value.linkedin_url,
  ]
  const filled = fields.filter(Boolean).length
  return Math.round((filled / fields.length) * 100)
})

const incompleteFields = computed(() => {
  if (!alumni.value) return []
  const checks = [
    { key: 'nik', label: 'NIK' },
    { key: 'gender', label: 'Jenis Kelamin' },
    { key: 'birthdate', label: 'Tanggal Lahir' },
    { key: 'phone', label: 'No. WhatsApp' },
    { key: 'email', label: 'Email' },
    { key: 'address_city', label: 'Kota Domisili' },
    { key: 'photo_url', label: 'Foto Profil' },
    { key: 'linkedin_url', label: 'LinkedIn' },
  ]
  return checks.filter(c => !alumni.value[c.key]).map(c => c.label)
})

const latestJob = computed(() => {
  const histories = alumni.value?.work_histories ?? []
  return histories.find(j => j.is_current) ?? histories[0] ?? null
})

onMounted(() => {
  alumniStore.fetchMyProfile()
})

function goToSurvey() {
  router.push({ name: surveyCard.value.btnRoute })
}

function goToProfile() {
  router.push({ name: 'alumni.profile.edit' })
}

function goToWorkHistory() {
  router.push({ name: 'alumni.work-histories' })
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-6 py-6 px-4">
    <!-- Welcome Banner -->
    <div class="card p-6 flex items-center gap-4">
      <img
        v-if="alumni?.photo_url"
        :src="alumni.photo_url"
        :alt="alumni.full_name"
        class="w-14 h-14 rounded-full object-cover ring-2 ring-primary-100"
      />
      <div
        v-else
        class="w-14 h-14 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold flex-shrink-0"
      >
        {{ alumni?.full_name?.charAt(0)?.toUpperCase() }}
      </div>
      <div>
        <p class="text-gray-500 text-sm">Assalamualaikum,</p>
        <h1 class="text-xl font-bold text-gray-900">{{ alumni?.full_name ?? 'Alumni' }}</h1>
        <p class="text-sm text-gray-500">{{ alumni?.study_program?.name }} · {{ alumni?.graduation_year?.academic_year }}</p>
      </div>
    </div>

    <!-- Survey Card -->
    <div
      class="card p-6 border-l-4"
      :class="{
        'border-gray-300': surveyStatus === 'belum_disurvei',
        'border-blue-500': surveyStatus === 'terkirim',
        'border-yellow-500': surveyStatus === 'sedang_mengisi',
        'border-green-500': surveyStatus === 'selesai',
      }"
    >
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <h2 class="font-semibold text-gray-900">{{ surveyCard.title }}</h2>
            <Badge :status="surveyStatus" />
          </div>
          <p class="text-sm text-gray-500">{{ surveyCard.description }}</p>

          <!-- Progress for sedang_mengisi -->
          <div v-if="surveyStatus === 'sedang_mengisi' && alumni?.survey_completion_percentage" class="mt-3">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
              <span>Progress pengisian</span>
              <span>{{ alumni.survey_completion_percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-yellow-500 h-2 rounded-full transition-all"
                :style="{ width: `${alumni.survey_completion_percentage}%` }"
              />
            </div>
          </div>

          <!-- Selesai: tanggal submit -->
          <div v-if="surveyStatus === 'selesai'" class="mt-3 text-xs text-green-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Terima kasih atas partisipasi Anda
          </div>
        </div>
        <button
          v-if="surveyCard.btnRoute"
          class="btn-primary ml-4 flex-shrink-0"
          @click="goToSurvey"
        >
          {{ surveyCard.btnLabel }}
        </button>
      </div>
    </div>

    <!-- Profile Completion -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-900">Kelengkapan Profil</h2>
        <span class="text-sm font-bold"
          :class="profileCompletion === 100 ? 'text-green-600' : 'text-primary-600'"
        >
          {{ profileCompletion }}%
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
        <div
          class="h-2 rounded-full transition-all"
          :class="profileCompletion === 100 ? 'bg-green-500' : 'bg-primary-500'"
          :style="{ width: `${profileCompletion}%` }"
        />
      </div>
      <div v-if="incompleteFields.length" class="text-sm text-gray-500 mb-3">
        Field belum diisi: <span class="text-gray-700">{{ incompleteFields.join(', ') }}</span>
      </div>
      <button class="btn-secondary text-sm" @click="goToProfile">Lengkapi Profil</button>
    </div>

    <!-- Work History Summary -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Riwayat Pekerjaan</h2>
        <button class="text-sm text-primary-600 hover:underline" @click="goToWorkHistory">Kelola</button>
      </div>
      <div v-if="latestJob">
        <p class="font-medium text-gray-800">{{ latestJob.position }}</p>
        <p class="text-sm text-gray-500">{{ latestJob.company_name }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ latestJob.start_date }} — {{ latestJob.is_current ? 'Sekarang' : latestJob.end_date }}</p>
      </div>
      <div v-else class="text-center py-6">
        <p class="text-sm text-gray-400">Belum ada riwayat pekerjaan.</p>
        <button class="btn-primary mt-3 text-sm" @click="goToWorkHistory">Tambah Pekerjaan</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
</style>
