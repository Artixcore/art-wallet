<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Casts;

use const JSON_THROW_ON_ERROR;

use Artwallet\VaultRbac\Exceptions\Data\CastTransformationException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonException;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

/**
 * Nullable JSON object/array column decoded as array. Malformed JSON throws.
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
final class ValidatedJsonArray implements CastsAttributes
{
    public function __construct(
        private readonly int $maxDepth = 32,
        private readonly int $maxKeys = 512,
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $this->assertShape($value, $key, $model);

            return $value;
        }

        if (! is_string($value)) {
            throw new CastTransformationException(
                sprintf('[%s] on [%s] must be JSON text, null, or array.', $key, $model::class),
            );
        }

        try {
            $decoded = json_decode($value, true, $this->maxDepth + 1, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new CastTransformationException(
                sprintf('[%s] on [%s] contains invalid JSON.', $key, $model::class),
                0,
                $e,
            );
        }

        if (! is_array($decoded)) {
            throw new CastTransformationException(
                sprintf('[%s] on [%s] must decode to an object or array.', $key, $model::class),
            );
        }

        $this->assertShape($decoded, $key, $model);

        return $decoded;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (! is_array($value)) {
            throw new CastTransformationException(
                sprintf('[%s] on [%s] must be an array or null.', $key, $model::class),
            );
        }

        $this->assertShape($value, $key, $model);

        return [$key => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)];
    }

    /**
     * @param  array<mixed>  $value
     */
    private function assertShape(array $value, string $key, Model $model): void
    {
        $totalKeys = 0;
        $this->walk($value, 1, $totalKeys, $key, $model);
    }

    /**
     * @param  array<mixed>  $value
     */
    private function walk(array $value, int $depth, int &$totalKeys, string $key, Model $model): void
    {
        if ($depth > $this->maxDepth) {
            throw new CastTransformationException(
                sprintf('[%s] on [%s] exceeds maximum nesting depth.', $key, $model::class),
            );
        }

        foreach ($value as $child) {
            $totalKeys++;
            if ($totalKeys > $this->maxKeys) {
                throw new CastTransformationException(
                    sprintf('[%s] on [%s] exceeds maximum key count.', $key, $model::class),
                );
            }
            if (is_array($child)) {
                $this->walk($child, $depth + 1, $totalKeys, $key, $model);
            }
        }
    }
}
