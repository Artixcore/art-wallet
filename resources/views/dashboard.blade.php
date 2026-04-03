<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('ArtWallet — self-hosted wallet and secure messaging') }}
                </p>
            </div>
            @if (Auth::user()->isAdmin())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 dark:bg-amber-900/40 px-3 py-1 text-xs font-semibold text-amber-900 dark:text-amber-200 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/30 w-fit">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Administrator') }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 px-6 py-8 sm:px-10 sm:py-10 shadow-xl ring-1 ring-white/10">
                <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-indigo-500/15 blur-2xl" aria-hidden="true"></div>
                <div class="absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-violet-500/10 blur-2xl" aria-hidden="true"></div>
                <div class="relative">
                    <p class="text-sm font-medium text-indigo-200/90">{{ __('Signed in as') }}</p>
                    <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                        {{ Auth::user()->name }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-300 max-w-xl">
                        {{ __('Use the shortcuts below to explore crypto tooling. Wallet creation and messaging will connect here in upcoming phases.') }}
                    </p>
                </div>
            </div>

            {{-- Quick actions --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">
                    {{ __('Quick actions') }}
                </h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ route('crypto.poc') }}"
                        class="group flex flex-col rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition duration-200">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h4 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">{{ __('Client crypto PoC') }}</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex-1">
                            {{ __('BIP39 (24 words), Argon2id, AES-GCM — all in the browser.') }}
                        </p>
                        <span class="mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-500">
                            {{ __('Open') }} →
                        </span>
                    </a>

                    <a href="{{ route('security.index') }}"
                        class="group flex flex-col rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition duration-200">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h4 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">{{ __('Security center') }}</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex-1">
                            {{ __('Backup, recovery kit, trusted devices, and sessions.') }}
                        </p>
                        <span class="mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-500">
                            {{ __('Open') }} →
                        </span>
                    </a>

                    <div class="flex flex-col rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-900/40 p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-200/80 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h4 class="mt-4 font-semibold text-gray-700 dark:text-gray-300">{{ __('Secure messages') }}</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('End-to-end encrypted chat and attachments — Phase 4–5.') }}
                        </p>
                        <span class="mt-4 text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Soon') }}</span>
                    </div>
                </div>
            </div>

            {{-- Security note --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5 shadow-sm">
                <div class="flex gap-4">
                    <div class="shrink-0 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Security reminder') }}</h4>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ __('Your account password is separate from your wallet encryption password. Never share your recovery phrase. On a shared machine, log out when finished.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
