<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Notifications') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Transaction updates, security notices, and system messages.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p id="notif-unread-label" class="text-sm text-gray-600 dark:text-gray-300"></p>
                <div class="flex gap-2">
                    <button type="button" id="notif-mark-all-read"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('Mark all read') }}
                    </button>
                </div>
            </div>
            <ul id="notif-list" class="divide-y divide-gray-200 dark:divide-gray-700 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400" id="notif-loading">
                    {{ __('Loading…') }}
                </li>
            </ul>
        </div>
    </div>

    @vite(['resources/js/notifications.js'])
</x-app-layout>
