<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityPolicySettingsRequest extends FormRequest
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
            'idle_timeout_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'max_session_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'notify_new_device_login' => ['required', 'boolean'],
            'settings_version' => ['required', 'integer', 'min:1'],
            'step_up_token' => ['nullable', 'string', 'size:48'],
        ];
    }
}
