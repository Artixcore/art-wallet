<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $networkIds = [
            'BTC_MAINNET' => DB::table('supported_networks')->where('slug', 'BTC_MAINNET')->value('id'),
            'ETH_MAINNET' => DB::table('supported_networks')->where('slug', 'ETH_MAINNET')->value('id'),
            'SOL_MAINNET' => DB::table('supported_networks')->where('slug', 'SOL_MAINNET')->value('id'),
            'TRON_MAINNET' => DB::table('supported_networks')->where('slug', 'TRON_MAINNET')->value('id'),
        ];

        $rows = [
            [
                'code' => 'BTC',
                'network' => 'BTC_MAINNET',
                'supported_network_id' => $networkIds['BTC_MAINNET'],
                'asset_type' => 'native',
                'decimals' => 8,
                'contract_address' => null,
                'sort_order' => 10,
            ],
            [
                'code' => 'ETH',
                'network' => 'ETH_MAINNET',
                'supported_network_id' => $networkIds['ETH_MAINNET'],
                'asset_type' => 'native',
                'decimals' => 18,
                'contract_address' => null,
                'sort_order' => 20,
            ],
            [
                'code' => 'SOL',
                'network' => 'SOL_MAINNET',
                'supported_network_id' => $networkIds['SOL_MAINNET'],
                'asset_type' => 'native',
                'decimals' => 9,
                'contract_address' => null,
                'sort_order' => 30,
            ],
            [
                'code' => 'USDT',
                'network' => 'ETH_MAINNET',
                'supported_network_id' => $networkIds['ETH_MAINNET'],
                'asset_type' => 'erc20',
                'decimals' => 6,
                'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'sort_order' => 40,
            ],
            [
                'code' => 'USDT',
                'network' => 'TRON_MAINNET',
                'supported_network_id' => $networkIds['TRON_MAINNET'],
                'asset_type' => 'trc20',
                'decimals' => 6,
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'sort_order' => 50,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('assets')->updateOrInsert(
                ['code' => $row['code'], 'network' => $row['network']],
                [
                    'supported_network_id' => $row['supported_network_id'],
                    'asset_type' => $row['asset_type'],
                    'decimals' => $row['decimals'],
                    'contract_address' => $row['contract_address'],
                    'enabled' => true,
                    'sort_order' => $row['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
