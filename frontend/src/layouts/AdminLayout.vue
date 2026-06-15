<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Overlay mobile -->
    <Transition name="fade">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-20 bg-black/50 lg:hidden"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- Sidebar panel -->
    <aside
      :class="[
        'fixed top-0 left-0 z-30 h-full w-60 bg-gray-900 text-white',
        'flex flex-col transition-transform duration-300 ease-in-out',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-700">
        <svg class="w-8 h-8 text-emerald-400 flex-shrink-0" viewBox="0 0 40 40" fill="none">
          <rect width="40" height="40" rx="8" fill="currentColor" fill-opacity="0.15" />
          <path d="M8 28V14l12-6 12 6v14l-12 6L8 28Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
        </svg>
        <div>
          <p class="text-sm font-semibold text-emerald-400">SITRAS UNISYA</p>
          <p class="text-xs text-gray-500">Tracer Study</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <!-- Dashboard -->
        <SidebarItem :to="{ name: 'admin.dashboard' }" icon="grid" label="Dashboard" />

        <!-- Data Alumni -->
        <SidebarGroup label="Data Alumni" icon="users">
          <SidebarItem :to="{ name: 'admin.alumni.index' }"  label="Daftar Alumni" />
          <SidebarItem :to="{ name: 'admin.alumni.import' }" label="Import Alumni" />
        </SidebarGroup>

        <!-- Employer -->
        <SidebarGroup label="Employer" icon="briefcase">
          <SidebarItem :to="{ name: 'admin.employer.index' }" label="Daftar Employer" />
        </SidebarGroup>

        <!-- Survei -->
        <SidebarGroup label="Survei" icon="clipboard-list">
          <SidebarItem :to="{ name: 'admin.survey-periods.index' }"   label="Periode Survei" />
          <SidebarItem :to="{ name: 'admin.questionnaires.index' }"   label="Kuesioner" />
        </SidebarGroup>

        <!-- Statistik & Laporan -->
        <SidebarGroup label="Statistik & Laporan" icon="bar-chart-2">
          <SidebarItem :to="{ name: 'admin.dashboard.statistics' }" icon="trending-up" label="Statistik" />
          <SidebarItem :to="{ name: 'admin.reports' }"              label="Laporan" />
        </SidebarGroup>

        <!-- Notifikasi -->
        <SidebarGroup label="Notifikasi" icon="bell">
          <SidebarItem :to="{ name: 'admin.notifications.templates' }" label="Template" />
          <SidebarItem :to="{ name: 'admin.notifications.logs' }"      label="Log Kirim" />
        </SidebarGroup>

        <!-- Master Data (superadmin + admin) -->
        <SidebarGroup label="Master Data" icon="database">
          <SidebarItem :to="{ name: 'admin.settings.faculties' }"         label="Fakultas" />
          <SidebarItem :to="{ name: 'admin.settings.study-programs' }"    label="Program Studi" />
          <SidebarItem :to="{ name: 'admin.settings.graduation-years' }"  label="Tahun Kelulusan" />
        </SidebarGroup>

        <!-- Superadmin only -->
        <template v-if="authStore.isSuperadmin">
          <div class="pt-3 pb-1 px-2">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sistem</p>
          </div>
          <SidebarItem :to="{ name: 'admin.users' }"      icon="user-cog" label="Kelola Admin" />
          <SidebarItem :to="{ name: 'admin.settings' }"   icon="settings" label="Pengaturan" />
          <SidebarItem :to="{ name: 'admin.audit-logs' }" icon="shield"   label="Audit Log" />
        </template>
      </nav>

      <!-- User info + logout -->
      <div class="border-t border-gray-700 px-4 py-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-sm font-bold">
            {{ authStore.user?.name?.charAt(0)?.toUpperCase() ?? 'A' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-400 capitalize">{{ authStore.user?.role }}</p>
          </div>
        </div>
        <button
          class="w-full text-left text-sm text-gray-400 hover:text-red-400 transition-colors flex items-center gap-2"
          @click="handleLogout"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
          </svg>
          Logout
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col min-w-0 lg:ml-60">
      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white border-b border-gray-200 h-14 flex items-center px-4 gap-4">
        <!-- Hamburger (mobile) -->
        <button
          class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100 transition"
          :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
          @click="sidebarOpen = !sidebarOpen"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path v-if="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Breadcrumb -->
        <nav class="flex-1 flex items-center gap-1 text-sm text-gray-500" aria-label="Breadcrumb">
          <span
            v-for="(crumb, i) in breadcrumbs"
            :key="i"
            class="flex items-center gap-1"
          >
            <span v-if="i > 0" class="text-gray-300">/</span>
            <router-link
              v-if="crumb.to"
              :to="crumb.to"
              class="hover:text-gray-800 transition-colors"
            >{{ crumb.label }}</router-link>
            <span v-else class="text-gray-800 font-medium">{{ crumb.label }}</span>
          </span>
        </nav>

        <!-- Notif icon -->
        <button
          class="p-2 rounded-md text-gray-500 hover:bg-gray-100 transition relative"
          aria-label="Notifikasi"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </button>

        <!-- Avatar dropdown -->
        <div class="relative" ref="avatarRef">
          <button
            class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold
                   flex items-center justify-center hover:ring-2 hover:ring-emerald-300 transition"
            @click="dropdownOpen = !dropdownOpen"
            :aria-expanded="dropdownOpen"
            aria-label="Menu pengguna"
          >
            {{ authStore.user?.name?.charAt(0)?.toUpperCase() ?? 'A' }}
          </button>

          <Transition name="dropdown">
            <div
              v-if="dropdownOpen"
              class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
            >
              <div class="px-4 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-800 truncate">{{ authStore.user?.name }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ authStore.user?.role }}</p>
              </div>
              <button
                class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-gray-50 transition"
                @click="handleLogout"
              >Logout</button>
            </div>
          </Transition>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 sm:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import SidebarItem  from '@/components/sidebar/SidebarItem.vue'
import SidebarGroup from '@/components/sidebar/SidebarGroup.vue'

const authStore    = useAuthStore()
const route        = useRoute()
const sidebarOpen  = ref(false)
const dropdownOpen = ref(false)
const avatarRef    = ref(null)

const breadcrumbs = computed(() => route.meta?.breadcrumbs ?? [
  { label: route.meta?.title ?? 'Halaman' },
])

async function handleLogout() {
  dropdownOpen.value = false
  await authStore.logout()
}

function onClickOutside(e) {
  if (avatarRef.value && !avatarRef.value.contains(e.target)) {
    dropdownOpen.value = false
  }
}
const closeSidebarOnResize = () => {
  if (window.innerWidth >= 1024) sidebarOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
  window.addEventListener('resize', closeSidebarOnResize)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  window.removeEventListener('resize', closeSidebarOnResize)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.dropdown-enter-active, .dropdown-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
