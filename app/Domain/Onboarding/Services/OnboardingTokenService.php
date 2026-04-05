<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Services;

use App\Models\OnboardingSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class OnboardingTokenService
{
    public const TOKEN_TTL_MINUTES = 30;

    /**
     * @return array{plain: string, hash: string, expires_at: Carbon}
     */
    public function mint(): array
    {
        $plain = Str::random(48);

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ];
    }

    public function validate(OnboardingSession $session, string $plainToken): bool
    {
        if ($session->step_token_expires_at->isPast()) {
            return false;
        }

        $hash = hash('sha256', $plainToken);

        return hash_equals($session->step_token_hash, $hash);
    }

    public function rotate(OnboardingSession $session): string
    {
        $minted = $this->mint();
        $session->update([
            'step_token_hash' => $minted['hash'],
            'step_token_expires_at' => $minted['expires_at'],
        ]);

        return $minted['plain'];
    }
}
