<template>
  <div class="min-h-screen flex">
    <!-- Panel Kiri: ilustrasi + branding (hidden di mobile) -->
    <div
      class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-between
             bg-gray-900 text-white px-12 py-10"
      aria-hidden="true"
    >
      <!-- Logo + nama universitas -->
      <div class="flex items-center gap-3">
        <!-- SVG Logo UNISYA -->
        <svg
          class="w-10 h-10 text-emerald-400 flex-shrink-0"
          viewBox="0 0 40 40"
          fill="none"
          aria-label="Logo UNISYA"
        >
          <rect width="40" height="40" rx="8" fill="currentColor" fill-opacity="0.15" />
          <path
            d="M8 28V14l12-6 12 6v14l-12 6L8 28Z"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linejoin="round"
          />
          <path
            d="M20 8v24M8 28l12-6 12 6"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-opacity="0.6"
          />
        </svg>
        <div>
          <p class="text-sm font-semibold tracking-wide text-emerald-400 uppercase">
            SITRAS UNISYA
          </p>
          <p class="text-xs text-gray-400">Sistem Tracer Study</p>
        </div>
      </div>

      <!-- Ilustrasi tengah -->
      <div class="flex-1 flex flex-col justify-center">
        <!-- Ornamen geometris -->
        <div class="relative mb-8">
          <div
            class="w-48 h-48 rounded-full bg-emerald-500/10 border border-emerald-500/20
                   flex items-center justify-center mx-auto"
          >
            <div
              class="w-32 h-32 rounded-full bg-emerald-500/15 border border-emerald-500/25
                     flex items-center justify-center"
            >
              <svg class="w-16 h-16 text-emerald-400" fill="none" viewBox="0 0 64 64">
                <path
                  d="M32 8C18.7 8 8 18.7 8 32s10.7 24 24 24 24-10.7 24-24S45.3 8 32 8Z"
                  stroke="currentColor" stroke-width="1.5" opacity="0.4"
                />
                <path
                  d="M22 26h20M22 32h14M22 38h17"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round"
                />
                <path
                  d="M16 20l16-8 16 8v4l-16 8-16-8v-4Z"
                  stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Judul halaman kiri -->
        <h1 class="text-3xl font-bold text-white leading-snug mb-4">
          Portal Alumni<br />
          <span class="text-emerald-400">Universitas Islam Syarifuddin</span>
        </h1>
        <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
          Sistem penelusuran alumni untuk mendukung peningkatan kualitas pendidikan
          dan relevansi kurikulum.
        </p>
      </div>

      <!-- Quote islami di bawah -->
      <div class="border-t border-gray-700 pt-6">
        <blockquote class="text-gray-300 text-sm italic leading-relaxed">
          &ldquo;Barang siapa yang menempuh jalan untuk mencari ilmu, maka Allah akan
          memudahkan baginya jalan menuju surga.&rdquo;
        </blockquote>
        <p class="text-gray-500 text-xs mt-2">&mdash; HR. Muslim</p>
      </div>
    </div>

    <!-- Panel Kanan: form konten -->
    <div
      class="flex-1 flex flex-col justify-center items-center
             bg-white px-6 py-12 sm:px-12"
    >
      <!-- Logo mobile (tampil hanya di bawah lg) -->
      <div class="flex lg:hidden items-center gap-2 mb-8">
        <svg
          class="w-8 h-8 text-emerald-600"
          viewBox="0 0 40 40"
          fill="none"
          aria-label="Logo UNISYA"
        >
          <rect width="40" height="40" rx="8" fill="currentColor" fill-opacity="0.12" />
          <path
            d="M8 28V14l12-6 12 6v14l-12 6L8 28Z"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linejoin="round"
          />
        </svg>
        <span class="font-semibold text-gray-800">SITRAS UNISYA</span>
      </div>

      <!-- Slot untuk halaman auth -->
      <div class="w-full max-w-sm">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
// AuthLayout tidak membutuhkan logic khusus
// Semua routing guard dikelola di router/index.js
</script>
