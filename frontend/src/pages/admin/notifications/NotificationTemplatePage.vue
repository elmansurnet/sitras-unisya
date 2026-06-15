<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import DataTable from '@/components/common/DataTable.vue'
import Badge from '@/components/common/Badge.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useNotificationStore } from '@/stores/notification'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const store = useNotificationStore()
const { toast } = useToast()
const { confirm } = useConfirm()
const filterType = ref('')
const filterEvent = ref('')
const showModal = ref(false)

async function loadTemplates() {
  try {
    await store.fetchTemplates({ type: filterType.value || undefined, event: filterEvent.value || undefined })
  } catch {
    toast.error('Gagal memuat template notifikasi.')
  }
}

onMounted(loadTemplates)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Template Notifikasi</h1>
      <p class="mt-1 text-sm text-gray-500">Kelola template pesan WhatsApp dan Email untuk notifikasi sistem.</p>
    </div>

    <div class="card p-4">
      <DataTable :columns="[{ key: 'name', label: 'Nama Template' }, { key: 'type', label: 'Channel' }, { key: 'event', label: 'Event' } ]" :rows="store.templates ?? []" :loading="store.loading" empty-message="Belum ada template notifikasi." />
    </div>
  </div>
</template>
