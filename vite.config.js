import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/app/theme.css',
                'resources/js/app.js',
            ],
            refresh: [`resources/views/**/*`],
        }),
        tailwindcss(),
    ],
    optimizeDeps: {
        include: ['@excalidraw/excalidraw', 'react', 'react-dom'],
    },
    define: {
        'process.env.IS_PREACT': JSON.stringify('false'),
    },
    server: {
        cors: true,
    },
});