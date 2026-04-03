<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthAjaxController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'time' => now()->toIso8601String(),
        ]);
    }
}
