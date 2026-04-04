<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Secure messaging') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div
                id="secure-messaging-root"
                class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col min-h-[32rem] lg:flex-row"
                data-messaging-endpoint="{{ url('/ajax') }}"
                data-current-user-id="{{ auth()->id() }}"
            >
                <aside class="w-full lg:w-80 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-700 flex flex-col shrink-0">
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="relative flex-1">
                            <input
                                type="search"
                                id="messaging-conv-filter"
                                placeholder="{{ __('Search conversations…') }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm"
                            />
                        </div>
                        <button
                            type="button"
                            id="messaging-new-message"
                            class="shrink-0 inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-500"
                        >
                            {{ __('New') }}
                        </button>
                    </div>
                    <div id="messaging-conversation-list" class="flex-1 overflow-y-auto p-2 min-h-[8rem]">
                        <p class="text-sm text-gray-500 p-2">{{ __('Loading…') }}</p>
                    </div>
                </aside>

                <section class="flex-1 flex flex-col min-h-[20rem]">
                    <header id="messaging-thread-header" class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 id="messaging-thread-title" class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('No conversation selected') }}
                            </h3>
                            <p id="messaging-thread-sub" class="text-xs text-gray-500 dark:text-gray-400"></p>
                        </div>
                    </header>

                    <div id="messaging-chat" class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900/40">
                        <div id="messaging-chat-inner" class="min-h-[12rem]">
                            <div id="messaging-empty-state" class="h-full flex flex-col items-center justify-center text-center text-gray-500 dark:text-gray-400 px-6">
                                <p class="text-sm mb-4">{{ __('Select a conversation or start a new message with a verified Solana address.') }}</p>
                                <button
                                    type="button"
                                    id="messaging-empty-new"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-500"
                                >
                                    {{ __('New message') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <footer class="p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex gap-2 items-end">
                            <button
                                type="button"
                                id="messaging-attach"
                                class="shrink-0 p-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                title="{{ __('Attachments (encrypted)') }}"
                                disabled
                            >
                                <span class="sr-only">{{ __('Attach') }}</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            </button>
                            <textarea
                                id="messaging-composer"
                                rows="2"
                                disabled
                                placeholder="{{ __('Encrypted send requires a client-held conversation key (E2E).') }}"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm disabled:opacity-60"
                            ></textarea>
                            <button
                                type="button"
                                id="messaging-send"
                                disabled
                                class="shrink-0 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium opacity-50 cursor-not-allowed"
                            >
                                {{ __('Send') }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Messages are end-to-end encrypted. The server stores ciphertext only.') }}
                        </p>
                        <button type="button" id="messaging-demo-encrypt" class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ __('Run local AES-GCM demo') }}
                        </button>
                    </footer>
                </section>
            </div>
        </div>
    </div>

    @vite(['resources/js/secure-messaging.js'])
</x-app-layout>
