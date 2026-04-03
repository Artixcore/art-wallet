<?php

namespace App\Listeners;

use App\Models\UserSessionRecord;
use App\Services\DeviceTrustService;
use App\Services\SessionFingerprintService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class RevokeUserSessionOnLogout
{
    public function __construct(
        private SessionFingerprintService $fingerprints,
        private DeviceTrustService $deviceTrust,
    ) {}

    public function handle(Logout $event): void
    {
        if ($event->guard !== 'web' || $event->user === null) {
            return;
        }

        $sessionId = Session::getId();
        $hash = $this->fingerprints->hashSessionId($sessionId);

        UserSessionRecord::query()
            ->where('session_id_hash', $hash)
            ->where('user_id', $event->user->getAuthIdentifier())
            ->update(['revoked_at' => now()]);

        $this->deviceTrust->clearElevation(session()->driver());
    }
}
