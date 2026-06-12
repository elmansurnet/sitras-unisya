<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Manajemen Employer</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola data perusahaan dan status survei employer.</p>
      </div>
      <button
        @click="$router.push({ name: 'admin.employers.create' })"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Employer
      </button>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        <div class="xl:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Cari</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Nama perusahaan, PIC, email..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            @keyup.enter="applyFilters"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Perusahaan</label>
          <select v-model="filters.company_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option value="">Semua Jenis</option>
            <option value="swasta">Swasta</option>
            <option value="bumn">BUMN</option>
            <option value="pemerintah">Pemerintah</option>
            <option value="ngo">NGO</option>
            <option value="startup">Startup</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Status Survei</label>
          <select v-model="filters.survey_status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            <option value="">Semua Status</option>
            <option value="belum_disurvei">Belum Disurvei</option>
            <option value="terkirim">Terkirim</option>
            <option value="selesai">Selesai</option>
          </select>
        </div>
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
      <!-- Loading state -->
      <div v-if="store.loading" class="space-y-3 p-4">
        <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded bg-gray-100" />
      </div>

      <!-- Empty state -->
      <div v-else-if="!store.list.length" class="flex flex-col items-center justify-center py-16 text-gray-400">
        <svg class="h-12 w-12 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
        </svg>
        <p class="font-medium">Belum ada data employer</p>
        <p class="text-sm mt-1">Tambah employer baru untuk memulai.</p>
      </div>

      <!-- Table data -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Perusahaan</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Jenis / Sektor</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Kota</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">PIC</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status Survei</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="employer in store.list"
              :key="employer.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 text-sm">{{ employer.company_name }}</div>
                <div v-if="employer.email" class="text-xs text-gray-400">{{ employer.email }}</div>
              </td>
              <td class="px-4 py-3">
                <div class="text-sm text-gray-700 capitalize">{{ employer.company_type ?? '—' }}</div>
                <div v-if="employer.industry_sector" class="text-xs text-gray-400">{{ employer.industry_sector }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ employer.address_city ?? '—' }}</td>
              <td class="px-4 py-3">
                <div class="text-sm text-gray-700">{{ employer.contact_person_name ?? '—' }}</div>
                <div v-if="employer.contact_person_phone" class="text-xs text-gray-400">{{ employer.contact_person_phone }}</div>
              </td>
              <td class="px-4 py-3">
                <span :class="statusBadge(employer.survey_status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ statusLabel(employer.survey_status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="$router.push({ name: 'admin.employers.show', params: { id: employer.id } })"
                    class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="Lihat detail"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.574-3.007-9.964-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </button>
                  <button
                    @click="$router.push({ name: 'admin.employers.edit', params: { id: employer.id } })"
                    class="rounded p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600"
                    title="Edit"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
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
          Menampilkan {{ (store.pagination.currentPage - 1) * store.pagination.perPage + 1 }}
          &ndash;
          {{ Math.min(store.pagination.currentPage * store.pagination.perPage, store.pagination.total) }}
          dari {{ store.pagination.total }} employer
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
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useEmployerStore } from '@/stores/employer'

const store = useEmployerStore()

const filters = reactive({ ...store.filters })

function statusLabel(status) {
  const map = {
    belum_disurvei: 'Belum Disurvei',
    terkirim:       'Terkirim',
    selesai:        'Selesai',
  }
  return map[status] ?? status
}

function statusBadge(status) {
  const map = {
    belum_disurvei: 'bg-gray-100 text-gray-600',
    terkirim:       'bg-amber-100 text-amber-700',
    selesai:        'bg-green-100 text-green-700',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}

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

onMounted(() => store.fetchList(1))
</script>
