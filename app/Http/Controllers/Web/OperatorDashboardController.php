<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Observability\Services\AdminAuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OperatorDashboardController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize(config('observability.permissions.dashboard'));

        $this->audit->log(
            eventType: 'operator.dashboard.view',
            actor: $request->user(),
            actorType: 'operator',
            subjectUserId: null,
            resourceType: 'route',
            resourceId: 'operator.dashboard',
            sensitivity: 'low',
            metadata: ['panels' => ['summary']],
            request: $request,
        );

        return view('operator.dashboard');
    }
}
