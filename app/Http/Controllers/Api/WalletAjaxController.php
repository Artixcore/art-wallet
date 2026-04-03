<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\StoreWalletRequest;
use App\Models\Wallet;
use App\Services\CryptoEnvelopeValidator;
use Illuminate\Http\JsonResponse;

class WalletAjaxController extends Controller
{
    public function store(StoreWalletRequest $request, CryptoEnvelopeValidator $crypto): JsonResponse
    {
        $validated = $request->validatedVaultEnvelope($crypto);
        $row = $request->validated();

        Wallet::query()->create([
            'user_id' => (int) $request->user()->id,
            'label' => $row['label'] ?? null,
            'public_wallet_id' => $row['public_wallet_id'],
            'vault_version' => $row['vault_version'],
            'kdf_params' => $validated['kdf_params'],
            'wallet_vault_ciphertext' => json_encode($request->input('wallet_vault'), JSON_THROW_ON_ERROR),
        ]);

        return response()->json(['ok' => true]);
    }
}
