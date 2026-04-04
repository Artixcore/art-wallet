<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Agents') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('AI agents, providers, workflows, and runs — orchestrated securely.') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" id="agents-dashboard-root"
            data-agents-dashboard
            data-ajax-dashboard-url="{{ route('ajax.agents.dashboard') }}"
            data-ajax-agents-url="{{ route('ajax.agents.index') }}"
            data-ajax-credentials-url="{{ route('ajax.agents.credentials.index') }}"
        >
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="agents-btn-create" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    {{ __('Create agent') }}
                </button>
                <button type="button" id="agents-btn-refresh" class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    {{ __('Refresh') }}
                </button>
            </div>

            <div id="agents-dashboard-widgets" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Active agents') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100" data-metric="active_agents">—</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Runs (7d)') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100" data-metric="runs_7d">—</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Providers connected') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100" data-metric="credentials_count">—</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Degraded providers') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300" data-metric="degraded_providers">—</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Your agents') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="agents-table-body" class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('Loading…') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Recent runs') }}</h3>
                </div>
                <ul id="agents-recent-runs" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('Loading…') }}</li>
                </ul>
            </div>
        </div>
    </div>

    @vite(['resources/js/agents-dashboard.js'])
</x-app-layout>
