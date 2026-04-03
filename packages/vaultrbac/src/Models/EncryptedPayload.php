<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;

class EncryptedPayload extends Model
{
    use MapsVaultRbacTable;

    protected static function vaultTableKey(): string
    {
        return 'encrypted_payloads';
    }

    protected $fillable = [
        'ciphertext',
        'dek_wrapped',
        'key_version',
    ];
}
