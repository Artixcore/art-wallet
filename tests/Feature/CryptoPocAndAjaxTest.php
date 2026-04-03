<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoPocAndAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_crypto_poc_redirects_guests(): void
    {
        $this->get(route('crypto.poc'))->assertRedirect(route('login'));
    }

    public function test_crypto_poc_renders_for_verified_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('crypto.poc'))
            ->assertOk()
            ->assertSee('Client crypto PoC', false);
    }

    public function test_ajax_health_redirects_guests(): void
    {
        $this->get(route('ajax.health'))->assertRedirect(route('login'));
    }

    public function test_ajax_health_returns_json_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ajax.health'))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
