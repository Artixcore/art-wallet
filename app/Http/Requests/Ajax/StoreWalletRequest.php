<?php

namespace App\Http\Requests\Ajax;

use App\Services\CryptoEnvelopeValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'public_wallet_id' => ['required', 'uuid'],
            'vault_version' => ['required', 'string', 'max:32'],
            'wallet_vault' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedVaultEnvelope(CryptoEnvelopeValidator $validator): array
    {
        /** @var array<string, mixed> $vault */
        $vault = $this->input('wallet_vault', []);

        return $validator->validateVaultEnvelope($vault);
    }
}
