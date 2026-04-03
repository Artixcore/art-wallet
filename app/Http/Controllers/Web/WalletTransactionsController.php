<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletTransactionsController extends Controller
{
    public function show(Request $request): View
    {
        $wallets = Wallet::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get(['id', 'label', 'public_wallet_id']);

        return view('wallet.transactions', [
            'wallets' => $wallets,
        ]);
    }
}
