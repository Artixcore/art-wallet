<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletSettingsRequest extends FormRequest
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
            'default_fee_tier' => ['nullable', 'string', 'max:32'],
            'show_testnet_assets' => ['required', 'boolean'],
            'settings_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
