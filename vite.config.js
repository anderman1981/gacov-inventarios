import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['frontend/resources/css/app.css', 'frontend/resources/js/app.js'],
            refresh: true,
        }),
    ],
});
