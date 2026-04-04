import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
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
            ],
            refresh: true,
        }),
    ],
});
