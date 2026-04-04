<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEAD_LETTER = 'dead_letter';

    protected $fillable = [
        'integration_endpoint_id',
        'event_id',
        'event_type',
        'payload_json',
        'idempotency_key',
        'attempt_count',
        'response_code',
        'status',
        'last_error',
        'delivered_at',
        'next_retry_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'delivered_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<IntegrationEndpoint, $this>
     */
    public function integrationEndpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class);
    }
}
