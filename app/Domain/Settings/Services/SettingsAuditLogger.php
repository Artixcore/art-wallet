<?php

namespace App\Domain\Settings\Services;

use App\Models\AuditLog;
use App\Models\SettingsChangeLog;
use App\Models\User;
use Illuminate\Http\Request;

final class SettingsAuditLogger
{
    private const MAX_LEN = 512;

    public function logChange(
        User $user,
        string $scope,
        ?int $walletId,
        string $settingKey,
        mixed $oldValue,
        mixed $newValue,
        Request $request,
    ): void {
        SettingsChangeLog::query()->create([
            'user_id' => $user->id,
            'scope' => $scope,
            'wallet_id' => $walletId,
            'setting_key' => $settingKey,
            'old_value' => $this->stringify($oldValue),
            'new_value' => $this->stringify($newValue),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logAuditAction(User $user, string $action, Request $request, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata_json' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return $this->truncate((string) $value);
        }

        return $this->truncate(json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function truncate(string $s): string
    {
        if (strlen($s) <= self::MAX_LEN) {
            return $s;
        }

        return substr($s, 0, self::MAX_LEN).'…';
    }
}
