<script setup>
/**
 * HomePage.vue — Alumni Dashboard
 * Route: /alumni/home (name: alumni.home)
 * Sesuai 06_UI_UX.md §3.5 & §8
 */
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAlumniProfileStore } from '@/stores/alumniProfile'
import Badge from '@/components/common/Badge.vue'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useAlumniProfileStore()

const user = computed(() => authStore.user)
const profile = computed(() => profileStore.profile)
const loading = computed(() => profileStore.loading)

const surveyStatusConfig = {
  belum_disurvei: {
    variant: 'muted',
    label: 'Belum Disurvei',
    cta: 'Mulai Isi Survei',
    ctaRoute: 'alumni.survey',
    message: 'Anda belum mengisi survei. Silakan mulai sekarang.',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
  },
  terkirim: {
    variant: 'info',
    label: 'Undangan Terkirim',
    cta: 'Mulai Isi Survei',
    ctaRoute: 'alumni.survey',
    message: 'Undangan survei telah dikirimkan ke Anda. Silakan mulai mengisi.',
    icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  },
  sedang_mengisi: {
    variant: 'warning',
    label: 'Sedang Mengisi',
    cta: 'Lanjutkan Survei',
    ctaRoute: 'alumni.survey',
    message: 'Anda sedang mengisi survei. Lanjutkan dari bagian yang terakhir.',
    icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
  },
  selesai: {
    variant: 'success',
    label: 'Survei Selesai',
    cta: null,
    ctaRoute: null,
    message: 'Terima kasih! Anda telah menyelesaikan survei. Kontribusi Anda sangat berarti.',
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
}

const surveyStatus = computed(() =>
  profile.value?.survey_status ?? 'belum_disurvei'
)
const statusConfig = computed(() =>
  surveyStatusConfig[surveyStatus.value] ?? surveyStatusConfig.belum_disurvei
)

/** Persentase kelengkapan profil (hitung field utama yang terisi) */
const profileCompletion = computed(() => {
  if (!profile.value) return 0
  const fields = [
    'full_name', 'nim', 'nik', 'phone', 'email',
    'address_city', 'birth_date', 'linkedin_url', 'photo_url',
  ]
  const filled = fields.filter((f) => !!profile.value[f]).length
  return Math.round((filled / fields.length) * 100)
})

const incompleteFields = computed(() => {
  if (!profile.value) return []
  const map = {
    nik: 'NIK',
    phone: 'Nomor WhatsApp',
    email: 'Email',
    address_city: 'Kota domisili',
    birth_date: 'Tanggal lahir',
    linkedin_url: 'LinkedIn URL',
    photo_url: 'Foto profil',
  }
  return Object.entries(map)
    .filter(([key]) => !profile.value[key])
    .map(([, label]) => label)
})

onMounted(() => {
  if (!profile.value) profileStore.fetchProfile()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Greeting -->
    <div>
      <h1 class="text-xl font-semibold text-[var(--color-text)]">
        Assalamu'alaikum, {{ user?.name ?? 'Alumni' }} 👋
      </h1>
      <p class="text-sm text-[var(--color-text-muted)] mt-0.5">
        Selamat datang di portal alumni SITRAS UNISYA.
      </p>
    </div>

    <!-- Skeleton loading -->
    <template v-if="loading">
      <div class="skeleton h-36 rounded-xl" />
      <div class="skeleton h-28 rounded-xl" />
      <div class="skeleton h-28 rounded-xl" />
    </template>

    <template v-else>
      <!-- ── Kartu Status Survei ── -->
      <div
        class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6"
        :class="{
          'border-l-4 border-l-[var(--color-success)]': surveyStatus === 'selesai',
          'border-l-4 border-l-[var(--color-warning)]': surveyStatus === 'sedang_mengisi',
          'border-l-4 border-l-[var(--color-info)]': surveyStatus === 'terkirim',
        }"
      >
        <div class="flex items-start gap-4">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
            :class="{
              'bg-[var(--color-success-highlight)] text-[var(--color-success)]': surveyStatus === 'selesai',
              'bg-[var(--color-warning-highlight)] text-[var(--color-warning)]': surveyStatus === 'sedang_mengisi',
              'bg-[var(--color-primary-highlight)] text-[var(--color-primary)]': ['belum_disurvei','terkirim'].includes(surveyStatus),
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" :d="statusConfig.icon" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm font-semibold text-[var(--color-text)]">Status Survei</span>
              <Badge :variant="statusConfig.variant" dot>{{ statusConfig.label }}</Badge>
            </div>
            <p class="text-sm text-[var(--color-text-muted)]">{{ statusConfig.message }}</p>

            <!-- Progress bar untuk sedang_mengisi -->
            <div v-if="surveyStatus === 'sedang_mengisi' && profile?.survey_progress" class="mt-3">
              <div class="flex justify-between text-xs text-[var(--color-text-muted)] mb-1">
                <span>Progress pengisian</span>
                <span>{{ profile.survey_progress }}%</span>
              </div>
              <div class="h-2 rounded-full bg-[var(--color-surface-offset)] overflow-hidden">
                <div
                  class="h-full bg-[var(--color-warning)] rounded-full transition-all duration-500"
                  :style="{ width: profile.survey_progress + '%' }"
                />
              </div>
            </div>

            <!-- Tanggal submit untuk selesai -->
            <p v-if="surveyStatus === 'selesai' && profile?.survey_submitted_at" class="text-xs text-[var(--color-text-faint)] mt-2">
              Dikirim pada {{ new Date(profile.survey_submitted_at).toLocaleString('id-ID') }}
            </p>
          </div>

          <!-- CTA button -->
          <router-link
            v-if="statusConfig.cta"
            :to="{ name: statusConfig.ctaRoute }"
            class="flex-shrink-0 h-9 px-4 inline-flex items-center rounded-lg bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
          >
            {{ statusConfig.cta }}
          </router-link>
        </div>
      </div>

      <!-- ── Kelengkapan Profil ── -->
      <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-sm font-semibold text-[var(--color-text)]">Kelengkapan Profil</h2>
            <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ profileCompletion }}% profil terisi</p>
          </div>
          <router-link
            :to="{ name: 'alumni.profile' }"
            class="h-8 px-3 inline-flex items-center rounded-md border border-[var(--color-border)] text-xs font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
          >
            Lengkapi Profil
          </router-link>
        </div>
        <div class="h-2 rounded-full bg-[var(--color-surface-offset)] overflow-hidden mb-3">
          <div
            class="h-full rounded-full transition-all duration-700"
            :class="profileCompletion === 100 ? 'bg-[var(--color-success)]' : 'bg-[var(--color-primary)]'"
            :style="{ width: profileCompletion + '%' }"
          />
        </div>
        <div v-if="incompleteFields.length" class="flex flex-wrap gap-1.5">
          <span
            v-for="field in incompleteFields"
            :key="field"
            class="text-xs px-2 py-0.5 rounded-full bg-[var(--color-surface-offset)] text-[var(--color-text-muted)]"
          >
            {{ field }}
          </span>
        </div>
        <p v-else class="text-xs text-[var(--color-success)]">Profil sudah lengkap ✓</p>
      </div>

      <!-- ── Riwayat Pekerjaan ── -->
      <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-[var(--color-text)]">Riwayat Pekerjaan</h2>
          <router-link
            :to="{ name: 'alumni.employment' }"
            class="h-8 px-3 inline-flex items-center rounded-md border border-[var(--color-border)] text-xs font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
          >
            Kelola
          </router-link>
        </div>
        <div v-if="profile?.latest_work">
          <p class="text-sm font-medium text-[var(--color-text)]">{{ profile.latest_work.position }}</p>
          <p class="text-xs text-[var(--color-text-muted)]">{{ profile.latest_work.company_name }}</p>
          <Badge variant="success" dot class="mt-2">Pekerjaan Terkini</Badge>
        </div>
        <div v-else class="text-sm text-[var(--color-text-muted)]">
          Belum ada riwayat pekerjaan.
          <router-link :to="{ name: 'alumni.employment' }" class="text-[var(--color-primary)] hover:underline ml-1">Tambah sekarang</router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
@keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
.skeleton {
  background: linear-gradient(90deg, var(--color-surface-offset) 25%, var(--color-surface-dynamic) 50%, var(--color-surface-offset) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
</style>
