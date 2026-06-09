<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const router = useRouter()
const alumniStore = useAlumniStore()
const toast = useToast()

const showDeleteModal = ref(false)
const deleting = ref(false)
const activeTab = ref('profil')

const tabs = [
  { key: 'profil', label: 'Profil' },
  { key: 'riwayat_kerja', label: 'Riwayat Kerja' },
  { key: 'respons_survei', label: 'Respons Survei' },
]

const surveyBadge = {
  belum_disurvei: { variant: 'muted', label: 'Belum Disurvei' },
  terkirim: { variant: 'info', label: 'Terkirim' },
  sedang_mengisi: { variant: 'warning', label: 'Sedang Mengisi' },
  selesai: { variant: 'success', label: 'Selesai' },
}

const alumni = computed(() => alumniStore.current)

onMounted(() => alumniStore.fetchDetail(route.params.id))

async function confirmDelete() {
  deleting.value = true
  try {
    await alumniStore.destroy(alumni.value.id)
    toast.success(`Alumni "${alumni.value.full_name}" berhasil dihapus.`)
    router.push('/admin/alumni')
  } catch {
    toast.error('Gagal menghapus alumni.')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- Back -->
    <router-link
      to="/admin/alumni"
      class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition-colors"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
      Kembali ke Daftar Alumni
    </router-link>

    <!-- Loading -->
    <div v-if="alumniStore.loadingDetail" class="space-y-4">
      <div class="skeleton h-8 w-48 rounded" />
      <div class="skeleton h-64 rounded-lg" />
    </div>

    <template v-else-if="alumni">
      <!-- Header card -->
      <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-[var(--color-surface-offset)] overflow-hidden flex-shrink-0">
              <img
                v-if="alumni.photo_url"
                :src="alumni.photo_url"
                :alt="alumni.full_name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-xl font-bold text-[var(--color-text-faint)]">
                {{ alumni.full_name?.[0] }}
              </div>
            </div>
            <div>
              <h1 class="text-lg font-semibold text-[var(--color-text)]">{{ alumni.full_name }}</h1>
              <p class="text-sm text-[var(--color-text-muted)]">NIM: {{ alumni.nim }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ alumni.study_program?.name }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Badge :variant="surveyBadge[alumni.survey_status]?.variant ?? 'muted'" dot>
              {{ surveyBadge[alumni.survey_status]?.label }}
            </Badge>
            <router-link
              :to="`/admin/alumni/${alumni.id}/edit`"
              class="h-9 px-3 inline-flex items-center gap-2 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
            >
              Edit
            </router-link>
            <button
              class="h-9 px-3 rounded-md border border-[var(--color-error)] text-sm font-medium text-[var(--color-error)] hover:bg-[var(--color-error-highlight)] transition-colors"
              @click="showDeleteModal = true"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-[var(--color-border)]">
        <nav class="flex gap-1 -mb-px">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
              'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors',
              activeTab === tab.key
                ? 'border-[var(--color-primary)] text-[var(--color-primary)]'
                : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text)]',
            ]"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Profil Tab -->
      <div v-if="activeTab === 'profil'" class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
          <div v-for="field in [
            { label: 'NIM', value: alumni.nim },
            { label: 'NIK', value: alumni.nik },
            { label: 'Jenis Kelamin', value: alumni.gender === 'L' ? 'Laki-laki' : 'Perempuan' },
            { label: 'Tempat, Tgl Lahir', value: `${alumni.birth_place}, ${alumni.birth_date}` },
            { label: 'Program Studi', value: alumni.study_program?.name },
            { label: 'Angkatan', value: alumni.graduation_year?.year },
            { label: 'IPK', value: alumni.gpa },
            { label: 'Predikat', value: alumni.graduation_predicate },
            { label: 'Email', value: alumni.email },
            { label: 'Nomor WA', value: alumni.phone },
            { label: 'LinkedIn', value: alumni.linkedin_url },
            { label: 'Kota', value: alumni.address_city },
          ]" :key="field.label">
            <div>
              <dt class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</dt>
              <dd class="mt-0.5 text-sm text-[var(--color-text)]">{{ field.value ?? '—' }}</dd>
            </div>
          </div>
        </dl>
      </div>

      <!-- Riwayat Kerja Tab -->
      <div v-else-if="activeTab === 'riwayat_kerja'" class="space-y-3">
        <div v-if="!alumni.work_histories?.length" class="text-center py-12 text-[var(--color-text-muted)] text-sm">
          Belum ada riwayat pekerjaan.
        </div>
        <div
          v-for="work in alumni.work_histories"
          :key="work.id"
          class="bg-[var(--color-surface)] rounded-lg border border-[var(--color-border)] p-4"
        >
          <div class="flex justify-between flex-wrap gap-2">
            <div>
              <p class="font-medium text-[var(--color-text)]">{{ work.position }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ work.company_name }}</p>
            </div>
            <Badge :variant="work.is_current ? 'success' : 'muted'" dot>
              {{ work.is_current ? 'Masih Bekerja' : 'Selesai' }}
            </Badge>
          </div>
          <p class="text-xs text-[var(--color-text-faint)] mt-1">
            {{ work.start_date }} – {{ work.end_date ?? 'Sekarang' }}
          </p>
        </div>
      </div>

      <!-- Respons Survei Tab -->
      <div v-else-if="activeTab === 'respons_survei'" class="text-center py-12 text-[var(--color-text-muted)] text-sm">
        Fitur ini tersedia di Fase 4 (Sesi Survei).
      </div>
    </template>

    <!-- Delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Hapus Alumni"
      :message="`Hapus alumni &quot;${alumni?.full_name}&quot;? Data akan di-soft delete.`"
      confirm-label="Ya, Hapus"
      :loading="deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
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
