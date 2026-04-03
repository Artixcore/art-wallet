<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletListAjaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = $request->user()->wallets()
            ->orderByDesc('id')
            ->get(['id', 'label', 'public_wallet_id', 'vault_version']);

        return response()->json(['wallets' => $rows]);
    }
}
