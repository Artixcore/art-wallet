<?php

namespace App\Services;

use App\Models\DeviceChallenge;
use App\Models\LoginTrustedDevice;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSessionRecord;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SodiumException;

class DeviceTrustService
{
    public function __construct(
        private SessionFingerprintService $fingerprints,
    ) {}

    public const PURPOSE_NEW_DEVICE = 'new_device';

    public const PURPOSE_STEP_UP = 'step_up';

    public const PURPOSE_RECOVERY = 'recovery';

    public const SIGN_MESSAGE_PREFIX = 'artwallet-device-challenge-v1|';

    public function issueChallenge(User $user, string $sessionId, string $purpose): DeviceChallenge
    {
        if (! in_array($purpose, [self::PURPOSE_NEW_DEVICE, self::PURPOSE_STEP_UP, self::PURPOSE_RECOVERY], true)) {
            throw new RuntimeException('Invalid challenge purpose.');
        }

        $nonce = base64_encode(random_bytes(32));
        $clientCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $binding = $this->fingerprints->sessionBindingHash($sessionId, (int) $user->id);
        $ttl = (int) config('artwallet_security.challenge_ttl_seconds', 600);

        return DeviceChallenge::query()->create([
            'public_uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'login_trusted_device_id' => null,
            'challenge_nonce' => $nonce,
            'client_code' => $clientCode,
            'session_binding_hash' => $binding,
            'purpose' => $purpose,
            'expires_at' => now()->addSeconds($ttl),
            'consumed_at' => null,
            'signature' => null,
            'created_at' => now(),
        ]);
    }

    public function signingMessage(DeviceChallenge $challenge, int $userId, int $trustVersion): string
    {
        return self::SIGN_MESSAGE_PREFIX
            .$challenge->challenge_nonce.'|'
            .$challenge->client_code.'|'
            .$userId.'|'
            .$challenge->purpose.'|'
            .$trustVersion;
    }

    /**
     * @return array{ok: true, challenge: DeviceChallenge}|array{ok: false}
     */
    public function approveChallenge(
        User $user,
        string $challengePublicUuid,
        LoginTrustedDevice $device,
        string $signatureBase64,
    ): array {
        if ($device->user_id !== $user->id || $device->isRevoked()) {
            $this->logEvent($user, 'device_challenge_denied', 'warning', ['reason' => 'invalid_device']);

            return ['ok' => false];
        }

        $result = DB::transaction(function () use ($user, $challengePublicUuid, $device, $signatureBase64) {
            /** @var DeviceChallenge|null $challenge */
            $challenge = DeviceChallenge::query()
                ->where('public_uuid', $challengePublicUuid)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->isConsumed() || $challenge->isExpired()) {
                return null;
            }

            $msg = $this->signingMessage($challenge, (int) $user->id, (int) $device->trust_version);
            $sig = base64_decode($signatureBase64, true);
            $pk = base64_decode($device->public_key, true);

            if ($sig === false || $pk === false || strlen($pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                return false;
            }

            try {
                $valid = sodium_crypto_sign_verify_detached($sig, $msg, $pk);
            } catch (SodiumException) {
                return false;
            }

            if (! $valid) {
                return false;
            }

            // Session binding is for the *requesting* browser (new device), verified when that
            // session calls tryElevateCurrentSession — not the approver's session.

            $challenge->login_trusted_device_id = $device->id;
            $challenge->signature = $signatureBase64;
            $challenge->consumed_at = now();
            $challenge->save();

            $device->last_used_at = now();
            $device->save();

            return $challenge;
        });

        if ($result === null) {
            $this->logEvent($user, 'device_challenge_invalid', 'warning', ['reason' => 'expired_or_missing']);

            return ['ok' => false];
        }

        if ($result === false) {
            $this->logEvent($user, 'device_challenge_bad_signature', 'warning', ['reason' => 'verify_failed']);

            return ['ok' => false];
        }

        $this->logEvent($user, 'device_challenge_approved', 'info', ['challenge_uuid' => $challengePublicUuid]);

        return ['ok' => true, 'challenge' => $result];
    }

    public function elevateSessionForBinding(Store $session, DeviceChallenge $challenge): void
    {
        $session->put('artwallet.session_elevated_at', now()->timestamp);
        $session->put('artwallet.elevation_challenge_uuid', $challenge->public_uuid);
    }

    public function isSessionElevated(Store $session): bool
    {
        $ts = $session->get('artwallet.session_elevated_at');

        return is_int($ts) && $ts > 0;
    }

    public function clearElevation(Store $session): void
    {
        $session->forget(['artwallet.session_elevated_at', 'artwallet.elevation_challenge_uuid']);
    }

    /**
     * Elevate the current session when a consumed challenge exists for this session binding.
     */
    public function tryElevateCurrentSession(User $user, Store $session): bool
    {
        $binding = $this->fingerprints->sessionBindingHash($session->getId(), (int) $user->id);
        $challenge = DeviceChallenge::query()
            ->where('user_id', $user->id)
            ->where('session_binding_hash', $binding)
            ->whereNotNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('consumed_at')
            ->first();

        if ($challenge === null) {
            return false;
        }

        $this->elevateSessionForBinding($session, $challenge);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function challengeStatusForCurrentSession(User $user, string $sessionId): ?array
    {
        $binding = $this->fingerprints->sessionBindingHash($sessionId, (int) $user->id);
        $challenge = DeviceChallenge::query()
            ->where('user_id', $user->id)
            ->where('session_binding_hash', $binding)
            ->orderByDesc('created_at')
            ->first();

        if ($challenge === null) {
            return null;
        }

        if ($challenge->isConsumed()) {
            return [
                'status' => 'approved',
                'public_uuid' => $challenge->public_uuid,
                'client_code' => $challenge->client_code,
            ];
        }

        if ($challenge->isExpired()) {
            return [
                'status' => 'expired',
                'public_uuid' => $challenge->public_uuid,
            ];
        }

        return [
            'status' => 'pending',
            'public_uuid' => $challenge->public_uuid,
            'client_code' => $challenge->client_code,
            'expires_at' => $challenge->expires_at->toIso8601String(),
        ];
    }

    public function verifyPublicKeyFormat(string $publicKeyBase64): bool
    {
        $pk = base64_decode($publicKeyBase64, true);

        return $pk !== false && strlen($pk) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
    }

    private function logEvent(User $user, string $type, string $severity, array $meta): void
    {
        SecurityEvent::query()->create([
            'user_id' => $user->id,
            'event_type' => $type,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'metadata_json' => $meta,
            'created_at' => now(),
        ]);
    }

    /**
     * Score risk for a login session vs prior records (coarse).
     *
     * @return int 0 = low, higher = more suspicious
     */
    public function riskScoreForNewSession(User $user, UserSessionRecord $newRecord): int
    {
        $score = 0;
        $prior = UserSessionRecord::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('id', '!=', $newRecord->id)
            ->orderByDesc('last_seen_at')
            ->limit(5)
            ->get();

        if ($prior->isEmpty()) {
            return 0;
        }

        $last = $prior->first();
        if ($last->ip_hash !== $newRecord->ip_hash) {
            $score += 2;
        }
        if ($last->user_agent_hash !== $newRecord->user_agent_hash) {
            $score += 1;
        }

        $recentFails = SecurityEvent::query()
            ->where('user_id', $user->id)
            ->whereIn('event_type', ['device_challenge_bad_signature', 'device_challenge_invalid'])
            ->where('created_at', '>=', now()->subHour())
            ->count();
        $score += min(3, (int) $recentFails);

        return $score;
    }
}
