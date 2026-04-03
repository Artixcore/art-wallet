<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal user model for Testbench auth + morph assignments.
 */
final class User extends Authenticatable
{
    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
