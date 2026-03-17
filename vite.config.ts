import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-charts': ['apexcharts', 'vue3-apexcharts'],
                    'vendor-quill': ['@vueup/vue-quill'],
                    'vendor-ui': ['lucide-vue-next', '@headlessui/vue', 'reka-ui'],
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
});
