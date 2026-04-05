<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Onboarding\Services\PassphraseVerifierService;
use App\Models\OnboardingPassphraseVerifier;
use App\Models\OnboardingSession;
use App\Models\User;
use Database\Seeders\SupportedNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SupportedNetworkSeeder::class);
    }

    public function test_ajax_signup_creates_user_onboarding_session_and_session_token(): void
    {
        $password = 'LongPassword#1ForTests!';

        $response = $this->postJson('/ajax/onboarding/signup', [
            'username' => 'testuser',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.onboarding_state', 'awaiting_vault_upload');

        $this->assertAuthenticated();
        $response->assertSessionHas('onboarding_step_token_plain');

        $user = User::query()->where('username', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->onboarding_completed_at);
        $this->assertDatabaseHas('onboarding_sessions', [
            'user_id' => $user->id,
            'state' => 'awaiting_vault_upload',
        ]);
    }

    public function test_dashboard_redirects_when_onboarding_incomplete(): void
    {
        $user = User::factory()->onboardingPending()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.show'));
    }

    public function test_onboarding_page_ok_for_incomplete_user(): void
    {
        $user = User::factory()->onboardingPending()->create([
            'email_verified_at' => now(),
        ]);
        OnboardingSession::query()->create([
            'user_id' => $user->id,
            'state' => 'awaiting_vault_upload',
            'step_token_hash' => hash('sha256', 'test-token-plain'),
            'step_token_expires_at' => now()->addHour(),
            'passphrase_attempts' => 0,
        ]);

        $this->actingAs($user)->get(route('onboarding.show'))->assertOk();
    }

    public function test_passphrase_hmac_matches_across_service_and_normalized_phrase(): void
    {
        /** @var PassphraseVerifierService $svc */
        $svc = app(PassphraseVerifierService::class);
        $saltHex = bin2hex(random_bytes(32));
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon art';
        $normalized = $svc->normalizeMnemonic($mnemonic);
        $hex = $svc->computeHmacHex($saltHex, $normalized);

        $verifier = new OnboardingPassphraseVerifier([
            'verifier_salt_hex' => $saltHex,
            'verifier_hmac_hex' => $hex,
        ]);

        $this->assertTrue($svc->verify($verifier, $normalized));
        $this->assertFalse($svc->verify($verifier, $normalized.'wrong'));
    }
}
