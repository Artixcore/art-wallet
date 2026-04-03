<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Http\Middleware;

use Artwallet\VaultRbac\Support\AuthorizationRequestMemo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears request-scoped authorization memo after the response is sent.
 */
final class FlushAuthorizationRequestMemoMiddleware
{
    public function __construct(
        private readonly AuthorizationRequestMemo $memo,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->memo->flush();
    }
}
