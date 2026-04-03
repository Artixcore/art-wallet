<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
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
            'ciphertext' => ['required', 'string'],
            'nonce' => ['required', 'string', 'max:64'],
            'alg' => ['required', 'string', 'max:32'],
            'version' => ['required', 'string', 'max:16'],
        ];
    }
}
