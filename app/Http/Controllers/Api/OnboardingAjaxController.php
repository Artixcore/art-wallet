<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Chain\Exceptions\ChainAdapterException;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Onboarding\Enums\OnboardingState;
use App\Domain\Onboarding\Services\OnboardingStateMachine;
use App\Exceptions\Onboarding\InvalidOnboardingTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\Onboarding\AcknowledgePassphraseRequest;
use App\Http\Requests\Ajax\Onboarding\ConfirmPassphraseRequest;
use App\Http\Requests\Ajax\Onboarding\SignupOnboardingRequest;
use App\Http\Requests\Ajax\Onboarding\SubmitOnboardingVaultRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\OnboardingSession;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingAjaxController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                __('Unauthenticated.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(401);
        }

        $session = OnboardingSession::query()->where('user_id', $user->id)->first();

        return AjaxEnvelope::ok(
            '',
            data: [
                'onboarding_completed' => $user->hasCompletedOnboarding(),
                'onboarding_status' => $user->onboarding_status,
                'wallet_status' => $user->hasCompletedOnboarding() ? 'active' : 'inactive',
                'onboarding_state' => $session?->state,
            ],
            meta: [
                'retryable' => false,
                'requires_action' => $user->hasCompletedOnboarding() ? null : 'finish_onboarding',
            ],
        )->toJsonResponse();
    }

    public function signup(SignupOnboardingRequest $request, OnboardingStateMachine $machine): JsonResponse
    {
        $username = $request->string('username')->toString();
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $email = Str::lower($username).'@users.'.$host;

        $user = User::query()->create([
            'name' => $username,
            'username' => Str::lower($username),
            'email' => $email,
            'password' => $request->input('password'),
            'email_verified_at' => now(),
            'onboarding_status' => 'pending',
            'onboarding_completed_at' => null,
        ]);

        event(new Registered($user));

        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event_type' => 'onboarding_started',
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'metadata_json' => [],
            'created_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $boot = $machine->bootstrapAfterSignup($user);
        $request->session()->put('onboarding_step_token_plain', $boot['step_token']);

        return AjaxEnvelope::ok(
            __('Account created. Continue wallet setup.'),
            data: [
                'onboarding_status' => $user->onboarding_status,
                'onboarding_state' => $boot['session']->state,
                'verifier_salt_hex' => $boot['verifier_salt_hex'],
                'next_step' => 'vault_upload',
            ],
            severity: NotificationSeverity::Success,
            toast: AjaxEnvelope::toastPayload(
                __('Signed up'),
                __('Continue to secure your wallet.'),
                NotificationSeverity::Success,
            ),
            meta: ['retryable' => false],
        )->toJsonResponse();
    }

    public function submitVault(
        SubmitOnboardingVaultRequest $request,
        OnboardingStateMachine $machine,
    ): JsonResponse {
        $user = $request->user();
        $session = OnboardingSession::query()->where('user_id', $user->id)->firstOrFail();

        try {
            $nextToken = $machine->submitVault(
                $user,
                $session,
                $request->string('step_token')->toString(),
                $request->input('wallet_vault', []),
                $request->string('passphrase_verifier_hmac_hex')->toString(),
                $request->input('addresses', []),
                $request->string('public_wallet_id')->toString(),
                $request->string('vault_version')->toString(),
            );
        } catch (InvalidOnboardingTransitionException) {
            return AjaxEnvelope::error(
                AjaxResponseCode::OnboardingStateInvalid,
                __('This step is no longer valid. Refresh the page and try again.'),
                NotificationSeverity::Warning,
                meta: ['retryable' => true],
            )->toJsonResponse(409);
        } catch (ChainAdapterException $e) {
            return AjaxEnvelope::error(
                AjaxResponseCode::WalletBootstrapFailed,
                __('Wallet addresses could not be validated.'),
                NotificationSeverity::Danger,
                meta: ['retryable' => true],
            )->toJsonResponse(422);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            return AjaxEnvelope::error(
                AjaxResponseCode::WalletBootstrapFailed,
                __('Wallet setup failed. Try again.'),
                NotificationSeverity::Danger,
                meta: ['retryable' => true],
            )->toJsonResponse(500);
        }

        $request->session()->put('onboarding_step_token_plain', $nextToken);
        $session->refresh();

        return AjaxEnvelope::ok(
            __('Encrypted wallet stored.'),
            data: [
                'onboarding_state' => $session->state,
                'step_token' => $nextToken,
                'next_step' => 'passphrase_reveal',
                'wallet_status' => 'inactive',
            ],
            meta: ['retryable' => false],
        )->toJsonResponse();
    }

    public function acknowledgePassphrase(
        AcknowledgePassphraseRequest $request,
        OnboardingStateMachine $machine,
    ): JsonResponse {
        $user = $request->user();
        $session = OnboardingSession::query()->where('user_id', $user->id)->firstOrFail();

        try {
            $nextToken = $machine->acknowledgePassphrase(
                $user,
                $session,
                $request->string('step_token')->toString(),
            );
        } catch (InvalidOnboardingTransitionException) {
            return AjaxEnvelope::error(
                AjaxResponseCode::OnboardingStateInvalid,
                __('This step is no longer valid. Refresh the page and try again.'),
                NotificationSeverity::Warning,
                meta: ['retryable' => true],
            )->toJsonResponse(409);
        }

        $request->session()->put('onboarding_step_token_plain', $nextToken);
        $session->refresh();

        return AjaxEnvelope::ok(
            '',
            data: [
                'onboarding_state' => $session->state,
                'step_token' => $nextToken,
                'next_step' => 'passphrase_confirm',
            ],
            meta: ['retryable' => false],
        )->toJsonResponse();
    }

    public function confirmPassphrase(
        ConfirmPassphraseRequest $request,
        OnboardingStateMachine $machine,
    ): JsonResponse {
        $user = $request->user();
        $session = OnboardingSession::query()->where('user_id', $user->id)->firstOrFail();

        try {
            $machine->confirmPassphrase(
                $user,
                $session,
                $request->string('step_token')->toString(),
                $request->string('mnemonic')->toString(),
            );
        } catch (InvalidOnboardingTransitionException) {
            return AjaxEnvelope::error(
                AjaxResponseCode::OnboardingStateInvalid,
                __('This step is no longer valid. Refresh the page and try again.'),
                NotificationSeverity::Warning,
                meta: ['retryable' => true],
            )->toJsonResponse(409);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['step_token'])) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::OnboardingStateInvalid,
                    __('This step is no longer valid. Refresh the page and try again.'),
                    NotificationSeverity::Warning,
                    meta: ['retryable' => true],
                )->toJsonResponse(409);
            }
            if (isset($errors['mnemonic'])) {
                return AjaxEnvelope::error(
                    AjaxResponseCode::PassphraseConfirmationFailed,
                    __('Recovery phrase does not match.'),
                    NotificationSeverity::Danger,
                    meta: [
                        'retryable' => true,
                        'onboarding_state' => $session->fresh()->state,
                        'passphrase_attempts' => $session->fresh()->passphrase_attempts,
                    ],
                )->toJsonResponse(422);
            }

            return AjaxEnvelope::validationFailed($errors)->toJsonResponse(422);
        }

        $user->refresh();
        $request->session()->forget('onboarding_step_token_plain');

        $envelope = AjaxEnvelope::ok(
            __('Wallet is ready.'),
            data: [
                'onboarding_completed' => true,
                'onboarding_state' => OnboardingState::Completed->value,
                'wallet_status' => 'active',
                'next_step' => 'dashboard',
            ],
            toast: AjaxEnvelope::toastPayload(
                __('Welcome'),
                __('Your wallet is active.'),
                NotificationSeverity::Success,
            ),
            meta: ['retryable' => false],
        );
        $envelope->redirect = route('dashboard', absolute: false);

        return $envelope->toJsonResponse();
    }
}
