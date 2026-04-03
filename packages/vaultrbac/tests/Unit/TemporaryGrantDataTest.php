<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Api\Dto\TemporaryGrantData;
use Artwallet\VaultRbac\Tests\TestCase;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class TemporaryGrantDataTest extends TestCase
{
    public function test_valid_until_must_be_after_valid_from(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('validUntil must be after validFrom');

        $from = CarbonImmutable::parse('2026-01-01 12:00:00');
        $until = CarbonImmutable::parse('2026-01-01 11:00:00');

        new TemporaryGrantData($from, $until);
    }

    public function test_equal_bounds_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $t = CarbonImmutable::parse('2026-06-01 00:00:00');
        new TemporaryGrantData($t, $t);
    }
}
