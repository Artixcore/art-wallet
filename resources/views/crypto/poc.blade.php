<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Client crypto PoC') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    {{ __('Demonstrates BIP39 (24 words), Argon2id (hash-wasm), and AES-256-GCM in the browser only. Nothing sensitive is persisted by this page.') }}
                </p>
                <div class="space-y-3">
                    <button type="button" id="poc-gen-mnemonic"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('Generate 24-word mnemonic') }}
                    </button>
                    <div id="poc-mnemonic" class="text-sm font-mono break-words text-gray-900 dark:text-gray-100 min-h-[1.5rem]"></div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Wallet password (PoC)') }}</label>
                    <input type="password" id="poc-wallet-password" autocomplete="off"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" id="poc-encrypt"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            {{ __('Encrypt vault JSON') }}
                        </button>
                        <button type="button" id="poc-decrypt"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Decrypt') }}
                        </button>
                    </div>
                    <p id="poc-status" class="text-sm text-gray-600 dark:text-gray-400"></p>
                    <button type="button" id="poc-ajax-health"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('GET /ajax/health (CSRF session)') }}
                    </button>
                    <pre id="poc-output" class="text-xs font-mono bg-gray-100 dark:bg-gray-900 p-3 rounded-md overflow-x-auto text-gray-800 dark:text-gray-200 max-h-64 overflow-y-auto"></pre>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/crypto-poc.js'])
</x-app-layout>
