<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

use Artwallet\VaultRbac\Models\Concerns\MapsVaultRbacTable;
use Illuminate\Database\Eloquent\Model;

class EncryptedMetadata extends Model
{
    use MapsVaultRbacTable;

    public $timestamps = false;

    protected static function vaultTableKey(): string
    {
        return 'encrypted_metadata';
    }

    protected $fillable = [
        'ciphertext',
        'wrapped_dek',
        'key_version',
        'nonce',
        'tag',
        'algo',
        'plaintext_fingerprint',
    ];
}
