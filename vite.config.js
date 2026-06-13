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
    server: {
        host: '0.0.0.0',
        port: 5173,
        proxy: {
            '/api': {
                target: process.env.APP_URL || 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '/sanctum': {
                target: process.env.APP_URL || 'http://localhost:8000',
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
                 * Object literal tidak didukung — menyebabkan warning
                 * "Expected Function but received Object".
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
