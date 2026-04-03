<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Casts;

use const JSON_THROW_ON_ERROR;

use Artwallet\VaultRbac\Exceptions\Data\InvalidEncryptedPayloadException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;

use function json_decode;
use function json_encode;

/**
 * Approval {@code payload} column (longText): plaintext JSON or Laravel-encrypted ciphertext.
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
final class MaybeEncryptedApprovalPayload implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! $this->encrypt()) {
            return $this->decodeJson((string) $value, $key, $model);
        }

        try {
            $plain = $this->encrypter()->decryptString((string) $value);
        } catch (DecryptException) {
            // Legacy rows may store plaintext JSON when encryption was disabled.
            $plain = (string) $value;
        }

        return $this->decodeJson($plain, $key, $model);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $plain, string $key, Model $model): array
    {
        try {
            $decoded = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidEncryptedPayloadException(
                sprintf('Invalid JSON for [%s] on [%s].', $key, $model::class),
                0,
                $e,
            );
        }

        if (! is_array($decoded)) {
            throw new InvalidEncryptedPayloadException(
                sprintf('[%s] on [%s] must decode to an object or array.', $key, $model::class),
            );
        }

        return $decoded;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (! is_array($value)) {
            throw new InvalidEncryptedPayloadException(sprintf('[%s] must be an array or null.', $key));
        }

        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        if (! $this->encrypt()) {
            return [$key => $json];
        }

        return [$key => $this->encrypter()->encryptString($json)];
    }

    private function encrypt(): bool
    {
        return (bool) app(ConfigRepository::class)->get('vaultrbac.encryption.approvals.encrypt_payload', true);
    }

    private function encrypter(): Encrypter
    {
        return app(Encrypter::class);
    }
}
