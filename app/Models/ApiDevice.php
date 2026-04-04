<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 */
class ApiDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'name',
        'platform',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
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
     * @return HasMany<ApiRefreshToken, $this>
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(ApiRefreshToken::class, 'api_device_id');
    }
}
