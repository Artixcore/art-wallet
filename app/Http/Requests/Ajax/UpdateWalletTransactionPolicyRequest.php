<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletTransactionPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed|string>|string>
     */
    public function rules(): array
    {
        return [
            'confirm_above_amount' => ['nullable', 'numeric', 'min:0'],
            'fiat_currency' => ['required', 'string', 'size:3'],
            'require_second_approval' => ['required', 'boolean'],
            'settings_version' => ['required', 'integer', 'min:1'],
            'step_up_token' => ['nullable', 'string', 'size:48'],
        ];
    }
}
