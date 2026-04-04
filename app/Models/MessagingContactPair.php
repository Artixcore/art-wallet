<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingContactPair extends Model
{
    protected $fillable = [
        'user_low_id',
        'user_high_id',
    ];
}
