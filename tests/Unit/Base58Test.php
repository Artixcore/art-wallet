<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Messaging\Support\Base58;
use PHPUnit\Framework\TestCase;

class Base58Test extends TestCase
{
    public function test_decodes_solana_system_program_address_to_32_bytes(): void
    {
        $decoded = Base58::decode('11111111111111111111111111111112');
        $this->assertSame(32, strlen($decoded));
        // Reference: bs58 (Bitcoin alphabet) decodes this to 31 zero bytes + 0x01 (not 32 zero bytes).
        $this->assertSame("\x01", substr($decoded, -1));
        $this->assertSame(str_repeat("\0", 31), substr($decoded, 0, 31));
    }
}
