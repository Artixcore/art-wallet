<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Secure messaging') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div id="secure-messaging-root" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                 data-messaging-endpoint="{{ url('/ajax') }}">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Messages are end-to-end encrypted. The server cannot read message content.') }}
                </p>
                <button type="button" id="messaging-demo-encrypt" class="mb-4 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-200 dark:hover:bg-indigo-900/60">
                    {{ __('Run local AES-GCM demo') }}
                </button>
                <div id="messaging-conversation-list" class="border border-gray-200 dark:border-gray-700 rounded-md min-h-[120px] p-3 mb-4"></div>
                <div id="messaging-chat" class="border border-gray-200 dark:border-gray-700 rounded-md min-h-[200px] p-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Select a conversation or create one via the API / crypto POC flow.') }}
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/secure-messaging.js'])
</x-app-layout>
