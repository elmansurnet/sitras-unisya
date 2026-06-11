<script setup>
/**
 * EmploymentPage.vue — Riwayat Pekerjaan Alumni
 * Route: /alumni/employment (name: alumni.employment)
 * Sesuai 06_UI_UX.md §2.2 & §8
 * API: GET/POST/PUT/DELETE /api/v1/alumni/work-histories (05_API.md §7)
 */
import { ref, computed, onMounted } from 'vue'
import { useWorkHistoryStore } from '@/stores/workHistory'
import { useToast } from '@/composables/useToast'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const workStore = useWorkHistoryStore()
const toast = useToast()

const showForm = ref(false)
const editTarget = ref(null)
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)
const saving = ref(false)
const errors = ref({})

const list = computed(() => workStore.list)
const loading = computed(() => workStore.loading)

const emptyForm = () => ({
  company_name: '',
  position: '',
  industry_sector: '',
  employment_type: 'full_time',
  start_date: '',
  end_date: '',
  is_current: false,
  salary_range: '',
  job_description: '',
  city: '',
  country: 'Indonesia',
})

const form = ref(emptyForm())

const employmentTypes = [
  { value: 'full_time',  label: 'Full Time' },
  { value: 'part_time',  label: 'Part Time' },
  { value: 'contract',   label: 'Kontrak' },
  { value: 'freelance',  label: 'Freelance' },
  { value: 'internship', label: 'Magang' },
  { value: 'wirausaha',  label: 'Wirausaha' },
]

function openAdd() {
  editTarget.value = null
  form.value = emptyForm()
  errors.value = {}
  showForm.value = true
}

function openEdit(item) {
  editTarget.value = item
  form.value = { ...item }
  errors.value = {}
  showForm.value = true
}

function cancelForm() {
  showForm.value = false
  editTarget.value = null
  errors.value = {}
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editTarget.value) {
      await workStore.update(editTarget.value.id, form.value)
      toast.success('Riwayat pekerjaan berhasil diperbarui.')
    } else {
      await workStore.store(form.value)
      toast.success('Riwayat pekerjaan berhasil ditambahkan.')
    }
    cancelForm()
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      toast.error('Gagal menyimpan data.')
    }
  } finally {
    saving.value = false
  }
}

function openDelete(item) {
  deleteTarget.value = item
  showDeleteModal.value = true
}

async function confirmDelete() {
  deleting.value = true
  try {
    await workStore.destroy(deleteTarget.value.id)
    toast.success('Riwayat pekerjaan berhasil dihapus.')
  } catch {
    toast.error('Gagal menghapus data.')
  } finally {
    deleting.value = false
    showDeleteModal.value = false
    deleteTarget.value = null
  }
}

onMounted(() => workStore.fetchList())
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-[var(--color-text)]">Riwayat Pekerjaan</h1>
        <p class="text-sm text-[var(--color-text-muted)]">Kelola data pengalaman kerja Anda.</p>
      </div>
      <button
        class="h-9 px-4 inline-flex items-center gap-2 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors"
        @click="openAdd"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Tambah Pekerjaan
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
      <div class="skeleton h-24 rounded-xl" />
      <div class="skeleton h-24 rounded-xl" />
    </div>

    <!-- Empty -->
    <div v-else-if="!list.length" class="text-center py-16 space-y-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-[var(--color-text-faint)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
      <p class="text-sm font-medium text-[var(--color-text-muted)]">Belum ada riwayat pekerjaan.</p>
      <p class="text-xs text-[var(--color-text-faint)]">Tambahkan pengalaman kerja pertama Anda.</p>
      <button
        class="h-9 px-4 inline-flex items-center gap-2 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors mx-auto"
        @click="openAdd"
      >
        + Tambah Pekerjaan
      </button>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="item in list"
        :key="item.id"
        class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-5"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-medium text-[var(--color-text)] truncate">{{ item.position }}</p>
              <Badge v-if="item.is_current" variant="success" dot>Saat ini</Badge>
            </div>
            <p class="text-sm text-[var(--color-text-muted)]">{{ item.company_name }}</p>
            <div class="flex items-center gap-3 mt-1 flex-wrap">
              <span class="text-xs text-[var(--color-text-faint)]">
                {{ item.start_date }} – {{ item.is_current ? 'Sekarang' : (item.end_date ?? '—') }}
              </span>
              <span v-if="item.city" class="text-xs text-[var(--color-text-faint)]">📍 {{ item.city }}</span>
              <span v-if="item.employment_type" class="text-xs px-2 py-0.5 rounded-full bg-[var(--color-surface-offset)] text-[var(--color-text-muted)]">
                {{ employmentTypes.find(t => t.value === item.employment_type)?.label ?? item.employment_type }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-1.5 flex-shrink-0">
            <button
              class="p-1.5 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] hover:text-[var(--color-primary)] transition-colors"
              aria-label="Edit pekerjaan"
              @click="openEdit(item)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </button>
            <button
              class="p-1.5 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-error-highlight)] hover:text-[var(--color-error)] transition-colors"
              aria-label="Hapus pekerjaan"
              @click="openDelete(item)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Slide-in Panel -->
    <Transition name="slide">
      <div
        v-if="showForm"
        class="fixed inset-0 z-40 flex justify-end"
        aria-modal="true"
        role="dialog"
      >
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/40" @click="cancelForm" />
        <!-- Panel -->
        <div class="relative w-full max-w-lg bg-[var(--color-surface)] shadow-xl overflow-y-auto">
          <div class="sticky top-0 bg-[var(--color-surface)] border-b border-[var(--color-border)] px-6 py-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-[var(--color-text)]">{{ editTarget ? 'Edit Pekerjaan' : 'Tambah Pekerjaan' }}</h2>
            <button class="p-1.5 rounded-md hover:bg-[var(--color-surface-offset)] transition-colors" aria-label="Tutup" @click="cancelForm">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <form class="p-6 space-y-4" @submit.prevent="save">
            <!-- Company & Position -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Nama Perusahaan <span class="text-[var(--color-error)]">*</span></label>
              <input v-model="form.company_name" type="text" class="w-full h-9 px-3 rounded-md border text-sm transition-colors" :class="errors.company_name ? 'border-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'" required />
              <p v-if="errors.company_name" class="text-xs text-[var(--color-error)] mt-1">{{ errors.company_name[0] }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Jabatan / Posisi <span class="text-[var(--color-error)]">*</span></label>
              <input v-model="form.position" type="text" class="w-full h-9 px-3 rounded-md border text-sm transition-colors" :class="errors.position ? 'border-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'" required />
              <p v-if="errors.position" class="text-xs text-[var(--color-error)] mt-1">{{ errors.position[0] }}</p>
            </div>

            <!-- Employment type & Industry -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Tipe Pekerjaan</label>
                <select v-model="form.employment_type" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] bg-[var(--color-surface)] transition-colors">
                  <option v-for="t in employmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Kota</label>
                <input v-model="form.city" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" />
              </div>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Tanggal Mulai <span class="text-[var(--color-error)]">*</span></label>
                <input v-model="form.start_date" type="date" class="w-full h-9 px-3 rounded-md border text-sm transition-colors" :class="errors.start_date ? 'border-[var(--color-error)]' : 'border-[var(--color-border)] focus:border-[var(--color-primary)]'" required />
                <p v-if="errors.start_date" class="text-xs text-[var(--color-error)] mt-1">{{ errors.start_date[0] }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Tanggal Selesai</label>
                <input v-model="form.end_date" type="date" :disabled="form.is_current" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors disabled:opacity-50" />
              </div>
            </div>

            <!-- Is Current -->
            <label class="flex items-center gap-2 text-sm text-[var(--color-text)] cursor-pointer">
              <input
                v-model="form.is_current"
                type="checkbox"
                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--color-primary)]"
                @change="if (form.is_current) form.end_date = ''"
              />
              Ini adalah pekerjaan saya saat ini
            </label>

            <!-- Industry -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Sektor Industri</label>
              <input v-model="form.industry_sector" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" placeholder="mis. Teknologi Informasi" />
            </div>

            <!-- Salary range -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Rentang Gaji</label>
              <input v-model="form.salary_range" type="text" class="w-full h-9 px-3 rounded-md border border-[var(--color-border)] text-sm focus:border-[var(--color-primary)] transition-colors" placeholder="mis. 5.000.000 – 7.000.000" />
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button" class="h-9 px-4 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors" @click="cancelForm">Batal</button>
              <button type="submit" :disabled="saving" class="h-9 px-5 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors disabled:opacity-60">
                <span v-if="saving">Menyimpan...</span>
                <span v-else>Simpan</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Delete modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Hapus Pekerjaan"
      :message="`Hapus riwayat pekerjaan di &quot;${deleteTarget?.company_name}&quot;?`"
      confirm-label="Ya, Hapus"
      variant="danger"
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
.slide-enter-active, .slide-leave-active { transition: transform 200ms ease-out; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
