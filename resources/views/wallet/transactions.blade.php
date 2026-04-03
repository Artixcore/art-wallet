<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Wallet: send, receive, history') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Addresses sync to the server as public metadata only. Signing stays in your browser for Ethereum; other chains require separate tooling until extended.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p id="wt-boot-status" class="text-sm text-red-600 min-h-[1.25rem]" role="status"></p>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="wt-wallet" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Wallet') }}</label>
                        <select id="wt-wallet" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm"></select>
                    </div>
                    <div>
                        <label for="wt-wallet-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Wallet password') }}</label>
                        <input type="password" id="wt-wallet-password" autocomplete="off"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm" />
                    </div>
                </div>
                <button type="button" id="wt-load"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    {{ __('Unlock & sync addresses') }}
                </button>
                <p id="wt-unlock-status" class="text-sm text-gray-600 dark:text-gray-400 min-h-[1.25rem]" role="status"></p>
            </div>

            <div id="wt-panel" class="hidden space-y-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex gap-6" aria-label="Tabs">
                        <button type="button" id="wt-tab-receive" class="wt-tab border-b-2 border-indigo-600 py-3 px-1 text-sm font-medium text-indigo-600">{{ __('Receive') }}</button>
                        <button type="button" id="wt-tab-send" class="wt-tab border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">{{ __('Send') }}</button>
                        <button type="button" id="wt-tab-history" class="wt-tab border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">{{ __('History') }}</button>
                    </nav>
                </div>

                <div id="wt-section-receive" class="wt-panel-section rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                    <label for="wt-receive-network" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Network') }}</label>
                    <select id="wt-receive-network" class="block w-full max-w-md rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm"></select>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Supported assets on this network:') }} <span id="wt-receive-assets" class="font-mono"></span></p>
                    <div id="wt-receive-warning" class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-900 dark:text-amber-100">
                        {{ __('Deposits sent on the wrong network or wrong token contract are usually irreversible. Verify network and asset with the sender.') }}
                    </div>
                    <p class="text-sm font-mono break-all text-gray-900 dark:text-gray-100" id="wt-receive-address"></p>
                    <div class="flex flex-wrap items-end gap-4">
                        <canvas id="wt-qr" width="200" height="200" class="rounded-lg border border-gray-200 dark:border-gray-600" aria-hidden="true"></canvas>
                    </div>
                </div>

                <div id="wt-section-send" class="wt-panel-section hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                    <div>
                        <label for="wt-send-asset" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Asset') }}</label>
                        <select id="wt-send-asset" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm"></select>
                    </div>
                    <div>
                        <label for="wt-send-to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Recipient') }}</label>
                        <input type="text" id="wt-send-to" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm font-mono text-sm" />
                    </div>
                    <div>
                        <label for="wt-send-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount (decimal units)') }}</label>
                        <input type="text" id="wt-send-amount" placeholder="0.01"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 shadow-sm" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="wt-fetch-fee"
                            class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200">
                            {{ __('Refresh fee estimate') }}
                        </button>
                        <button type="button" id="wt-create-intent"
                            class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-lg text-xs font-semibold text-white">
                            {{ __('Create transaction intent') }}
                        </button>
                    </div>
                    <pre id="wt-fee-panel" class="text-xs font-mono bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto max-h-40"></pre>
                    <pre id="wt-intent-json" class="text-xs font-mono bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto max-h-64"></pre>
                    <button type="button" id="wt-sign-broadcast"
                        class="inline-flex items-center px-4 py-2 bg-emerald-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-600">
                        {{ __('Sign & broadcast (Ethereum)') }}
                    </button>
                    <p id="wt-send-status" class="text-sm min-h-[1.25rem]" role="status"></p>
                </div>

                <div id="wt-section-history" class="wt-panel-section hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                    <p id="wt-history-status" class="text-sm text-red-600 min-h-[1.25rem]"></p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="py-2 pr-4">{{ __('Tx') }}</th>
                                    <th class="py-2 pr-4">{{ __('Dir') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4">{{ __('Network') }}</th>
                                    <th class="py-2">{{ __('Link') }}</th>
                                </tr>
                            </thead>
                            <tbody id="wt-history-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/wallet-transactions.js'])
</x-app-layout>
