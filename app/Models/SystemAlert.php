<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemAlert extends Model
{
    protected $fillable = [
        'severity',
        'title',
        'body',
        'status',
        'metadata_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
        ];
    }

    /**
     * @return HasMany<OperatorAlertAcknowledgement, $this>
     */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(OperatorAlertAcknowledgement::class);
    }
}
