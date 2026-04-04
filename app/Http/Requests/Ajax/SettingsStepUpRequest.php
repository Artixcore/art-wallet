<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class SettingsStepUpRequest extends FormRequest
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
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
