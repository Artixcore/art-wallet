<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOnboardingComplete
{
    /**
     * Block app areas until wallet onboarding is finished (fail-closed).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if ($user->hasCompletedOnboarding()) {
            return $next($request);
        }

        if ($this->allowedWhileIncomplete($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $envelope = AjaxEnvelope::error(
                AjaxResponseCode::OnboardingIncomplete,
                __('Complete wallet setup first.'),
                NotificationSeverity::Warning,
                meta: ['retryable' => false],
            );
            $envelope->redirect = route('onboarding.show', absolute: false);

            return $envelope->toJsonResponse(409);
        }

        return redirect()->route('onboarding.show');
    }

    private function allowedWhileIncomplete(Request $request): bool
    {
        $route = $request->route();
        if ($route === null) {
            return false;
        }

        $name = $route->getName();

        return $name !== null && (
            str_starts_with($name, 'onboarding.')
            || str_starts_with($name, 'ajax.onboarding.')
            || $name === 'logout'
        );
    }
}
