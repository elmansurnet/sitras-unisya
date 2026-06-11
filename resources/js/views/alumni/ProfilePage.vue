<script setup>
/**
 * ProfilePage.vue — Profil Alumni
 * Route: /alumni/profile (name: alumni.profile)
 * Sesuai 06_UI_UX.md §2.2 & §8
 * API: GET/PUT /api/v1/alumni/profile (05_API.md §7)
 */
import { ref, computed, onMounted } from 'vue'
import { useAlumniProfileStore } from '@/stores/alumniProfile'
import { useToast } from '@/composables/useToast'

const profileStore = useAlumniProfileStore()
const toast = useToast()

const editing = ref(false)
const saving = ref(false)

const profile = computed(() => profileStore.profile)
const loading = computed(() => profileStore.loading)

// Form data (hanya field yang bisa diupdate alumni)
const form = ref({
  phone: '',
  email: '',
  address_street: '',
  address_village: '',
  address_district: '',
  address_city: '',
  address_province: '',
  address_postal_code: '',
  linkedin_url: '',
})

const errors = ref({})

function startEdit() {
  if (!profile.value) return
  form.value = {
    phone:               profile.value.phone ?? '',
    email:               profile.value.email ?? '',
    address_street:      profile.value.address_street ?? '',
    address_village:     profile.value.address_village ?? '',
    address_district:    profile.value.address_district ?? '',
    address_city:        profile.value.address_city ?? '',
    address_province:    profile.value.address_province ?? '',
    address_postal_code: profile.value.address_postal_code ?? '',
    linkedin_url:        profile.value.linkedin_url ?? '',
  }
  errors.value = {}
  editing.value = true
}

function cancelEdit() {
  editing.value = false
  errors.value = {}
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    await profileStore.updateProfile(form.value)
    toast.success('Profil berhasil diperbarui.')
    editing.value = false
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      toast.error('Gagal menyimpan profil.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (!profile.value) profileStore.fetchProfile()
})
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-[var(--color-text)]">Profil Saya</h1>
        <p class="text-sm text-[var(--color-text-muted)]">Data pribadi dan informasi kontak Anda.</p>
      </div>
      <button
        v-if="!editing && profile"
        class="h-9 px-4 inline-flex items-center gap-2 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
        @click="startEdit"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
        Edit Profil
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div class="skeleton h-32 rounded-xl" />
      <div class="skeleton h-48 rounded-xl" />
    </div>

    <template v-else-if="profile">
      <!-- ── Read Mode ── -->
      <template v-if="!editing">
        <!-- Data Akademik (read-only) -->
        <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
          <h2 class="text-sm font-semibold text-[var(--color-text)] mb-4">Data Akademik</h2>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
            <div v-for="field in [
              { label: 'Nama Lengkap', value: profile.full_name },
              { label: 'NIM', value: profile.nim },
              { label: 'Program Studi', value: profile.study_program?.name },
              { label: 'Angkatan', value: profile.graduation_year?.year },
              { label: 'IPK', value: profile.gpa },
              { label: 'Predikat', value: profile.graduation_predicate },
            ]" :key="field.label">
              <div>
                <dt class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</dt>
                <dd class="mt-0.5 text-sm text-[var(--color-text)]">{{ field.value ?? '—' }}</dd>
              </div>
            </div>
          </dl>
        </div>

        <!-- Data Pribadi & Kontak -->
        <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">
          <h2 class="text-sm font-semibold text-[var(--color-text)] mb-4">Data Pribadi & Kontak</h2>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
            <div v-for="field in [
              { label: 'Email', value: profile.email },
              { label: 'Nomor WhatsApp', value: profile.phone },
              { label: 'LinkedIn', value: profile.linkedin_url },
              { label: 'Kota', value: profile.address_city },
              { label: 'Provinsi', value: profile.address_province },
              { label: 'Kode Pos', value: profile.address_postal_code },
            ]" :key="field.label">
              <div>
                <dt class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</dt>
                <dd class="mt-0.5 text-sm text-[var(--color-text)]">{{ field.value ?? '—' }}</dd>
              </div>
            </div>
          </dl>
        </div>
      </template>

      <!-- ── Edit Mode ── -->
      <form v-else class="space-y-5" @submit.prevent="save">
        <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[var(--color-text)]">Edit Informasi Kontak & Alamat</h2>

          <!-- Kontak -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Nomor WhatsApp</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full h-9 px-3 rounded-md border text-sm transition-colors"
                :class="errors.phone ? 'border-[var(--color-error)] focus:ring-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'"
                placeholder="08xxxxxxxxxx"
              />
              <p v-if="errors.phone" class="text-xs text-[var(--color-error)] mt-1">{{ errors.phone[0] }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Email</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full h-9 px-3 rounded-md border text-sm transition-colors"
                :class="errors.email ? 'border-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'"
                placeholder="nama@email.com"
              />
              <p v-if="errors.email" class="text-xs text-[var(--color-error)] mt-1">{{ errors.email[0] }}</p>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">URL LinkedIn</label>
              <input
                v-model="form.linkedin_url"
                type="url"
                class="w-full h-9 px-3 rounded-md border text-sm transition-colors"
                :class="errors.linkedin_url ? 'border-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'"
                placeholder="https://linkedin.com/in/username"
              />
              <p v-if="errors.linkedin_url" class="text-xs text-[var(--color-error)] mt-1">{{ errors.linkedin_url[0] }}</p>
            </div>
          </div>

          <!-- Alamat -->
          <div class="border-t border-[var(--color-border)] pt-4">
            <p class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide mb-3">Alamat</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Jalan</label>
                <input v-model="form.address_street" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" placeholder="Jl. ..." />
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Kelurahan</label>
                <input v-model="form.address_village" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Kecamatan</label>
                <input v-model="form.address_district" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Kota / Kabupaten</label>
                <input v-model="form.address_city" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" />
                <p v-if="errors.address_city" class="text-xs text-[var(--color-error)] mt-1">{{ errors.address_city[0] }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Provinsi</label>
                <input v-model="form.address_province" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Kode Pos</label>
                <input v-model="form.address_postal_code" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" maxlength="5" />
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <button
            type="button"
            class="h-9 px-4 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
            @click="cancelEdit"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="h-9 px-5 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors disabled:opacity-60"
          >
            <span v-if="saving">Menyimpan...</span>
            <span v-else>Simpan Perubahan</span>
          </button>
        </div>
      </form>
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
