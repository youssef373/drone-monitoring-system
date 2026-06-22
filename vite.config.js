import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/map.css',
                'resources/js/app.js',
                'resources/js/bootstrap.js',
                'resources/js/map.js',
                'resources/js/map-layout.js',
                'resources/js/map-index.js',
                'resources/js/map-show.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
