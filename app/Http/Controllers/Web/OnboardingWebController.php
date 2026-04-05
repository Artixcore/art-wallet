<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Onboarding\Enums\OnboardingState;
use App\Domain\Onboarding\Services\OnboardingTokenService;
use App\Http\Controllers\Controller;
use App\Models\OnboardingPassphraseVerifier;
use App\Models\OnboardingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingWebController extends Controller
{
    public function signupForm(): View
    {
        return view('onboarding.wizard', [
            'stepToken' => '',
            'verifierSaltHex' => '',
            'onboardingState' => 'awaiting_signup',
            'guestSignup' => true,
        ]);
    }

    public function show(Request $request, OnboardingTokenService $tokens): View|RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('register');
        }

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $onboardingSession = OnboardingSession::query()->where('user_id', $user->id)->first();
        if ($onboardingSession?->stateEnum() === OnboardingState::LockedOut) {
            return view('onboarding.locked');
        }

        if ($onboardingSession !== null && ! $request->session()->has('onboarding_step_token_plain')) {
            $plain = $tokens->rotate($onboardingSession);
            $request->session()->put('onboarding_step_token_plain', $plain);
        }

        $verifier = OnboardingPassphraseVerifier::query()->where('user_id', $user->id)->first();

        return view('onboarding.wizard', [
            'stepToken' => (string) $request->session()->get('onboarding_step_token_plain', ''),
            'verifierSaltHex' => $verifier?->verifier_salt_hex ?? '',
            'onboardingState' => $onboardingSession?->state ?? OnboardingState::AwaitingVaultUpload->value,
            'guestSignup' => false,
        ]);
    }
}
