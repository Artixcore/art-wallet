<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Network\NetworkMetadataService;
use Illuminate\Http\JsonResponse;

class NetworkMetadataAjaxController extends Controller
{
    public function index(NetworkMetadataService $networks): JsonResponse
    {
        return response()->json(['networks' => $networks->enabledNetworksWithAssets()]);
    }
}
