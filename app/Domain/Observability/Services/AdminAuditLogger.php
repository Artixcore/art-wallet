<?php

declare(strict_types=1);

namespace App\Domain\Observability\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

final class AdminAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata  Must be redacted upstream; never secrets.
     */
    public function log(
        string $eventType,
        ?User $actor,
        string $actorType,
        ?int $subjectUserId,
        ?string $resourceType,
        ?string $resourceId,
        string $sensitivity,
        array $metadata,
        ?Request $request = null,
    ): AdminAuditLog {
        $req = $request ?? request();

        return AdminAuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'actor_type' => $actorType,
            'event_type' => $eventType,
            'subject_user_id' => $subjectUserId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $req?->ip(),
            'user_agent_hash' => $req ? hash('sha256', (string) $req->userAgent()) : null,
            'metadata_json' => $metadata,
            'sensitivity' => $sensitivity,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }
}
