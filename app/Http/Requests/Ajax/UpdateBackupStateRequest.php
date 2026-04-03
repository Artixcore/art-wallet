<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBackupStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'mnemonic_verified' => ['sometimes', 'boolean'],
            'strict_security_mode' => ['sometimes', 'boolean'],
            'hint_public' => ['nullable', 'string', 'max:255'],
        ];
    }
}
