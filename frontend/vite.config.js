/**
 * vite.config.js — Frontend SPA standalone
 * Digunakan saat development Vue terpisah dari Laravel
 *
 * Catatan: Root vite.config.js (laravel-vite-plugin) tetap digunakan
 * untuk production build via Laravel. File ini untuk standalone dev.
 *
 * Sesuai 04_ARCHITECTURE.md §2
 */
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5174,
    proxy: {
      '/api': {
        target: process.env.VITE_API_URL || 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/sanctum': {
        target: process.env.VITE_API_URL || 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: '../public/spa',
    sourcemap: false,
    rollupOptions: {
      output: {
        /**
         * manualChunks HARUS Function di Vite 8 (Rolldown).
         * Object literal tidak didukung dan menyebabkan warning + build failure.
         * Strategi: vendor (vue ecosystem) | charts (apexcharts) | maps (leaflet)
         */
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
              return 'charts'
            }
            if (id.includes('leaflet')) {
              return 'maps'
            }
            if (
              id.includes('/vue/') ||
              id.includes('/vue-router/') ||
              id.includes('/pinia/')
            ) {
              return 'vendor'
            }
          }
        },
      },
    },
  },
})
