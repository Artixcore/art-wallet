<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Settings & policy center') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Preferences, session policy, messaging privacy, risk alerts, and audit history. Sensitive changes require password verification.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/90 dark:bg-indigo-950/30 px-4 py-3 text-sm text-indigo-950 dark:text-indigo-100">
                {{ __('High-risk changes (relaxing security) require a fresh password check. Use “Verify password” before saving those sections.') }}
            </div>

            <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2" role="tablist">
                <button type="button" class="settings-tab px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100" data-settings-tab="general">{{ __('General') }}</button>
                <button type="button" class="settings-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-settings-tab="security">{{ __('Session & security') }}</button>
                <button type="button" class="settings-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-settings-tab="messaging">{{ __('Messaging') }}</button>
                <button type="button" class="settings-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-settings-tab="risk">{{ __('Risk alerts') }}</button>
                <button type="button" class="settings-tab px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" data-settings-tab="audit">{{ __('Audit log') }}</button>
            </div>

            <div id="settings-panel-general" class="settings-panel rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Display & locale') }}</h3>
                <form id="form-user-settings" class="space-y-3 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-theme">{{ __('Theme') }}</label>
                        <select id="set-theme" name="theme" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm">
                            <option value="system">{{ __('System') }}</option>
                            <option value="light">{{ __('Light') }}</option>
                            <option value="dark">{{ __('Dark') }}</option>
                        </select>
                        <p id="err-theme" data-error-for="theme" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-locale">{{ __('Locale') }}</label>
                        <input type="text" id="set-locale" name="locale" maxlength="16" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" placeholder="en" />
                        <p id="err-locale" data-error-for="locale" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-tz">{{ __('Timezone') }}</label>
                        <input type="text" id="set-tz" name="timezone" maxlength="64" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" placeholder="UTC" />
                        <p id="err-timezone" data-error-for="timezone" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <input type="hidden" id="set-user-version" name="settings_version" value="1" />
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save') }}</button>
                </form>
            </div>

            <div id="settings-panel-security" class="settings-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Session policy') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Increasing idle timeout or removing a session cap requires password verification.') }}</p>
                <form id="form-security-policy" class="space-y-3 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-idle">{{ __('Idle timeout (minutes)') }}</label>
                        <input type="number" id="set-idle" name="idle_timeout_minutes" min="5" max="720" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" />
                        <p id="err-idle_timeout_minutes" data-error-for="idle_timeout_minutes" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-max-sess">{{ __('Max session duration (minutes, optional)') }}</label>
                        <input type="number" id="set-max-sess" name="max_session_duration_minutes" min="5" max="10080" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" />
                        <p id="err-max_session_duration_minutes" data-error-for="max_session_duration_minutes" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-notify-dev" name="notify_new_device_login" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-notify-dev" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Notify on new device login') }}</label>
                    </div>
                    <input type="hidden" id="set-security-version" name="settings_version" value="1" />
                    <button type="button" id="btn-step-up" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('Verify password') }}</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save session policy') }}</button>
                </form>
            </div>

            <div id="settings-panel-messaging" class="settings-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Messaging privacy') }}</h3>
                <form id="form-messaging-privacy" class="space-y-3 max-w-lg">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-rr" name="read_receipts_enabled" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-rr" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Read receipts') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-typing" name="typing_indicators_enabled" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-typing" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Typing indicators') }}</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-att">{{ __('Max attachment size (MB)') }}</label>
                        <input type="number" id="set-att" name="max_attachment_mb" min="1" max="50" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" />
                        <p id="err-max_attachment_mb" data-error-for="max_attachment_mb" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-safe" name="safety_warnings_enabled" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-safe" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Safety warnings') }}</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-sol-discover">{{ __('Discoverable by verified Solana address') }}</label>
                        <select id="set-sol-discover" name="discoverable_by_sol_address" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm">
                            <option value="off">{{ __('Off (recommended)') }}</option>
                            <option value="contacts_only">{{ __('Contacts only (prior direct chats)') }}</option>
                            <option value="all_verified_users">{{ __('All verified ArtWallet users') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Controls whether others can find you by a Solana address you verified through wallet sync.') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-dm-approval" name="require_dm_approval" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-dm-approval" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Require approval for new direct messages') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="set-hide-profile" name="hide_profile_until_dm_accepted" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="set-hide-profile" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Hide display name until conversation is allowed') }}</label>
                    </div>
                    <input type="hidden" id="set-messaging-version" name="settings_version" value="1" />
                    <button type="button" id="btn-step-up-msg" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('Verify password') }}</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save messaging settings') }}</button>
                </form>
            </div>

            <div id="settings-panel-risk" class="settings-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Large transaction alerts') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Raising the fiat threshold so alerts fire less often requires password verification.') }}</p>
                <form id="form-risk-thresholds" class="space-y-3 max-w-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-large-tx">{{ __('Alert above (fiat)') }}</label>
                        <input type="text" id="set-large-tx" name="large_tx_alert_fiat" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" placeholder="1000.00" />
                        <p id="err-large_tx_alert_fiat" data-error-for="large_tx_alert_fiat" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="set-large-cur">{{ __('Currency') }}</label>
                        <input type="text" id="set-large-cur" name="large_tx_alert_currency" maxlength="3" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm" value="USD" />
                        <p id="err-large_tx_alert_currency" data-error-for="large_tx_alert_currency" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <input type="hidden" id="set-risk-version" name="settings_version" value="1" />
                    <button type="button" id="btn-step-up-risk" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('Verify password') }}</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Save risk alerts') }}</button>
                </form>
            </div>

            <div id="settings-panel-audit" class="settings-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Recent settings changes') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Last 100 entries. Values may be truncated.') }}</p>
                <ul id="settings-audit-list" class="divide-y divide-gray-200 dark:divide-gray-700 text-sm font-mono text-xs"></ul>
            </div>
        </div>
    </div>

    @vite(['resources/js/settings-manager.js'])
</x-app-layout>
