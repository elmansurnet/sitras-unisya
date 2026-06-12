<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const alumniStore = useAlumniStore()
const authStore = useAuthStore()

const alumni = computed(() => authStore.user?.alumni)

onMounted(() => {
  alumniStore.fetchMyProfile()
})

function goToEdit() {
  router.push({ name: 'alumni.profile.edit' })
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-6 px-4 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
      <button class="btn-primary flex items-center gap-2" @click="goToEdit">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Edit Profil
      </button>
    </div>

    <!-- Photo & Name -->
    <div class="card p-6 flex items-center gap-6">
      <img
        v-if="alumni?.photo_url"
        :src="alumni.photo_url"
        :alt="alumni.full_name"
        class="w-20 h-20 rounded-full object-cover ring-2 ring-primary-100"
      />
      <div
        v-else
        class="w-20 h-20 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-2xl font-bold"
      >
        {{ alumni?.full_name?.charAt(0)?.toUpperCase() }}
      </div>
      <div>
        <h2 class="text-xl font-bold text-gray-900">{{ alumni?.full_name }}</h2>
        <p class="text-sm text-gray-500">NIM: {{ alumni?.nim }}</p>
        <p class="text-sm text-gray-500">{{ alumni?.study_program?.name }} · {{ alumni?.graduation_year?.academic_year }}</p>
      </div>
    </div>

    <!-- Data Pribadi -->
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Data Pribadi</h3>
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <dt class="text-xs text-gray-400">NIK</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.nik ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-400">Jenis Kelamin</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.gender === 'L' ? 'Laki-laki' : alumni?.gender === 'P' ? 'Perempuan' : '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-400">Tempat Lahir</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.birthplace ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-400">Tanggal Lahir</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.birthdate ?? '-' }}</dd>
        </div>
      </dl>
    </div>

    <!-- Kontak -->
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Kontak</h3>
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <dt class="text-xs text-gray-400">No. WhatsApp</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.phone ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-400">Email</dt>
          <dd class="text-sm font-medium text-gray-800">{{ alumni?.email ?? '-' }}</dd>
        </div>
        <div class="md:col-span-2">
          <dt class="text-xs text-gray-400">LinkedIn</dt>
          <dd class="text-sm">
            <a
              v-if="alumni?.linkedin_url"
              :href="alumni.linkedin_url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary-600 hover:underline"
            >{{ alumni.linkedin_url }}</a>
            <span v-else class="text-gray-800">-</span>
          </dd>
        </div>
      </dl>
    </div>

    <!-- Alamat -->
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Alamat</h3>
      <p class="text-sm text-gray-800">
        {{ [alumni?.address_street, alumni?.address_village, alumni?.address_district, alumni?.address_city, alumni?.address_province, alumni?.address_postal_code].filter(Boolean).join(', ') || '-' }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
</style>
