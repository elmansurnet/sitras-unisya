<template>
  <div class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Kuesioner</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola kuesioner untuk alumni dan employer.</p>
      </div>
      <button
        @click="$router.push({ name: 'admin.questionnaires.create' })"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Buat Kuesioner
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        <!-- Search -->
        <div class="xl:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Judul kuesioner..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            @keyup.enter="applyFilters"
          />
        </div>

        <!-- Filter Tipe -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Tipe</label>
          <select
            v-model="filters.type"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          >
            <option value="">Semua Tipe</option>
            <option value="alumni">Alumni</option>
            <option value="employer">Employer</option>
          </select>
        </div>

        <!-- Filter Status -->
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
          >
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <!-- Actions -->
        <div class="flex items-end gap-2">
          <button
            @click="applyFilters"
            class="flex-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
          >
            Filter
          </button>
          <button
            v-if="store.hasFilters"
            @click="resetFilters"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
            title="Reset filter"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

      <!-- Loading -->
      <div v-if="store.loading" class="space-y-3 p-4">
        <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded bg-gray-100" />
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!store.list.length"
        class="flex flex-col items-center justify-center py-16 text-gray-400"
      >
        <svg class="h-12 w-12 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V19.5a2.25 2.25 0 0 0 2.25 2.25h.75" />
        </svg>
        <p class="font-medium">Belum ada kuesioner</p>
        <p class="text-sm mt-1">Klik <strong>Buat Kuesioner</strong> untuk membuat kuesioner baru.</p>
      </div>

      <!-- Table data -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Judul</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Tipe</th>
              <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Seksi</th>
              <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Pertanyaan</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Dibuat</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="q in store.list"
              :key="q.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- Judul & deskripsi -->
              <td class="px-4 py-3 max-w-xs">
                <div class="font-medium text-gray-900 text-sm truncate">{{ q.title }}</div>
                <div v-if="q.description" class="text-xs text-gray-400 truncate mt-0.5">{{ q.description }}</div>
              </td>

              <!-- Tipe -->
              <td class="px-4 py-3">
                <span :class="typeBadge(q.type)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                  {{ q.type ?? '—' }}
                </span>
              </td>

              <!-- Seksi -->
              <td class="px-4 py-3 text-center text-sm text-gray-700 tabular-nums">
                {{ q.sections_count ?? '—' }}
              </td>

              <!-- Pertanyaan -->
              <td class="px-4 py-3 text-center text-sm text-gray-700 tabular-nums">
                {{ q.questions_count ?? '—' }}
              </td>

              <!-- Status -->
              <td class="px-4 py-3">
                <span :class="statusBadge(q.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ statusLabel(q.status) }}
                </span>
              </td>

              <!-- Tanggal dibuat -->
              <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                {{ formatDate(q.created_at) }}
              </td>

              <!-- Aksi -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <!-- Preview -->
                  <button
                    @click="$router.push({ name: 'admin.questionnaires.preview', params: { id: q.id } })"
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="Preview"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.574-3.007-9.964-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </button>

                  <!-- Builder / Edit -->
                  <button
                    v-if="q.status !== 'archived'"
                    @click="$router.push({ name: 'admin.questionnaires.builder', params: { id: q.id } })"
                    class="rounded p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600"
                    title="Buka Builder"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                  </button>

                  <!-- Publish (hanya draft) -->
                  <button
                    v-if="q.status === 'draft'"
                    @click="confirmPublish(q)"
                    class="rounded p-1.5 text-gray-400 hover:bg-green-50 hover:text-green-600"
                    title="Publish"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                  </button>

                  <!-- Archive (hanya published) -->
                  <button
                    v-if="q.status === 'published'"
                    @click="confirmArchive(q)"
                    class="rounded p-1.5 text-gray-400 hover:bg-amber-50 hover:text-amber-600"
                    title="Archive"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-.375c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v.375c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                  </button>

                  <!-- Delete (hanya draft) -->
                  <button
                    v-if="q.status === 'draft'"
                    @click="confirmDelete(q)"
                    class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                    title="Hapus"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="store.pagination.total > 0"
        class="flex items-center justify-between border-t border-gray-200 px-4 py-3"
      >
        <p class="text-sm text-gray-500">
          Menampilkan
          {{ (store.pagination.currentPage - 1) * store.pagination.perPage + 1 }}–{{
            Math.min(store.pagination.currentPage * store.pagination.perPage, store.pagination.total)
          }}
          dari {{ store.pagination.total }} kuesioner
        </p>
        <div class="flex gap-2">
          <button
            :disabled="store.pagination.currentPage <= 1"
            @click="changePage(store.pagination.currentPage - 1)"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            &laquo; Prev
          </button>
          <button
            :disabled="store.pagination.currentPage >= store.pagination.lastPage"
            @click="changePage(store.pagination.currentPage + 1)"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Next &raquo;
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Modal (Publish / Archive / Delete) -->
    <Teleport to="body">
      <div
        v-if="modal.show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="modal.show = false"
      >
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
          <h2 class="text-base font-semibold text-gray-900 mb-2">{{ modal.title }}</h2>
          <p class="text-sm text-gray-500 mb-6">{{ modal.message }}</p>

          <!-- Error inline -->
          <p v-if="store.error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ store.error }}
          </p>

          <div class="flex justify-end gap-3">
            <button
              @click="modal.show = false"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
            >
              Batal
            </button>
            <button
              :disabled="store.loading"
              @click="modal.action"
              :class="modal.confirmClass"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              <span v-if="store.loading" class="inline-flex items-center gap-1">
                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                Memproses...
              </span>
              <span v-else>{{ modal.confirmLabel }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useQuestionnaireStore } from '@/stores/questionnaire'

const store = useQuestionnaireStore()

const filters = reactive({ ...store.filters })

const modal = reactive({
  show: false,
  title: '',
  message: '',
  confirmLabel: 'Ya',
  confirmClass: 'bg-teal-600 hover:bg-teal-700',
  action: () => {},
})

// ─── Badge helpers ─────────────────────────────────────────────────────────────

function statusLabel(status) {
  const map = { draft: 'Draft', published: 'Published', archived: 'Archived' }
  return map[status] ?? status
}

function statusBadge(status) {
  const map = {
    draft:      'bg-gray-100 text-gray-600',
    published:  'bg-green-100 text-green-700',
    archived:   'bg-amber-100 text-amber-700',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}

function typeBadge(type) {
  const map = {
    alumni:   'bg-blue-100 text-blue-700',
    employer: 'bg-purple-100 text-purple-700',
  }
  return map[type] ?? 'bg-gray-100 text-gray-600'
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
}

// ─── Filter & pagination ────────────────────────────────────────────────────────

async function applyFilters() {
  store.setFilters({ ...filters })
  await store.fetchList(1)
}

async function resetFilters() {
  store.resetFilters()
  Object.assign(filters, store.filters)
  await store.fetchList(1)
}

async function changePage(page) {
  await store.fetchList(page)
}

// ─── Confirm actions ────────────────────────────────────────────────────────────

function confirmPublish(q) {
  modal.title = 'Publish Kuesioner?'
  modal.message = `"${q.title}" akan dipublikasikan dan tidak dapat diubah strukturnya.`
  modal.confirmLabel = 'Publish'
  modal.confirmClass = 'bg-green-600 hover:bg-green-700'
  modal.action = async () => {
    await store.publish(q.id)
    if (!store.error) {
      modal.show = false
      await store.fetchList(store.pagination.currentPage)
    }
  }
  modal.show = true
}

function confirmArchive(q) {
  modal.title = 'Archive Kuesioner?'
  modal.message = `"${q.title}" akan diarsipkan. Kuesioner yang diarsipkan tidak dapat digunakan untuk survei baru.`
  modal.confirmLabel = 'Archive'
  modal.confirmClass = 'bg-amber-600 hover:bg-amber-700'
  modal.action = async () => {
    await store.archive(q.id)
    if (!store.error) {
      modal.show = false
      await store.fetchList(store.pagination.currentPage)
    }
  }
  modal.show = true
}

function confirmDelete(q) {
  modal.title = 'Hapus Kuesioner?'
  modal.message = `"${q.title}" akan dihapus permanen. Aksi ini tidak dapat dibatalkan.`
  modal.confirmLabel = 'Hapus'
  modal.confirmClass = 'bg-red-600 hover:bg-red-700'
  modal.action = async () => {
    await store.destroy(q.id)
    if (!store.error) {
      modal.show = false
      await store.fetchList(store.pagination.currentPage)
    }
  }
  modal.show = true
}

// ─── Init ───────────────────────────────────────────────────────────────────────

onMounted(() => store.fetchList(1))
</script>
