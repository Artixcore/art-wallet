<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                        <x-application-logo class="block h-9 w-9 sm:h-10 sm:w-10" />
                        <span class="hidden sm:inline font-semibold text-gray-900 dark:text-gray-100 tracking-tight text-base">{{ config('app.name') }}</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('crypto.poc')" :active="request()->routeIs('crypto.poc')">
                        {{ __('Crypto PoC') }}
                    </x-nav-link>
                    <x-nav-link :href="route('security.index')" :active="request()->routeIs('security.index')">
                        {{ __('Security') }}
                    </x-nav-link>
                    <x-nav-link :href="route('messaging.index')" :active="request()->routeIs('messaging.index')">
                        {{ __('Messaging') }}
                    </x-nav-link>
                    <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.index')">
                        {{ __('Settings') }}
                    </x-nav-link>
                    @can('ops.dashboard.view')
                        <x-nav-link :href="route('operator.dashboard')" :active="request()->routeIs('operator.dashboard')">
                            {{ __('Operator') }}
                        </x-nav-link>
                    @endcan
                    <x-nav-link :href="route('wallet.transactions')" :active="request()->routeIs('wallet.transactions')">
                        {{ __('Wallet') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Notifications + Settings -->
            <div class="hidden sm:flex sm:items-center sm:gap-2 sm:ms-6">
                <div class="relative">
                    <button type="button" id="nav-notif-bell" class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="{{ __('Notifications') }}" aria-expanded="false" aria-haspopup="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span id="nav-notif-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[1.125rem] h-[1.125rem] px-1 flex items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white leading-none"></span>
                    </button>
                    <div id="nav-notif-panel" class="hidden absolute right-0 z-50 mt-2 w-80 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-3 py-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Recent') }}</span>
                            <button type="button" id="nav-notif-mark-read" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Mark all read') }}</button>
                        </div>
                        <ul id="nav-notif-list" class="max-h-72 overflow-y-auto py-1"></ul>
                        <div class="border-t border-gray-100 dark:border-gray-700 px-3 py-2 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ __('View all') }}</a>
                        </div>
                    </div>
                </div>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center gap-2">
                                <span>{{ Auth::user()->name }}</span>
                                @if (Auth::user()->isAdmin())
                                    <span class="rounded bg-amber-100 dark:bg-amber-900/50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900 dark:text-amber-200">{{ __('Admin') }}</span>
                                @endif
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-700 dark:focus:text-gray-200 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.poc')" :active="request()->routeIs('crypto.poc')">
                {{ __('Crypto PoC') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('security.index')" :active="request()->routeIs('security.index')">
                {{ __('Security') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('messaging.index')" :active="request()->routeIs('messaging.index')">
                {{ __('Messaging') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.index')">
                {{ __('Settings') }}
            </x-responsive-nav-link>
            @can('ops.dashboard.view')
                <x-responsive-nav-link :href="route('operator.dashboard')" :active="request()->routeIs('operator.dashboard')">
                    {{ __('Operator') }}
                </x-responsive-nav-link>
            @endcan
            <x-responsive-nav-link :href="route('wallet.transactions')" :active="request()->routeIs('wallet.transactions')">
                {{ __('Wallet') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                {{ __('Notifications') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
