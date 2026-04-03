<?php

namespace App\Listeners;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSessionRecord;
use App\Services\DeviceTrustService;
use App\Services\SessionFingerprintService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class RegisterUserSessionOnLogin
{
    public function __construct(
        private SessionFingerprintService $fingerprints,
        private DeviceTrustService $deviceTrust,
        private Request $request,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $sessionId = Session::getId();
        $hash = $this->fingerprints->hashSessionId($sessionId);

        $record = UserSessionRecord::query()->firstOrNew(['session_id_hash' => $hash]);
        $record->user_id = $user->id;
        $record->ip_hash = $this->fingerprints->hashIp($this->request->ip());
        $record->user_agent_hash = $this->fingerprints->hashUserAgent($this->request->userAgent());
        $record->session_id_encrypted = Crypt::encryptString($sessionId);
        if (! $record->exists) {
            $record->created_at = now();
        }
        $record->last_seen_at = now();
        $record->revoked_at = null;
        $record->save();

        $score = $this->deviceTrust->riskScoreForNewSession($user, $record);
        if ($score >= 3) {
            SecurityEvent::query()->create([
                'user_id' => $user->id,
                'event_type' => 'suspicious_login_signals',
                'severity' => 'warning',
                'ip_address' => $this->request->ip(),
                'metadata_json' => ['score' => $score],
                'created_at' => now(),
            ]);
        }
    }
}
