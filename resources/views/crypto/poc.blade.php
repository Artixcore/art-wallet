<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Client crypto PoC') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Browser-only BIP39, Argon2id, and AES-GCM') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm dark:shadow-gray-950/30 sm:rounded-xl space-y-8">
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ __('Demonstrates BIP39 (24 words), Argon2id (hash-wasm), and AES-256-GCM in the browser only. Nothing sensitive is persisted by this page.') }}
                </p>

                <section class="space-y-3" aria-labelledby="poc-mnemonic-heading">
                    <h3 id="poc-mnemonic-heading" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Mnemonic') }}
                    </h3>
                    <button type="button" id="poc-gen-mnemonic"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-lg font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('Generate 24-word mnemonic') }}
                    </button>
                    <div id="poc-mnemonic" class="text-sm font-mono break-words text-gray-900 dark:text-gray-100 min-h-[1.5rem] rounded-lg border border-dashed border-gray-200 dark:border-gray-600 px-3 py-2 bg-gray-50 dark:bg-gray-900/50"></div>
                </section>

                <section class="space-y-3" aria-labelledby="poc-vault-heading">
                    <h3 id="poc-vault-heading" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Encrypt / decrypt (PoC)') }}
                    </h3>
                    <label for="poc-wallet-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Wallet password (PoC)') }}</label>
                    <input type="password" id="poc-wallet-password" autocomplete="off"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="poc-encrypt"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            {{ __('Encrypt vault JSON') }}
                        </button>
                        <button type="button" id="poc-decrypt"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Decrypt') }}
                        </button>
                        <button type="button" id="poc-save-wallet"
                            class="inline-flex items-center px-4 py-2 bg-emerald-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            {{ __('POST encrypted vault to server') }}
                        </button>
                    </div>
                    <p id="poc-status" class="text-sm text-gray-600 dark:text-gray-400 min-h-[1.25rem]" role="status"></p>
                </section>

                <section class="space-y-3" aria-labelledby="poc-ajax-heading">
                    <h3 id="poc-ajax-heading" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Session / AJAX') }}
                    </h3>
                    <button type="button" id="poc-ajax-health"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('GET /ajax/health (CSRF session)') }}
                    </button>
                    <pre id="poc-output" class="text-xs font-mono bg-gray-100 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto text-gray-800 dark:text-gray-200 max-h-64 overflow-y-auto" aria-live="polite"></pre>
                </section>
            </div>
        </div>
    </div>

    @vite(['resources/js/crypto-poc.js'])
</x-app-layout>
