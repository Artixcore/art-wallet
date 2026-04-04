<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Support;

use InvalidArgumentException;

/**
 * Bitcoin/Solana-style Base58 decode (no checksum).
 */
final class Base58
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * @throws InvalidArgumentException
     */
    public static function decode(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            throw new InvalidArgumentException('Empty Base58 input.');
        }

        $alphabet = self::ALPHABET;
        $indexes = [];
        for ($i = 0; $i < 58; $i++) {
            $indexes[$alphabet[$i]] = $i;
        }

        $bigint = '0';
        $len = strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $c = $input[$i];
            if (! isset($indexes[$c])) {
                throw new InvalidArgumentException('Invalid Base58 character.');
            }
            $bigint = bcmul($bigint, '58', 0);
            $bigint = bcadd($bigint, (string) $indexes[$c], 0);
        }

        $bytes = '';
        while (bccomp($bigint, '0', 0) > 0) {
            $bytes = chr((int) bcmod($bigint, '256')).$bytes;
            $bigint = bcdiv($bigint, '256', 0);
        }

        $leadingZeros = 0;
        for ($i = 0; $i < $len && $input[$i] === $alphabet[0]; $i++) {
            $leadingZeros++;
        }

        return str_repeat("\0", $leadingZeros).$bytes;
    }
}
