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
        include: ['@excalidraw/excalidraw', 'react', 'react-dom', 'react/jsx-runtime'],
    },
    resolve: {
        dedupe: ['react', 'react-dom'],
    },
    build: {
        commonjsOptions: {
            transformMixedEsModules: true,
            include: [/node_modules/],
        },
    },
    define: {
        'process.env.IS_PREACT': 'false',
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    server: {
        cors: true,
    },
});