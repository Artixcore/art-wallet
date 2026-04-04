<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $family_id
 * @property string $token_hash
 */
class ApiRefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'api_device_id',
        'family_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'replaced_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ApiDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ApiDevice::class, 'api_device_id');
    }
}
