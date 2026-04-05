<x-guest-layout>
    <div class="text-center space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Onboarding locked') }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Too many incorrect recovery phrase attempts. For your security this onboarding session cannot continue from here. Please contact support if you need help.') }}
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-primary-button>{{ __('Sign out') }}</x-primary-button>
        </form>
    </div>
</x-guest-layout>
