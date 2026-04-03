<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletVaultAjaxController extends Controller
{
    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        $wallet->refresh();

        return response()->json([
            'public_wallet_id' => $wallet->public_wallet_id,
            'vault_version' => $wallet->vault_version,
            'kdf_params' => $wallet->kdf_params,
            'wallet_vault' => json_decode((string) $wallet->wallet_vault_ciphertext, true, 512, JSON_THROW_ON_ERROR),
        ]);
    }
}
