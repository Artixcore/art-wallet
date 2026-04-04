<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentsWebController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agent::class);

        return view('agents.index');
    }

    public function edit(Request $request, Agent $agent): View
    {
        $this->authorize('update', $agent);

        return view('agents.edit', ['agent' => $agent]);
    }
}
