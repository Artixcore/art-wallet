<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', 'in:draft,active,disabled'],
            'budget_json' => ['nullable', 'array'],
            'budget_json.max_tokens_per_run' => ['sometimes', 'integer', 'min:256', 'max:128000'],
            'budget_json.timeout_seconds' => ['sometimes', 'integer', 'min:10', 'max:600'],
        ];
    }
}
