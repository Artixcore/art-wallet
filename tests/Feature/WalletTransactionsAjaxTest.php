<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\PruneExpiredIntentsJob;
use App\Models\Asset;
use App\Models\SupportedNetwork;
use App\Models\TransactionIntent;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAddress;
use Database\Seeders\AssetSeeder;
use Database\Seeders\SupportedNetworkSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class WalletTransactionsAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_networks_index_returns_seeded_networks(): void
    {
        $this->seed(SupportedNetworkSeeder::class);
        $this->seed(AssetSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $res = $this->getJson('/ajax/networks');
        $res->assertOk();
        $res->assertJsonStructure(['networks' => [['slug', 'assets']]]);
    }

    public function test_creates_transaction_intent_with_faked_eth_rpc(): void
    {
        $this->seed(SupportedNetworkSeeder::class);
        $this->seed(AssetSeeder::class);
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'label' => 't',
            'public_wallet_id' => (string) Str::uuid(),
            'vault_version' => '1',
            'kdf_params' => [],
            'wallet_vault_ciphertext' => json_encode(['format' => 'artwallet-vault-v1'], JSON_THROW_ON_ERROR),
        ]);
        $eth = SupportedNetwork::query()->where('slug', 'ETH_MAINNET')->firstOrFail();
        $asset = Asset::query()->where('code', 'ETH')->where('network', 'ETH_MAINNET')->firstOrFail();
        WalletAddress::query()->create([
            'wallet_id' => $wallet->id,
            'supported_network_id' => $eth->id,
            'chain' => 'ETH',
            'address' => '0x1111111111111111111111111111111111111111',
            'derivation_path' => "m/44'/60'/0'/0/0",
            'derivation_index' => 0,
            'is_change' => false,
        ]);

        Http::fake([
            '*' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x2'])
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
                    'baseFeePerGas' => '0x3b9aca00',
                ]]),
        ]);

        $this->actingAs($user);
        $res = $this->postJson("/ajax/wallets/{$wallet->id}/transaction-intents", [
            'asset_id' => $asset->id,
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount_atomic' => '1000000000000000',
        ]);
        $res->assertCreated();
        $res->assertJsonPath('intent.status', 'awaiting_signature');
        $res->assertJsonStructure([
            'intent' => ['id', 'intent_hash', 'construction_payload', 'asset', 'network'],
            'signing_request' => ['server_nonce', 'expires_at'],
        ]);
    }

    public function test_prune_expired_intents_marks_cancelled(): void
    {
        $this->seed(SupportedNetworkSeeder::class);
        $this->seed(AssetSeeder::class);
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'label' => 't',
            'public_wallet_id' => (string) Str::uuid(),
            'vault_version' => '1',
            'kdf_params' => [],
            'wallet_vault_ciphertext' => json_encode(['format' => 'artwallet-vault-v1'], JSON_THROW_ON_ERROR),
        ]);
        $eth = SupportedNetwork::query()->where('slug', 'ETH_MAINNET')->firstOrFail();
        $asset = Asset::query()->where('code', 'ETH')->where('network', 'ETH_MAINNET')->firstOrFail();
        $intent = TransactionIntent::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset_id' => $asset->id,
            'supported_network_id' => $eth->id,
            'direction' => 'out',
            'from_address' => '0x1',
            'to_address' => '0x2',
            'amount_atomic' => '1',
            'memo' => null,
            'fee_quote_json' => null,
            'intent_hash' => str_repeat('a', 64),
            'status' => TransactionIntent::STATUS_AWAITING_SIGNATURE,
            'expires_at' => now()->subMinute(),
            'idempotency_client_key' => null,
            'construction_payload_json' => [],
        ]);

        (new PruneExpiredIntentsJob)->handle();

        $intent->refresh();
        $this->assertSame(TransactionIntent::STATUS_CANCELLED, $intent->status);
    }
}
