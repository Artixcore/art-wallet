<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Structural validation for client-built recovery kit envelopes — never decrypts.
 */
class RecoveryBlobValidator
{
    public const KIT_FORMAT = 'artwallet-recovery-kit-v1';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateRecoveryKitEnvelope(array $data): array
    {
        $v = Validator::make($data, [
            'format' => ['required', 'string', 'in:'.self::KIT_FORMAT],
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
            'aad_hint' => ['required', 'string', 'max:256'],
            'kit_version' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        $nonce = base64_decode($data['nonce'], true);
        if ($nonce === false || strlen($nonce) !== 12) {
            throw ValidationException::withMessages(['nonce' => ['Invalid recovery kit nonce length.']]);
        }

        $ct = base64_decode($data['ciphertext'], true);
        if ($ct === false || strlen($ct) < 16) {
            throw ValidationException::withMessages(['ciphertext' => ['Invalid recovery kit ciphertext.']]);
        }

        $salt = base64_decode($data['kdf_params']['salt'], true);
        if ($salt === false || strlen($salt) < 16) {
            throw ValidationException::withMessages(['kdf_params.salt' => ['Invalid KDF salt.']]);
        }

        return $v->validated();
    }
}
