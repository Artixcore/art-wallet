<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentCredentialRequest extends FormRequest
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
            'provider' => ['required', 'string', Rule::in(['openai', 'anthropic', 'gemini', 'xai', 'finnhub', 'stub'])],
            'api_key' => ['required', 'string', 'max:2048'],
            'label' => ['nullable', 'string', 'max:120'],
            'default_model' => ['nullable', 'string', 'max:128'],
        ];
    }
}
