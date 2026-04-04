<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessTokenBase;

class SanctumPersonalAccessToken extends SanctumPersonalAccessTokenBase
{
    protected $table = 'personal_access_tokens';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'api_device_id',
    ];

    /**
     * @return BelongsTo<ApiDevice, $this>
     */
    public function apiDevice(): BelongsTo
    {
        return $this->belongsTo(ApiDevice::class, 'api_device_id');
    }
}
