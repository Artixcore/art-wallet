<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessagingPrivacySettingsRequest extends FormRequest
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
            'read_receipts_enabled' => ['required', 'boolean'],
            'typing_indicators_enabled' => ['required', 'boolean'],
            'max_attachment_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'safety_warnings_enabled' => ['required', 'boolean'],
            'settings_version' => ['required', 'integer', 'min:1'],
            'step_up_token' => ['nullable', 'string', 'size:48'],
        ];
    }
}
