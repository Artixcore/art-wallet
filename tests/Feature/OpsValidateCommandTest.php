<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OpsValidateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_validate_ci_passes_in_repo(): void
    {
        $this->artisan('ops:validate', ['--ci' => true])
            ->assertExitCode(0);
    }
}
