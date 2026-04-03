<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Casts;

use const JSON_THROW_ON_ERROR;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

use function json_decode;
use function json_encode;

/**
 * JSON metadata stored as plaintext JSON or Laravel-encrypted ciphertext (Phase 5).
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
final class MaybeEncryptedJson implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->enabled()) {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        try {
            $plain = $this->encrypter()->decryptString((string) $value);
        } catch (DecryptException $e) {
            throw new InvalidArgumentException(
                sprintf('Unable to decrypt [%s] on [%s].', $key, $model::class),
                0,
                $e,
            );
        }

        return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf('[%s] must be an array or null.', $key));
        }

        if (! $this->enabled()) {
            return [$key => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)];
        }

        return [$key => $this->encrypter()->encryptString(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))];
    }

    private function enabled(): bool
    {
        return (bool) app(ConfigRepository::class)->get('vaultrbac.encryption.metadata.enabled', false);
    }

    private function encrypter(): Encrypter
    {
        return app(Encrypter::class);
    }
}
