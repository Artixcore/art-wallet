<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Models;

/**
 * @deprecated Use {@see EncryptedMetadata}. Table key `encrypted_payloads` remains in config as an alias.
 */
class EncryptedPayload extends EncryptedMetadata
{
    protected static function vaultTableKey(): string
    {
        return 'encrypted_payloads';
    }
}
