<?php

declare(strict_types=1);

namespace App\Services\Tx;

/**
 * Builds expected ERC-20 transfer calldata for intent verification.
 */
final class Erc20TransferEncoder
{
    private const SELECTOR = 'a9059cbb';

    public function encode(string $recipientAddress, string $amountAtomicDecimal): string
    {
        $to = $this->normalizeAddress($recipientAddress);
        $amountHex = $this->decimalToPaddedHex($amountAtomicDecimal);

        return '0x'.self::SELECTOR.str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT).$amountHex;
    }

    private function normalizeAddress(string $address): string
    {
        $a = strtolower($address);
        if (! str_starts_with($a, '0x')) {
            $a = '0x'.$a;
        }

        return $a;
    }

    private function decimalToPaddedHex(string $decimal): string
    {
        if (! preg_match('/^\d+$/', $decimal)) {
            throw new \InvalidArgumentException('amount_atomic must be a non-negative decimal string.');
        }
        $hex = $this->bcDecToHex($decimal);

        return str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    private function bcDecToHex(string $decimal): string
    {
        if (function_exists('gmp_init')) {
            $h = gmp_strval(gmp_init($decimal, 10), 16);

            return strlen($h) % 2 === 1 ? '0'.$h : $h;
        }
        $hex = '';
        $num = $decimal;
        while (bccomp($num, '0', 0) > 0) {
            $rem = bcmod($num, '16');
            $hex = dechex((int) $rem).$hex;
            $num = bcdiv($num, '16', 0);
        }

        return $hex === '' ? '0' : $hex;
    }
}
