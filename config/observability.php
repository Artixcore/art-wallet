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

    /*
    |--------------------------------------------------------------------------
    | External monitoring (token-gated)
    |--------------------------------------------------------------------------
    |
    | When OPS_MONITOR_TOKEN is empty, GET /ops/monitor/health returns 404 so the
    | route cannot be used without explicit opt-in. Uses the same TTL rules as the
    | operator dashboard (stale = probe older than ttl_seconds.*).
    |
    */

    'monitoring' => [
        'token' => env('OPS_MONITOR_TOKEN'),
        'fail_on_stale' => filter_var(env('OPS_MONITOR_FAIL_ON_STALE', true), FILTER_VALIDATE_BOOL),
        'fail_on_critical' => filter_var(env('OPS_MONITOR_FAIL_ON_CRITICAL', true), FILTER_VALIDATE_BOOL),
        'fail_on_partial' => filter_var(env('OPS_MONITOR_FAIL_ON_PARTIAL', false), FILTER_VALIDATE_BOOL),
    ],

];
