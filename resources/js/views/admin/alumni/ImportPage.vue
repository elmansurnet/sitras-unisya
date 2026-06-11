<script setup>
import { ref } from 'vue'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import FileUpload from '@/components/common/FileUpload.vue'
import Badge from '@/components/common/Badge.vue'
import api from '@/services/api'

const alumniStore = useAlumniStore()
const toast = useToast()

const file = ref(null)
const uploadProgress = ref(0)
const result = ref(null)
const fileError = ref(null)

async function downloadTemplate() {
  try {
    const res = await api.get('/admin/alumni/import-template', { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-import-alumni.xlsx'
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.error('Gagal mengunduh template.')
  }
}

async function submit() {
  if (!file.value) {
    fileError.value = 'Pilih file terlebih dahulu.'
    return
  }
  fileError.value = null
  uploadProgress.value = 0
  result.value = null

  const formData = new FormData()
  formData.append('file', file.value)

  try {
    await alumniStore.importAlumni(formData, (e) => {
      uploadProgress.value = Math.round((e.loaded * 100) / e.total)
    })
    result.value = alumniStore.importResult
    toast.success(`Import selesai: ${result.value?.imported ?? 0} data berhasil diimpor.`)
    file.value = null
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Import gagal.')
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-semibold text-[var(--color-text)]">Import Alumni</h1>
        <p class="text-sm text-[var(--color-text-muted)]">Upload file Excel untuk menambahkan data alumni secara massal.</p>
      </div>
      <router-link
        to="/admin/alumni"
        class="h-9 px-4 inline-flex items-center rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
      >
        Kembali
      </router-link>
    </div>

    <!-- Panduan -->
    <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6 space-y-4">
      <h2 class="font-semibold text-[var(--color-text)]">Panduan Import</h2>
      <ol class="list-decimal list-inside text-sm text-[var(--color-text-muted)] space-y-1.5">
        <li>Unduh template Excel dengan klik tombol di bawah.</li>
        <li>Isi data alumni sesuai format kolom dalam template.</li>
        <li>Kolom wajib: NIM, Nama Lengkap, Jenis Kelamin, Kode Prodi, Angkatan.</li>
        <li>Upload kembali file yang sudah diisi.</li>
        <li>Sistem akan memvalidasi setiap baris dan melaporkan error baris per baris.</li>
      </ol>
      <button
        type="button"
        class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-[var(--color-border)] text-sm font-medium text-[var(--color-text-muted)] hover:bg-[var(--color-surface-offset)] transition-colors"
        @click="downloadTemplate"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
        Unduh Template Excel
      </button>
    </div>

    <!-- Upload area -->
    <div class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6 space-y-4">
      <h2 class="font-semibold text-[var(--color-text)]">Upload File</h2>
      <FileUpload
        v-model="file"
        accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        :max-size-mb="10"
        label="Klik atau seret file Excel (.xlsx)"
        :error="fileError"
        :loading="alumniStore.loadingImport"
      />

      <!-- Progress -->
      <div v-if="alumniStore.loadingImport && uploadProgress > 0" class="space-y-1">
        <div class="flex justify-between text-xs text-[var(--color-text-muted)]">
          <span>Upload...</span>
          <span>{{ uploadProgress }}%</span>
        </div>
        <div class="h-2 rounded-full bg-[var(--color-surface-offset)] overflow-hidden">
          <div
            class="h-full bg-[var(--color-primary)] rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          />
        </div>
      </div>

      <button
        type="button"
        :disabled="!file || alumniStore.loadingImport"
        class="h-9 px-5 rounded-md bg-[var(--color-primary)] text-white text-sm font-medium hover:bg-[var(--color-primary-hover)] transition-colors disabled:opacity-60"
        @click="submit"
      >
        <span v-if="alumniStore.loadingImport">Memproses...</span>
        <span v-else>Mulai Import</span>
      </button>
    </div>

    <!-- Result -->
    <div v-if="result" class="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-6 space-y-4">
      <h2 class="font-semibold text-[var(--color-text)]">Hasil Import</h2>
      <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--color-success-highlight)]">
          <span class="text-sm font-semibold text-[var(--color-success)]">{{ result.imported }}</span>
          <span class="text-xs text-[var(--color-success)]">Berhasil</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--color-error-highlight)]">
          <span class="text-sm font-semibold text-[var(--color-error)]">{{ result.failed }}</span>
          <span class="text-xs text-[var(--color-error)]">Gagal</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--color-surface-offset)]">
          <span class="text-sm font-semibold text-[var(--color-text)]">{{ result.total }}</span>
          <span class="text-xs text-[var(--color-text-muted)]">Total Baris</span>
        </div>
      </div>

      <!-- Error rows -->
      <div v-if="result.errors?.length" class="space-y-1.5">
        <p class="text-xs font-medium text-[var(--color-error)] uppercase tracking-wide">Baris yang Gagal</p>
        <div class="max-h-60 overflow-y-auto space-y-1">
          <div
            v-for="(err, i) in result.errors"
            :key="i"
            class="text-xs flex gap-2 p-2 rounded bg-[var(--color-error-highlight)] text-[var(--color-error)]"
          >
            <span class="font-medium whitespace-nowrap">Baris {{ err.row }}:</span>
            <span>{{ Array.isArray(err.errors) ? err.errors.join(', ') : err.errors }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
