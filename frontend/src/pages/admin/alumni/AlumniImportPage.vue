<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAlumniStore } from '@/stores/alumni'
import { useToast } from '@/composables/useToast'
import FileUpload from '@/components/common/FileUpload.vue'

const router = useRouter()
const alumniStore = useAlumniStore()
const { showToast } = useToast()

const uploading = ref(false)
const importResult = ref(null)
const progress = ref(0)

const acceptedExtensions = ['.xlsx', '.xls']

function goBack() {
  router.push({ name: 'admin.alumni' })
}

async function handleDownloadTemplate() {
  try {
    await alumniStore.downloadImportTemplate()
  } catch {
    showToast('Gagal mengunduh template.', 'error')
  }
}

async function handleUpload(file) {
  uploading.value = true
  importResult.value = null
  progress.value = 0

  const interval = setInterval(() => {
    if (progress.value < 85) progress.value += 10
  }, 300)

  try {
    const result = await alumniStore.importAlumni(file)
    progress.value = 100
    importResult.value = result
    showToast(`Import selesai: ${result.success} berhasil, ${result.failed} gagal.`, result.failed > 0 ? 'warning' : 'success')
  } catch (err) {
    showToast('Import gagal. Periksa format file Anda.', 'error')
  } finally {
    clearInterval(interval)
    uploading.value = false
  }
}
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center gap-3 mb-6">
      <button class="text-gray-500 hover:text-gray-700 p-1 rounded" @click="goBack">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Import Alumni</h1>
        <p class="text-sm text-gray-500 mt-0.5">Upload file Excel untuk menambahkan data alumni secara massal</p>
      </div>
    </div>

    <div class="max-w-2xl space-y-6">
      <!-- Step 1: Download Template -->
      <div class="card p-6">
        <div class="flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm flex-shrink-0">1</div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Unduh Template Excel</h3>
            <p class="text-sm text-gray-500 mt-1">Gunakan template resmi untuk memastikan format data yang benar.</p>
            <button
              class="mt-3 btn-secondary flex items-center gap-2"
              @click="handleDownloadTemplate"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Download Template
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: Upload -->
      <div class="card p-6">
        <div class="flex items-start gap-4">
          <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm flex-shrink-0">2</div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Upload File Excel</h3>
            <p class="text-sm text-gray-500 mt-1">Format .xlsx atau .xls, maksimal 10MB.</p>
            <div class="mt-3">
              <FileUpload
                :accept="acceptedExtensions"
                :max-size-kb="10240"
                label="Upload File Excel"
                hint="Format .xlsx atau .xls, maks. 10MB"
                :disabled="uploading"
                @upload="handleUpload"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Progress -->
      <div v-if="uploading" class="card p-6">
        <div class="flex items-center gap-3 mb-3">
          <svg class="w-5 h-5 text-primary-600 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          <span class="text-sm font-medium text-gray-700">Memproses import...</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-primary-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${progress}%` }"
          />
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ progress }}%</p>
      </div>

      <!-- Result -->
      <div v-if="importResult" class="card p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Hasil Import</h3>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-700">{{ importResult.success }}</p>
            <p class="text-sm text-green-600">Berhasil</p>
          </div>
          <div class="text-center p-4 bg-red-50 rounded-lg">
            <p class="text-2xl font-bold text-red-700">{{ importResult.failed }}</p>
            <p class="text-sm text-red-600">Gagal</p>
          </div>
          <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <p class="text-2xl font-bold text-yellow-700">{{ importResult.skipped ?? 0 }}</p>
            <p class="text-sm text-yellow-600">Dilewati (Duplikat)</p>
          </div>
        </div>

        <!-- Error rows -->
        <div v-if="importResult.errors?.length" class="mt-4">
          <h4 class="text-sm font-semibold text-red-600 mb-2">Detail Error:</h4>
          <div class="max-h-48 overflow-y-auto border border-red-200 rounded-lg">
            <table class="w-full text-xs">
              <thead class="bg-red-50">
                <tr>
                  <th class="px-3 py-2 text-left text-red-700">Baris</th>
                  <th class="px-3 py-2 text-left text-red-700">NIM</th>
                  <th class="px-3 py-2 text-left text-red-700">Pesan Error</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="err in importResult.errors"
                  :key="err.row"
                  class="border-t border-red-100"
                >
                  <td class="px-3 py-2">{{ err.row }}</td>
                  <td class="px-3 py-2">{{ err.nim ?? '-' }}</td>
                  <td class="px-3 py-2 text-red-600">{{ err.message }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex gap-3 mt-5">
          <button class="btn-primary" @click="goBack">Kembali ke Daftar Alumni</button>
          <button class="btn-secondary" @click="importResult = null">Import Lagi</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-primary { @apply bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors; }
.btn-secondary { @apply bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors; }
.card { @apply bg-white rounded-xl shadow-card border border-gray-100; }
</style>
