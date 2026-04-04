<?php

namespace App\Domain\Settings\Services;

use App\Models\SettingsChangeConfirmation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StepUpTokenService
{
    private const PURPOSE_SETTINGS = 'settings_step_up';

    private const TTL_MINUTES = 10;

    /**
     * Issue a one-time step-up token after the caller has verified the account password (e.g. Form Request).
     */
    public function issueToken(User $user): string
    {
        $plain = Str::random(48);
        $hash = hash('sha256', $plain);

        SettingsChangeConfirmation::query()->create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'purpose' => self::PURPOSE_SETTINGS,
            'payload_json' => null,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'consumed_at' => null,
        ]);

        return $plain;
    }

    /**
     * Validate and consume a step-up token. Throws ValidationException if invalid.
     */
    public function assertValidAndConsume(User $user, ?string $plainToken): void
    {
        if ($plainToken === null || $plainToken === '') {
            throw ValidationException::withMessages([
                'step_up_token' => [__('A verified password step is required for this change.')],
            ]);
        }

        $hash = hash('sha256', $plainToken);

        $row = SettingsChangeConfirmation::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $hash)
            ->where('purpose', self::PURPOSE_SETTINGS)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($row === null) {
            throw ValidationException::withMessages([
                'step_up_token' => [__('This confirmation has expired. Verify your password again.')],
            ]);
        }

        $row->forceFill(['consumed_at' => now()])->save();
    }
}
