<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Freshness TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | If a probe's observed_at is older than this value, the subsystem is stale.
    |
    */

    'ttl_seconds' => [
        'default' => (int) env('OBSERVABILITY_TTL_DEFAULT', 120),
        'queue' => (int) env('OBSERVABILITY_TTL_QUEUE', 180),
        'rpc' => (int) env('OBSERVABILITY_TTL_RPC', 300),
        'database' => (int) env('OBSERVABILITY_TTL_DATABASE', 60),
        'notifications' => (int) env('OBSERVABILITY_TTL_NOTIFICATIONS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | ArtGate permission names (bootstrap alongside is_admin on User)
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'dashboard' => 'ops.dashboard.view',
        'health' => 'ops.health.read',
        'security' => 'ops.security.read',
        'support' => 'ops.support.read',
        'audit_export' => 'ops.audit.export',
        'remediation_queue_retry' => 'ops.remediation.queue_retry',
        'remediation_session_revoke' => 'ops.remediation.session_revoke',
        'health_trigger_probe' => 'ops.health.trigger_probe',
        'incident_manage' => 'ops.incident.manage',
    ],

    'probe_version' => env('OBSERVABILITY_PROBE_VERSION', '1'),

];
