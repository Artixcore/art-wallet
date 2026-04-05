<x-guest-layout>
    @push('scripts')
        @unless (app()->environment('testing'))
            @vite(['resources/js/onboarding.js'])
        @endunless
    @endpush

    <div class="w-full max-w-lg mx-auto">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('Create your wallet') }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">{{ __('Secure multi-chain setup. Your recovery phrase never leaves your device unencrypted.') }}</p>

        @if ($guestSignup ?? false)
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                <a class="underline text-indigo-600 dark:text-indigo-400" href="{{ route('login') }}">{{ __('Already have an account? Log in') }}</a>
            </p>
        @endif

        <ol id="onboarding-progress" class="flex gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 mb-6">
            <li data-step="1" class="rounded px-2 py-1 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200">1</li>
            <li data-step="2" class="rounded px-2 py-1">2</li>
            <li data-step="3" class="rounded px-2 py-1">3</li>
            <li data-step="4" class="rounded px-2 py-1">4</li>
        </ol>

        <div id="step-signup" class="space-y-4 {{ ($guestSignup ?? false) ? '' : 'hidden' }}">
            <div>
                <x-input-label for="ob-username" :value="__('Username')" />
                <x-text-input id="ob-username" class="block mt-1 w-full" type="text" autocomplete="username" />
                <p id="ob-username-err" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden"></p>
            </div>
            <div>
                <x-input-label for="ob-password" :value="__('Password')" />
                <x-text-input id="ob-password" class="block mt-1 w-full" type="password" autocomplete="new-password" />
            </div>
            <div>
                <x-input-label for="ob-password2" :value="__('Confirm password')" />
                <x-text-input id="ob-password2" class="block mt-1 w-full" type="password" autocomplete="new-password" />
            </div>
            <x-primary-button type="button" id="ob-signup-btn">{{ __('Sign up') }}</x-primary-button>
        </div>

        <div id="step-encrypt" class="space-y-4 hidden">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Re-enter your account password. It is used only in your browser to encrypt the wallet vault before upload. It is not sent to the server.') }}</p>
            <div>
                <x-input-label for="ob-enc-password" :value="__('Password')" />
                <x-text-input id="ob-enc-password" class="block mt-1 w-full" type="password" autocomplete="current-password" />
            </div>
            <x-primary-button type="button" id="ob-vault-btn">{{ __('Generate wallet & encrypt') }}</x-primary-button>
            <p id="ob-vault-status" class="text-sm text-gray-500 dark:text-gray-400"></p>
        </div>

        <div id="step-reveal" class="space-y-4 hidden">
            <div class="rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-950 dark:text-amber-100">
                <p class="font-semibold mb-1">{{ __('Save your recovery phrase') }}</p>
                <p>{{ __('Write it offline. Never share it. Anyone with this phrase can control your funds. Loss may mean permanent loss of access.') }}</p>
            </div>
            <div id="ob-mnemonic-grid" class="font-mono text-sm leading-relaxed p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 select-all"></div>
            <button type="button" id="ob-copy-mnemonic" class="text-sm text-indigo-600 dark:text-indigo-400 underline">{{ __('Copy to clipboard') }}</button>
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" id="ob-saved-check" class="mt-1 rounded border-gray-300 dark:border-gray-600" />
                <span>{{ __('I have written down my 24-word recovery phrase in a safe place.') }}</span>
            </label>
            <x-primary-button type="button" id="ob-ack-btn" disabled>{{ __('Continue') }}</x-primary-button>
        </div>

        <div id="step-confirm" class="space-y-4 hidden">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Type your full 24-word recovery phrase to confirm you saved it.') }}</p>
            <textarea id="ob-mnemonic-confirm" rows="4" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm text-sm font-mono" placeholder="{{ __('word1 word2 …') }}"></textarea>
            <p id="ob-confirm-err" class="text-sm text-red-600 dark:text-red-400 hidden"></p>
            <x-primary-button type="button" id="ob-confirm-btn">{{ __('Activate wallet') }}</x-primary-button>
        </div>
    </div>

    <script type="application/json" id="onboarding-config">{!! json_encode([
        'guestSignup' => (bool) ($guestSignup ?? false),
        'stepToken' => (string) ($stepToken ?? ''),
        'verifierSaltHex' => (string) ($verifierSaltHex ?? ''),
        'onboardingState' => (string) ($onboardingState ?? 'awaiting_signup'),
        'routes' => [
            'signup' => route('ajax.onboarding.signup'),
            'vault' => route('ajax.onboarding.vault'),
            'acknowledge' => route('ajax.onboarding.acknowledge-passphrase'),
            'confirm' => route('ajax.onboarding.confirm-passphrase'),
            'dashboard' => route('dashboard'),
            'onboarding' => route('onboarding.show'),
        ],
    ], JSON_THROW_ON_ERROR) !!}</script>
</x-guest-layout>
