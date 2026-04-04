<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressResolutionAudit extends Model
{
    protected $table = 'address_resolution_audit';

    public $timestamps = false;

    protected $fillable = [
        'searcher_id',
        'address_hash',
        'outcome',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function searcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'searcher_id');
    }
}
