import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

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
        transformer: 'postcss',
        postcss: {
            plugins: [
                tailwindcss({ config: resolve(__dirname, 'frontend/tailwind.config.js') }),
                autoprefixer(),
            ],
        },
    },
    server: {
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
