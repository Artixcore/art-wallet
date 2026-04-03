<?php

declare(strict_types=1);

namespace Tests\Unit;

use Artixcore\ArtGate\Facades\ArtGate;
use Tests\TestCase;

final class VaultRbacPhase1Test extends TestCase
{
    public function test_default_resolver_denies_all(): void
    {
        $this->assertFalse(ArtGate::check('anything'));
    }
}
