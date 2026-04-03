<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportedNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'chain' => 'BTC',
                'slug' => 'BTC_MAINNET',
                'display_name' => 'Bitcoin Mainnet',
                'chain_id' => null,
                'hrp' => null,
                'is_testnet' => false,
                'explorer_tx_url_template' => 'https://mempool.space/tx/{txid}',
                'enabled' => true,
                'sort_order' => 10,
            ],
            [
                'chain' => 'ETH',
                'slug' => 'ETH_MAINNET',
                'display_name' => 'Ethereum Mainnet',
                'chain_id' => 1,
                'hrp' => null,
                'is_testnet' => false,
                'explorer_tx_url_template' => 'https://etherscan.io/tx/{txid}',
                'enabled' => true,
                'sort_order' => 20,
            ],
            [
                'chain' => 'SOL',
                'slug' => 'SOL_MAINNET',
                'display_name' => 'Solana Mainnet',
                'chain_id' => null,
                'hrp' => null,
                'is_testnet' => false,
                'explorer_tx_url_template' => 'https://solscan.io/tx/{txid}',
                'enabled' => true,
                'sort_order' => 30,
            ],
            [
                'chain' => 'TRON',
                'slug' => 'TRON_MAINNET',
                'display_name' => 'Tron Mainnet',
                'chain_id' => null,
                'hrp' => null,
                'is_testnet' => false,
                'explorer_tx_url_template' => 'https://tronscan.org/#/transaction/{txid}',
                'enabled' => true,
                'sort_order' => 40,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('supported_networks')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
