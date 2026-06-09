<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const alumniStore = useAlumniStore()
const toast = useToast()

const isEdit = computed(() => !!route.params.id)
const activeTab = ref('data_diri')

const tabs = [
  { key: 'data_diri', label: '1. Data Diri' },
  { key: 'akademik', label: '2. Akademik' },
  { key: 'alamat', label: '3. Alamat' },
  { key: 'kontak', label: '4. Kontak' },
  { key: 'akun', label: '5. Akun' },
]

const studyPrograms = ref([])
const graduationYears = ref([])

const form = ref({
  nim: '', nik: '', full_name: '', gender: 'L',
  birth_place: '', birth_date: '',
  study_program_id: null, graduation_year_id: null,
  thesis_title: '', gpa: null, graduation_predicate: '',
  address_street: '', address_village: '', address_district: '',
  address_city: '', address_province: '', address_postal_code: '',
  phone: '', email: '', linkedin_url: '',
})

const errors = ref({})
const loading = ref(false)

onMounted(async () => {
  const [spRes, gyRes] = await Promise.all([
    api.get('/public/study-programs'),
    api.get('/public/graduation-years'),
  ])
  studyPrograms.value = spRes.data.data
  graduationYears.value = gyRes.data.data

  if (isEdit.value) {
    await alumniStore.fetchDetail(route.params.id)
    if (alumniStore.current) {
      Object.keys(form.value).forEach((k) => {
        if (alumniStore.current[k] !== undefined) form.value[k] = alumniStore.current[k]
      })
    }
  }
})

async function submit() {
  errors.value = {}
  loading.value = true
  try {
    if (isEdit.value) {
      await alumniStore.update(route.params.id, form.value)
      toast.success('Data alumni berhasil diperbarui.')
    } else {
      await alumniStore.create(form.value)
      toast.success('Alumni berhasil ditambahkan.')
    }
    router.push('/admin/alumni')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
      toast.error('Periksa kembali isian form.')
    } else {
      toast.error(err.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-[var(--color-text)]">
          {{ isEdit ? 'Edit Alumni' : 'Tambah Alumni' }}
        </h1>
        <p class="text-sm text-[var(--color-text-muted)]">
          {{ isEdit ? 'Perbarui data alumni yang sudah ada.' : 'Tambah data alumni baru ke sistem.' }}
        </p>
      </div>
      <router-link
        to="/admin/alumni"
        class="h-9 px-4 inline-flex items-center gap-2 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
      >
        Batal
      </router-link>
    </div>

    <!-- Tabs -->
    <div class="border-b border-[var(--color-border)]">
      <nav class="flex gap-1 -mb-px overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="[
            'px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
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

    <form @submit.prevent="submit" class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6">

      <!-- Tab 1: Data Diri -->
      <div v-if="activeTab === 'data_diri'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="field in [
          { key: 'nim', label: 'NIM', type: 'text', required: true },
          { key: 'nik', label: 'NIK', type: 'text' },
          { key: 'full_name', label: 'Nama Lengkap', type: 'text', required: true, span: true },
          { key: 'birth_place', label: 'Tempat Lahir', type: 'text' },
          { key: 'birth_date', label: 'Tanggal Lahir', type: 'date' },
        ]" :key="field.key" :class="field.span ? 'sm:col-span-2' : ''">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">
              {{ field.label }} <span v-if="field.required" class="text-[var(--color-error)]">*</span>
            </span>
            <input
              v-model="form[field.key]"
              :type="field.type"
              :required="field.required"
              class="mt-1 w-full h-9 px-3 rounded-md border bg-[var(--color-surface-2)] text-sm text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent"
              :class="errors[field.key] ? 'border-[var(--color-error)]' : 'border-[var(--color-border)]'"
            />
            <p v-if="errors[field.key]" class="mt-1 text-xs text-[var(--color-error)]">{{ errors[field.key][0] }}</p>
          </label>
        </div>
        <!-- Gender -->
        <div>
          <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">Jenis Kelamin <span class="text-[var(--color-error)]">*</span></span>
          <div class="mt-2 flex gap-4">
            <label v-for="opt in [{ value: 'L', label: 'Laki-laki' }, { value: 'P', label: 'Perempuan' }]" :key="opt.value" class="inline-flex items-center gap-2 cursor-pointer">
              <input type="radio" :value="opt.value" v-model="form.gender" class="accent-[var(--color-primary)]" />
              <span class="text-sm">{{ opt.label }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Tab 2: Akademik -->
      <div v-else-if="activeTab === 'akademik'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">Program Studi <span class="text-[var(--color-error)]">*</span></span>
            <select v-model="form.study_program_id" required class="mt-1 w-full h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
              <option :value="null">-- Pilih Prodi --</option>
              <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
            </select>
          </label>
        </div>
        <div>
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">Angkatan <span class="text-[var(--color-error)]">*</span></span>
            <select v-model="form.graduation_year_id" required class="mt-1 w-full h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]">
              <option :value="null">-- Pilih Angkatan --</option>
              <option v-for="gy in graduationYears" :key="gy.id" :value="gy.id">{{ gy.year }} ({{ gy.academic_year }})</option>
            </select>
          </label>
        </div>
        <div v-for="field in [
          { key: 'gpa', label: 'IPK', type: 'number', step: '0.01', min: '0', max: '4' },
          { key: 'graduation_predicate', label: 'Predikat Kelulusan', type: 'text' },
        ]" :key="field.key">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</span>
            <input v-model="form[field.key]" :type="field.type" :step="field.step" :min="field.min" :max="field.max" class="mt-1 w-full h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          </label>
        </div>
        <div class="sm:col-span-2">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">Judul Skripsi/TA</span>
            <textarea v-model="form.thesis_title" rows="3" class="mt-1 w-full px-3 py-2 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
          </label>
        </div>
      </div>

      <!-- Tab 3: Alamat -->
      <div v-else-if="activeTab === 'alamat'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">Alamat Jalan</span>
            <textarea v-model="form.address_street" rows="2" class="mt-1 w-full px-3 py-2 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] resize-none" />
          </label>
        </div>
        <div v-for="field in [
          { key: 'address_village', label: 'Kelurahan/Desa' },
          { key: 'address_district', label: 'Kecamatan' },
          { key: 'address_city', label: 'Kota/Kabupaten' },
          { key: 'address_province', label: 'Provinsi' },
          { key: 'address_postal_code', label: 'Kode Pos' },
        ]" :key="field.key">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</span>
            <input v-model="form[field.key]" type="text" class="mt-1 w-full h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
          </label>
        </div>
      </div>

      <!-- Tab 4: Kontak -->
      <div v-else-if="activeTab === 'kontak'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="field in [
          { key: 'phone', label: 'Nomor WhatsApp', type: 'tel' },
          { key: 'email', label: 'Email', type: 'email' },
          { key: 'linkedin_url', label: 'URL LinkedIn', type: 'url', span: true },
        ]" :key="field.key" :class="field.span ? 'sm:col-span-2' : ''">
          <label class="block">
            <span class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide">{{ field.label }}</span>
            <input v-model="form[field.key]" :type="field.type" class="mt-1 w-full h-9 px-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-2)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" />
            <p v-if="errors[field.key]" class="mt-1 text-xs text-[var(--color-error)]">{{ errors[field.key][0] }}</p>
          </label>
        </div>
      </div>

      <!-- Tab 5: Akun -->
      <div v-else-if="activeTab === 'akun'" class="space-y-4">
        <p class="text-sm text-[var(--color-text-muted)]">Akun pengguna untuk alumni dibuat otomatis berdasarkan nomor WA atau email yang diisi di tab Kontak. Tidak perlu mengisi password secara manual.</p>
        <div class="p-4 rounded-lg bg-[var(--color-surface-offset)] text-sm text-[var(--color-text-muted)]">
          Login alumni dilakukan via OTP WhatsApp atau Email, bukan dengan password.<br />
          NIM digunakan sebagai identifier utama saat request OTP.
        </div>
      </div>

      <!-- Footer actions -->
      <div class="flex items-center justify-between pt-6 mt-6 border-t border-[var(--color-border)]">
        <div class="flex gap-2">
          <button
            v-if="activeTab !== tabs[0].key"
            type="button"
            class="h-9 px-4 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
            @click="activeTab = tabs[Math.max(0, tabs.findIndex(t => t.key === activeTab) - 1)].key"
          >
            ← Sebelumnya
          </button>
        </div>
        <div class="flex gap-2">
          <button
            v-if="activeTab !== tabs[tabs.length - 1].key"
            type="button"
            class="h-9 px-4 rounded-md bg-[var(--color-surface-offset)] text-sm font-medium text-[var(--color-text)] hover:bg-[var(--color-surface-dynamic)] transition-colors"
            @click="activeTab = tabs[Math.min(tabs.length - 1, tabs.findIndex(t => t.key === activeTab) + 1)].key"
          >
            Berikutnya →
          </button>
          <button
            v-else
            type="submit"
            :disabled="alumniStore.loadingSubmit"
            class="h-9 px-5 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors disabled:opacity-60"
          >
            <span v-if="alumniStore.loadingSubmit">Menyimpan...</span>
            <span v-else>{{ isEdit ? 'Simpan Perubahan' : 'Tambah Alumni' }}</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
