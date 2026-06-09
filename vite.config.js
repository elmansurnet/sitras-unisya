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
                manualChunks: {
                    vendor: ['vue', 'vue-router', 'pinia'],
                    charts: ['apexcharts', 'vue3-apexcharts'],
                    maps: ['leaflet'],
                },
            },
        },
    },
});
