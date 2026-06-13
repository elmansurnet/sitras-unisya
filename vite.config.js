import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['frontend/src/main.js'],
            refresh: [
                'resources/views/**',
                'routes/**',
                'frontend/src/**',
            ],
        }),
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
            '@': resolve(__dirname, 'frontend/src'),
        },
    },
    css: {
        /**
         * Paksa transformer ke 'postcss' agar @apply di <style scoped>
         * diproses Tailwind/PostCSS terlebih dahulu sebelum minifikasi.
         */
        transformer: 'postcss',
        /**
         * Arahkan PostCSS ke folder frontend/ tempat tailwind.config.js
         * dan postcss.config.js berada. Tanpa ini, Vite mencari config
         * di root project dan tidak menemukan Tailwind.
         */
        postcss: resolve(__dirname, 'frontend'),
    },
    server: {
        /**
         * Gunakan 127.0.0.1 (bukan 0.0.0.0) agar Vite dev server
         * bisa diakses langsung dari browser di Windows/Laragon.
         */
        host: '127.0.0.1',
        port: 5173,
        proxy: {
            '/api': {
                target: process.env.APP_URL || 'http://sitras-unisya.test',
                changeOrigin: true,
                secure: false,
            },
            '/sanctum': {
                target: process.env.APP_URL || 'http://sitras-unisya.test',
                changeOrigin: true,
                secure: false,
            },
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                /**
                 * manualChunks HARUS Function di Vite 8 (Rolldown).
                 * Object literal tidak didukung.
                 */
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                            return 'charts';
                        }
                        if (id.includes('leaflet')) {
                            return 'maps';
                        }
                        if (
                            id.includes('/vue/') ||
                            id.includes('/vue-router/') ||
                            id.includes('/pinia/')
                        ) {
                            return 'vendor';
                        }
                    }
                },
            },
        },
    },
});
