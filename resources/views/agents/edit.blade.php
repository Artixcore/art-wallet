<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Edit agent') }}: {{ $agent->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Prompts, tools, providers, and safety policy.') }}
                </p>
            </div>
            <a href="{{ route('agents.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Back to Agents') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
            id="agents-editor-root"
            data-agents-editor
            data-agent-id="{{ $agent->id }}"
            data-ajax-show-url="{{ route('ajax.agents.show', $agent) }}"
            data-ajax-update-url="{{ route('ajax.agents.update', $agent) }}"
            data-ajax-prompt-url="{{ route('ajax.agents.prompt.update', $agent) }}"
            data-ajax-run-url="{{ route('ajax.agents.run', $agent) }}"
            data-ajax-tools-url="{{ route('ajax.agents.tools.update', $agent) }}"
            data-ajax-bindings-url="{{ route('ajax.agents.bindings.update', $agent) }}"
        >
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                        <input type="text" id="agent-name" value="{{ $agent->name }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                        <textarea id="agent-description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $agent->description }}</textarea>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('System prompt') }}</label>
                        <textarea id="agent-system-prompt" rows="8" class="mt-1 font-mono text-sm block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="agent-save-meta" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            {{ __('Save') }}
                        </button>
                        <button type="button" id="agent-save-prompt" class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ __('Save prompt') }}
                        </button>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Run test') }}</h3>
                        <textarea id="agent-run-input" rows="4" placeholder="{{ __('User message…') }}" class="mt-2 font-mono text-sm block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                        <button type="button" id="agent-run-btn" class="mt-3 w-full inline-flex justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            {{ __('Queue run') }}
                        </button>
                        <pre id="agent-run-output" class="mt-3 max-h-48 overflow-auto rounded-md bg-gray-900 p-3 text-xs text-gray-100 hidden"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/agents-editor.js'])
</x-app-layout>
