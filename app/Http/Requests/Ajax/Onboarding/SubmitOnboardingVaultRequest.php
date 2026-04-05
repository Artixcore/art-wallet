<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOnboardingVaultRequest extends FormRequest
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
            'step_token' => ['required', 'string', 'max:128'],
            'public_wallet_id' => ['required', 'uuid'],
            'vault_version' => ['required', 'string', 'max:32'],
            'wallet_vault' => ['required', 'array'],
            'passphrase_verifier_hmac_hex' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            'addresses' => ['required', 'array', 'size:3'],
            'addresses.*.chain' => ['required', 'string', 'in:BTC,ETH,SOL'],
            'addresses.*.address' => ['required', 'string', 'max:128'],
            'addresses.*.derivation_path' => ['required', 'string', 'max:128'],
        ];
    }
}
