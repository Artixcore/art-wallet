<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class CompareProvidersRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:100000'],
            'max_concurrency' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'budget_max_calls' => ['sometimes', 'integer', 'min:1', 'max:8'],
        ];
    }
}
