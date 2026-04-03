<?php

declare(strict_types=1);

namespace App\Services\Tx;

final class IntentHashService
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, int|string|null>  $fields
     */
    public function canonicalJson(array $fields): string
    {
        ksort($fields);

        return json_encode($fields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function hashCanonical(string $canonicalJson): string
    {
        return hash('sha256', $canonicalJson);
    }
}
