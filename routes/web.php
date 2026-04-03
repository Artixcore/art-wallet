<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CryptoPocController;
use App\Http\Controllers\Web\SecurityWebController;
use App\Http\Controllers\Web\WalletTransactionsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/crypto/poc', [CryptoPocController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('crypto.poc');

Route::get('/security', [SecurityWebController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('security.index');

Route::get('/wallet/transactions', [WalletTransactionsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('wallet.transactions');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
