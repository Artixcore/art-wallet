<?php

namespace App\Services;

use Illuminate\Http\Request;

class SessionFingerprintService
{
    public function hashSessionId(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, $this->pepper());
    }

    public function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, $this->pepper());
    }

    public function hashUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return hash_hmac('sha256', $userAgent, $this->pepper());
    }

    public function sessionBindingHash(string $sessionId, int $userId): string
    {
        $payload = $sessionId.'|'.$userId;

        return hash_hmac('sha256', $payload, $this->pepper());
    }

    public function requestSignals(Request $request): array
    {
        return [
            'ua_hash_prefix' => substr((string) $this->hashUserAgent($request->userAgent()), 0, 16),
            'ip_hash_prefix' => substr((string) $this->hashIp($request->ip()), 0, 16),
        ];
    }

    private function pepper(): string
    {
        $extra = (string) config('artwallet_security.session_binding_pepper', '');

        return $extra !== '' ? $extra : (string) config('app.key');
    }
}
