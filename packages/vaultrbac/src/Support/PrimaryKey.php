<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Support;

use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Normalizes primary key values for repository queries against configured id strategy.
 */
final class PrimaryKey
{
    private const UUID_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public static function normalize(mixed $id, ?ConfigRepository $config = null): int|string
    {
        $config ??= app(ConfigRepository::class);
        $strategy = (string) $config->get('vaultrbac.ids.type', 'bigint');

        if ($strategy === 'uuid') {
            return self::normalizeUuid($id);
        }

        return self::normalizeBigInt($id);
    }

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public static function normalizeBigInt(mixed $id): int
    {
        if (is_int($id)) {
            if ($id < 1) {
                throw new UnsupportedIdentifierTypeException('Primary key must be a positive integer.');
            }

            return $id;
        }

        if (is_string($id)) {
            $trim = trim($id);
            if ($trim === '' || ! ctype_digit($trim)) {
                throw new UnsupportedIdentifierTypeException('Primary key must be a numeric string or integer.');
            }
            $n = (int) $trim;
            if ($n < 1) {
                throw new UnsupportedIdentifierTypeException('Primary key must be a positive integer.');
            }

            return $n;
        }

        throw new UnsupportedIdentifierTypeException('Primary key must be an integer or numeric string.');
    }

    /**
     * @throws UnsupportedIdentifierTypeException
     */
    public static function normalizeUuid(mixed $id): string
    {
        if (! is_string($id) || trim($id) === '') {
            throw new UnsupportedIdentifierTypeException('Primary key must be a non-empty UUID string.');
        }

        $trim = trim($id);
        if (preg_match(self::UUID_PATTERN, $trim) !== 1) {
            throw new UnsupportedIdentifierTypeException('Primary key must be a valid UUID string.');
        }

        return strtolower($trim);
    }
}
