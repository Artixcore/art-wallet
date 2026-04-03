<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Security center') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Backup, recovery kit, trusted devices, and active sessions — client-side crypto preserved.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/90 dark:bg-amber-950/30 px-4 py-3 text-sm text-amber-950 dark:text-amber-100">
                {{ __('Never share your 24-word phrase or recovery kit passphrase. ArtWallet staff cannot recover your wallet without them. Resetting your account password does not decrypt the vault.') }}
            </div>

            <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2" role="tablist">
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100" data-sec-tab="backup">{{ __('Backup') }}</button>
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-sec-tab="trusted">{{ __('Trusted devices') }}</button>
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-sec-tab="challenge">{{ __('New device') }}</button>
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-sec-tab="sessions">{{ __('Sessions') }}</button>
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-sec-tab="kit">{{ __('Recovery kit') }}</button>
                <button type="button" class="sec-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-sec-tab="events">{{ __('Activity') }}</button>
            </div>

            <div id="sec-panel-backup" class="sec-panel rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Mnemonic backup (24 words, BIP39)') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Generate once per session for practice. In production, tie this to wallet creation only.') }}</p>
                <button type="button" id="sec-gen-mnemonic" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Generate mnemonic') }}</button>
                <div id="sec-mnemonic-display" class="font-mono text-sm whitespace-pre-wrap break-words p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 min-h-[3rem]"></div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirm: re-enter all 24 words') }}</label>
                <textarea id="sec-mnemonic-confirm" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm" placeholder="word1 word2 …"></textarea>
                <button type="button" id="sec-verify-mnemonic" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ __('Verify & record backup') }}</button>
                <p id="sec-backup-msg" class="text-sm text-gray-600 dark:text-gray-400"></p>
            </div>

            <div id="sec-panel-trusted" class="sec-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Trusted login devices') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('This browser holds an Ed25519 key in localStorage (XSS can steal it). Register to approve new-device challenges.') }}</p>
                <button type="button" id="sec-register-device" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Register this browser') }}</button>
                <ul id="sec-device-list" class="divide-y divide-gray-200 dark:divide-gray-700 text-sm"></ul>
            </div>

            <div id="sec-panel-challenge" class="sec-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('New device verification') }}</h3>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('On the new browser') }}</p>
                        <button type="button" id="sec-create-challenge" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Create challenge') }}</button>
                        <pre id="sec-challenge-new" class="text-xs font-mono p-3 bg-gray-50 dark:bg-gray-900 rounded-lg overflow-x-auto"></pre>
                        <button type="button" id="sec-poll-status" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm dark:text-gray-200">{{ __('Check approval status') }}</button>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('On a trusted device') }}</p>
                        <button type="button" id="sec-load-pending" class="rounded-lg bg-gray-800 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white">{{ __('Load pending') }}</button>
                        <button type="button" id="sec-approve-first" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ __('Approve first pending with this device') }}</button>
                        <pre id="sec-challenge-trusted" class="text-xs font-mono p-3 bg-gray-50 dark:bg-gray-900 rounded-lg overflow-x-auto"></pre>
                    </div>
                </div>
            </div>

            <div id="sec-panel-sessions" class="sec-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Active sessions') }}</h3>
                <button type="button" id="sec-load-sessions" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Refresh list') }}</button>
                <button type="button" id="sec-revoke-others" class="rounded-lg border border-red-300 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-2 text-sm font-semibold">{{ __('Log out all other sessions') }}</button>
                <ul id="sec-session-list" class="divide-y divide-gray-200 dark:divide-gray-700 text-sm"></ul>
            </div>

            <div id="sec-panel-kit" class="sec-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Encrypted recovery kit') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Kit passphrase must differ from your wallet password. File + passphrase equals full recovery power.') }}</p>
                <label class="block text-sm font-medium">{{ __('Mnemonic to seal (24 words)') }}</label>
                <textarea id="sec-kit-mnemonic" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"></textarea>
                <label class="block text-sm font-medium">{{ __('Kit passphrase') }}</label>
                <input type="password" id="sec-kit-pass" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" autocomplete="new-password" />
                <button type="button" id="sec-build-kit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Build & download JSON') }}</button>
                <button type="button" id="sec-upload-kit" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm dark:text-gray-200">{{ __('Sync encrypted kit to server') }}</button>
                <p id="sec-kit-msg" class="text-sm text-gray-600 dark:text-gray-400"></p>
            </div>

            <div id="sec-panel-events" class="sec-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Security activity') }}</h3>
                <button type="button" id="sec-load-events" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Refresh') }}</button>
                <ul id="sec-event-list" class="divide-y divide-gray-200 dark:divide-gray-700 text-sm font-mono"></ul>
            </div>
        </div>
    </div>

    <div id="sec-page-meta" data-user-id="{{ (int) auth()->id() }}" class="hidden"></div>
    @vite(['resources/js/security-center.js'])
</x-app-layout>
