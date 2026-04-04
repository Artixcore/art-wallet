<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSettingsRequest extends FormRequest
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
            'locale' => ['nullable', 'string', 'max:16'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'theme' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
            'ui_preferences' => ['nullable', 'array'],
            'ui_preferences_version' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'settings_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
