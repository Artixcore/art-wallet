<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Exceptions;

/**
 * Encryption, decryption, or AEAD verification failed for sensitive payloads.
 */
final class EncryptionException extends VaultRbacException {}
