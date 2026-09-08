import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Font is "Outfit" (loaded via Google Fonts in resources/css/app.css, to
// match the tailadmin theme) — no laravel-vite-plugin font bundling here,
// so we don't ship the default skeleton's unused "Instrument Sans".
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js', 'resources/js/soal-editor.js'],
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
