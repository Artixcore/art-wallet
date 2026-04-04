<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Structural validation only — never decrypts user ciphertext.
 */
class CryptoEnvelopeValidator
{
    public const VAULT_FORMAT = 'artwallet-vault-v1';

    public const MESSAGE_ALG = 'AES-256-GCM';

    public const MESSAGE_VERSION = '1';

    public const WRAP_FORMAT = 'artwallet-wrap-v1';

    public const ATTACHMENT_MANIFEST_VERSION = '1';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> Validated data
     */
    public function validateVaultEnvelope(array $data): array
    {
        $v = Validator::make($data, [
            'format' => ['required', 'string', 'in:'.self::VAULT_FORMAT],
            'alg' => ['required', 'string', 'in:AES-256-GCM'],
            'kdf' => ['required', 'string', 'in:argon2id'],
            'kdf_params' => ['required', 'array'],
            'kdf_params.salt' => ['required', 'string'],
            'kdf_params.iterations' => ['required', 'integer', 'min:1', 'max:256'],
            'kdf_params.memoryKiB' => ['required', 'integer', 'min:8192', 'max:1048576'],
            'kdf_params.parallelism' => ['required', 'integer', 'min:1', 'max:8'],
            'kdf_params.hashLength' => ['required', 'integer', 'in:32'],
            'nonce' => ['required', 'string'],
            'ciphertext' => ['required', 'string'],
            'aad_hint' => ['required', 'string', 'in:vault-v1'],
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $decoded = $this->requireBase64Length($data['nonce'], 12, 'nonce');
        if ($decoded === null) {
            throw ValidationException::withMessages(['nonce' => ['Invalid vault nonce length.']]);
        }

        $ct = base64_decode($data['ciphertext'], true);
        if ($ct === false || strlen($ct) < 16) {
            throw ValidationException::withMessages(['ciphertext' => ['Invalid vault ciphertext.']]);
        }

        $salt = base64_decode($data['kdf_params']['salt'], true);
        if ($salt === false || strlen($salt) < 16) {
            throw ValidationException::withMessages(['kdf_params.salt' => ['Invalid KDF salt.']]);
        }

        return $v->validated();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateWrapEnvelope(array $data): array
    {
        $v = Validator::make($data, [
            'format' => ['required', 'string', 'in:'.self::WRAP_FORMAT],
            'alg' => ['required', 'string', 'in:AES-256-GCM'],
            'ephemeral_pub' => ['required', 'string'],
            'nonce' => ['required', 'string'],
            'ciphertext' => ['required', 'string'],
            'info' => ['required', 'string', 'max:512'],
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        if ($this->requireBase64Length($data['ephemeral_pub'], 32, 'ephemeral_pub') === null) {
            throw ValidationException::withMessages(['ephemeral_pub' => ['Invalid ephemeral key length.']]);
        }

        if ($this->requireBase64Length($data['nonce'], 12, 'nonce') === null) {
            throw ValidationException::withMessages(['nonce' => ['Invalid wrap nonce length.']]);
        }

        $ct = base64_decode($data['ciphertext'], true);
        if ($ct === false || strlen($ct) < 16) {
            throw ValidationException::withMessages(['ciphertext' => ['Invalid wrap ciphertext.']]);
        }

        return $v->validated();
    }

    public function validateMessageCipher(string $nonceB64, string $ciphertextB64, string $alg, string $version): void
    {
        if ($alg !== self::MESSAGE_ALG) {
            throw ValidationException::withMessages(['alg' => ['Unsupported message algorithm.']]);
        }

        if ($version !== self::MESSAGE_VERSION) {
            throw ValidationException::withMessages(['version' => ['Unsupported message version.']]);
        }

        if ($this->requireBase64Length($nonceB64, 12, 'nonce') === null) {
            throw ValidationException::withMessages(['nonce' => ['Invalid message nonce length.']]);
        }

        $ct = base64_decode($ciphertextB64, true);
        if ($ct === false || strlen($ct) < 16) {
            throw ValidationException::withMessages(['ciphertext' => ['Invalid message ciphertext.']]);
        }
    }

    /**
     * Structural validation for client-encrypted attachment manifest (opaque ciphertext fields).
     *
     * @param  array<string, mixed>  $data
     */
    public function validateAttachmentManifest(array $data): void
    {
        $v = Validator::make($data, [
            'version' => ['required', 'string', 'in:'.self::ATTACHMENT_MANIFEST_VERSION],
            'alg' => ['required', 'string', 'in:AES-256-GCM'],
            'nonce' => ['required', 'string'],
            'ciphertext' => ['required', 'string'],
            'original_name_ciphertext' => ['nullable', 'string'],
            'info' => ['required', 'string', 'max:512'],
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        if ($this->requireBase64Length($data['nonce'], 12, 'nonce') === null) {
            throw ValidationException::withMessages(['nonce' => ['Invalid attachment manifest nonce length.']]);
        }

        $ct = base64_decode($data['ciphertext'], true);
        if ($ct === false || strlen($ct) < 16) {
            throw ValidationException::withMessages(['ciphertext' => ['Invalid attachment manifest ciphertext.']]);
        }
    }

    private function requireBase64Length(string $b64, int $expectedBytes, string $field): ?string
    {
        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) !== $expectedBytes) {
            return null;
        }

        return $raw;
    }
}
