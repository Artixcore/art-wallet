<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPassphraseRequest extends FormRequest
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
            'mnemonic' => ['required', 'string', 'max:2000'],
        ];
    }
}
