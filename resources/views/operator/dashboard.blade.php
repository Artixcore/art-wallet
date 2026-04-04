<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Operator observability') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('System health, RPC probes, and incident signals. Operational metadata only — no wallet secrets.') }}
            </p>
        </div>
    </x-slot>

    <div id="operator-page-meta"
         class="hidden"
         data-summary-url="{{ route('ajax.operator.summary') }}"
         data-probes-url="{{ route('ajax.operator.probes.run') }}"
    ></div>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div id="operator-stale-banner" class="hidden rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 px-4 py-3 text-sm text-amber-950 dark:text-amber-100">
                <strong>{{ __('Stale data') }}</strong>
                <span id="operator-stale-text"></span>
            </div>

            <div id="operator-partial-banner" class="hidden rounded-xl border border-orange-300 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/30 px-4 py-3 text-sm text-orange-950 dark:text-orange-100">
                <strong>{{ __('Partial load') }}</strong>
                {{ __('Some panels could not be loaded. The dashboard is not fully authoritative.') }}
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Overall') }}</p>
                    <p id="operator-overall" class="text-2xl font-semibold text-gray-900 dark:text-gray-100">—</p>
                    <p id="operator-server-time" class="text-xs text-gray-500 dark:text-gray-400 mt-1"></p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="operator-refresh" class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('Refresh') }}
                    </button>
                    <button type="button" id="operator-run-probes" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                        {{ __('Run probes') }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Health checks') }}</h3>
                    <div id="operator-checks-loading" class="text-sm text-gray-500">{{ __('Loading…') }}</div>
                    <ul id="operator-checks" class="divide-y divide-gray-100 dark:divide-gray-700 text-sm hidden"></ul>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('RPC / chains') }}</h3>
                    <div id="operator-rpc-loading" class="text-sm text-gray-500">{{ __('Loading…') }}</div>
                    <ul id="operator-rpc" class="divide-y divide-gray-100 dark:divide-gray-700 text-sm hidden"></ul>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Incidents') }}</h3>
                <p id="operator-incidents" class="text-sm text-gray-600 dark:text-gray-300"></p>
            </div>
        </div>
    </div>
</x-app-layout>

@vite(['resources/js/operator-dashboard.js'])
