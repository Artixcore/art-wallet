<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tenancy;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Reads the first non-empty tenant or team id from configured sources (Phase 4).
 *
 * @phpstan-type Source array{driver:string, name?:string, parameter?:string, key?:string, attribute?:string, cast?:?string}
 */
final class RequestSourceReader
{
    public function __construct(
        private readonly Application $app,
        private readonly AuthFactory $auth,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $sources
     */
    public function firstMatch(array $sources): string|int|null
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        $request = $this->app->make('request');
        if (! $request instanceof Request) {
            return null;
        }

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            $driver = (string) ($source['driver'] ?? '');
            $value = match ($driver) {
                'header' => $this->fromHeader($request, (string) ($source['name'] ?? '')),
                'query' => $request->query((string) ($source['parameter'] ?? '')),
                'route' => $this->fromRoute($request, $source),
                'session' => $this->fromSession($request, (string) ($source['key'] ?? '')),
                'request_attribute' => $request->attributes->get((string) ($source['key'] ?? '')),
                'user_attribute' => $this->fromUserAttribute((string) ($source['attribute'] ?? '')),
                default => null,
            };

            $normalized = $this->normalizeValue($value);
            if ($normalized !== null) {
                return $this->cast($normalized, isset($source['cast']) ? (string) $source['cast'] : null);
            }
        }

        return null;
    }

    private function fromHeader(Request $request, string $name): mixed
    {
        if ($name === '') {
            return null;
        }

        return $request->headers->get($name);
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fromRoute(Request $request, array $source): mixed
    {
        $parameter = (string) ($source['parameter'] ?? '');
        if ($parameter === '') {
            return null;
        }

        $resolved = $request->route($parameter);
        if ($resolved === null) {
            return null;
        }

        if (is_object($resolved) && method_exists($resolved, 'getKey')) {
            return $resolved->getKey();
        }

        return $resolved;
    }

    private function fromSession(Request $request, string $key): mixed
    {
        if ($key === '' || ! $request->hasSession()) {
            return null;
        }

        return $request->session()->get($key);
    }

    private function fromUserAttribute(string $attribute): mixed
    {
        if ($attribute === '') {
            return null;
        }

        $user = $this->auth->user();
        if ($user === null) {
            return null;
        }

        return data_get($user, $attribute);
    }

    private function normalizeValue(mixed $value): string|int|null
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        return null;
    }

    private function cast(string|int $value, ?string $cast): string|int
    {
        if ($cast === 'int') {
            return (int) $value;
        }

        if ($cast === 'string') {
            return (string) $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return $value;
    }
}
