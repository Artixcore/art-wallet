import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    define: {
        global: 'globalThis',
    },
    resolve: {
        alias: {
            process: 'process/browser',
            buffer: 'buffer',
        },
    },
    optimizeDeps: {
        include: ['buffer', 'process'],
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/crypto-poc.js',
                'resources/js/security-center.js',
                'resources/js/wallet-transactions.js',
                'resources/js/notifications.js',
                'resources/js/settings-manager.js',
                'resources/js/operator-dashboard.js',
                'resources/js/secure-messaging.js',
                'resources/js/agents-dashboard.js',
                'resources/js/agents-editor.js',
                'resources/js/onboarding.js',
            ],
            refresh: true,
        }),
    ],
});
