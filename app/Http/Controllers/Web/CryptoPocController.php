<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CryptoPocController extends Controller
{
    public function show(): View
    {
        return view('crypto.poc');
    }
}
