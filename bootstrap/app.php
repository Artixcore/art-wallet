<?php

use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Settings\Exceptions\SettingsConflictException;
use App\Http\Middleware\RecordSessionActivity;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateOpsMonitorToken;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/ajax.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ops.monitor' => ValidateOpsMonitorToken::class,
        ]);
        $middleware->appendToGroup('web', [
            SecurityHeaders::class,
            RecordSessionActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $ajaxAware = static function (Request $request): bool {
            return $request->expectsJson()
                || $request->header('X-Requested-With') === 'XMLHttpRequest';
        };

        $exceptions->render(function (SettingsConflictException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::error(
                AjaxResponseCode::Conflict,
                $e->getMessage(),
                NotificationSeverity::Warning,
            )->toJsonResponse(409);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::validationFailed(
                $e->errors(),
                $e->getMessage() !== '' ? $e->getMessage() : __('Validation failed.'),
            )->toJsonResponse(422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                __('Unauthenticated.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::error(
                AjaxResponseCode::Forbidden,
                __('Forbidden.'),
                NotificationSeverity::Danger,
            )->toJsonResponse($e->getStatusCode() ?: 403);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::error(
                AjaxResponseCode::Forbidden,
                __('Forbidden.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request)) {
                return null;
            }

            return AjaxEnvelope::error(
                AjaxResponseCode::NotFound,
                __('Not found.'),
                NotificationSeverity::Info,
            )->toJsonResponse(404);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($ajaxAware) {
            if (! $ajaxAware($request) || config('app.debug')) {
                return null;
            }
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AccessDeniedHttpException
                || $e instanceof AuthorizationException
                || $e instanceof NotFoundHttpException) {
                return null;
            }
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            $id = (string) str()->uuid();
            Log::error('unhandled_exception', [
                'correlation_id' => $id,
                'exception' => $e::class,
            ]);

            return AjaxEnvelope::serverError($id)->toJsonResponse(500);
        });
    })->create();
