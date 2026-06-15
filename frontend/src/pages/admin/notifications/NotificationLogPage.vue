<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import Pagination from '@/components/common/Pagination.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'

const store = useNotificationStore()
const { toast } = useToast()
const localFilters = ref({ type: '', status: '', recipient_type: '', date_from: '', date_to: '' })
const showDetailModal = ref(false)
const selectedLog = ref(null)

const columns = [
  { key: 'type', label: 'Channel' },
  { key: 'recipient', label: 'Penerima' },
  { key: 'status', label: 'Status' },
  { key: 'sent_at', label: 'Waktu Kirim' },
  { key: 'actions', label: 'Detail' },
]

const totalInfo = computed(() => {
  const total = store.pagination?.total ?? 0
  const currentPage = store.pagination?.current_page ?? 1
  const perPage = store.pagination?.per_page ?? 20
  const from = total ? (currentPage - 1) * perPage + 1 : 0
  const to = total ? Math.min(currentPage * perPage, total) : 0
  return total > 0 ? `Menampilkan ${from}–${to} dari ${total} log` : 'Tidak ada log ditemukan'
})

async function loadLogs() {
  try {
    await store.fetchLogs()
  } catch {
    toast.error('Gagal memuat log notifikasi.')
  }
}

onMounted(() => {
  localFilters.value = { ...store.filters }
  loadLogs()
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Log Notifikasi</h1>
      <p class="mt-1 text-sm text-gray-500">Riwayat pengiriman notifikasi WhatsApp dan email.</p>
    </div>

    <div class="card p-4">
      <p class="text-sm text-gray-500">{{ totalInfo }}</p>
    </div>

    <div class="card p-4">
      <DataTable :columns="columns" :rows="store.logs ?? []" :loading="store.loading" empty-message="Belum ada log notifikasi." />
      <Pagination v-if="store.pagination?.last_page > 1" :meta="store.pagination" @change="store.fetchLogs" />
    </div>
  </div>
</template>
