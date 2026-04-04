<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskThresholdSettingsRequest extends FormRequest
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
            'large_tx_alert_fiat' => ['nullable', 'numeric', 'min:0'],
            'large_tx_alert_currency' => ['required', 'string', 'size:3'],
            'settings_version' => ['required', 'integer', 'min:1'],
            'step_up_token' => ['nullable', 'string', 'size:48'],
        ];
    }
}
