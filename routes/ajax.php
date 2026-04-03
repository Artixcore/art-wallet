<?php

use App\Http\Controllers\Api\HealthAjaxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('ajax')->group(function () {
    Route::get('/health', [HealthAjaxController::class, 'show'])->name('ajax.health');
});
