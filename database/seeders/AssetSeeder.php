<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'BTC', 'network' => 'BTC_MAINNET', 'decimals' => 8, 'contract_address' => null],
            ['code' => 'ETH', 'network' => 'ETH_MAINNET', 'decimals' => 18, 'contract_address' => null],
            ['code' => 'SOL', 'network' => 'SOL_MAINNET', 'decimals' => 9, 'contract_address' => null],
            ['code' => 'USDT', 'network' => 'ERC20', 'decimals' => 6, 'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7'],
            ['code' => 'USDT', 'network' => 'TRC20', 'decimals' => 6, 'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'],
        ];

        foreach ($rows as $row) {
            DB::table('assets')->updateOrInsert(
                ['code' => $row['code'], 'network' => $row['network']],
                [
                    'decimals' => $row['decimals'],
                    'contract_address' => $row['contract_address'],
                    'enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
