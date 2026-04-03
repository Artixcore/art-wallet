<?php

declare(strict_types=1);

namespace App\Services\Tx;

use InvalidArgumentException;
use Web3p\RLP\RLP;

/**
 * Decodes EIP-1559 (type-2) signed transactions from raw hex.
 * Legacy type-0 parsing is not implemented (fail closed).
 */
final class EvmSignedTransactionDecoder
{
    private RLP $rlp;

    public function __construct(?RLP $rlp = null)
    {
        $this->rlp = $rlp ?? new RLP;
    }

    /**
     * @return array{chainId: string, nonce: string, to: ?string, value: string, data: string}
     */
    public function decodeType2(string $rawHex): array
    {
        $hex = strtolower($rawHex);
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        if ($hex === '' || strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException('Invalid signed transaction hex.');
        }
        $type = substr($hex, 0, 2);
        if ($type !== '02') {
            throw new InvalidArgumentException('Only EIP-1559 (type-2) transactions are supported for verification.');
        }
        $payload = substr($hex, 2);
        $decoded = $this->rlp->decode('0x'.$payload);
        if (! is_array($decoded) || count($decoded) < 12) {
            throw new InvalidArgumentException('Invalid RLP structure.');
        }

        $chainId = $this->rlpFieldToIntHex($decoded[0]);
        $nonce = $this->rlpFieldToIntHex($decoded[1]);
        $to = $this->rlpFieldToAddress($decoded[5]);
        $value = $this->rlpFieldToIntHex($decoded[6]);
        $data = $this->rlpFieldToHex($decoded[7]);

        return [
            'chainId' => $chainId,
            'nonce' => $nonce,
            'to' => $to,
            'value' => $value,
            'data' => $data,
        ];
    }

    private function rlpFieldToHex(mixed $field): string
    {
        if (! is_string($field)) {
            return '';
        }
        $h = strtolower($field);
        if ($h === '') {
            return '';
        }

        return strlen($h) % 2 === 1 ? '0'.$h : $h;
    }

    private function rlpFieldToIntHex(mixed $field): string
    {
        $hex = $this->rlpFieldToHex($field);
        if ($hex === '') {
            return '0';
        }

        return $this->stripLeadingZeros($hex);
    }

    private function rlpFieldToAddress(mixed $field): ?string
    {
        $hex = $this->rlpFieldToHex($field);
        if ($hex === '') {
            return null;
        }
        $padded = str_pad($hex, 64, '0', STR_PAD_LEFT);
        $addr = substr($padded, -40);

        return '0x'.$addr;
    }

    private function stripLeadingZeros(string $hex): string
    {
        $stripped = ltrim($hex, '0');

        return $stripped === '' ? '0' : $stripped;
    }
}
