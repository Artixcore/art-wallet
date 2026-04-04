<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentPromptRequest extends FormRequest
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
            'system_prompt' => ['required', 'string', 'max:100000'],
            'developer_prompt' => ['nullable', 'string', 'max:100000'],
        ];
    }
}
