<?php

declare(strict_types=1);

return [

    'ethereum_rpc_url' => env('ARTWALLET_ETH_RPC_URL', 'https://cloudflare-eth.com'),

    'solana_rpc_url' => env('ARTWALLET_SOL_RPC_URL', 'https://api.mainnet-beta.solana.com'),

    'bitcoin_esplora_url' => env('ARTWALLET_BTC_ESPLORA_URL', 'https://blockstream.info/api'),

    'tron_api_url' => env('ARTWALLET_TRON_API_URL', 'https://api.trongrid.io'),

    'intent_ttl_minutes' => (int) env('ARTWALLET_INTENT_TTL_MINUTES', 15),

    'signing_request_ttl_minutes' => (int) env('ARTWALLET_SIGNING_REQUEST_TTL_MINUTES', 10),

    'fee_estimate_ttl_seconds' => (int) env('ARTWALLET_FEE_ESTIMATE_TTL_SECONDS', 60),

];
