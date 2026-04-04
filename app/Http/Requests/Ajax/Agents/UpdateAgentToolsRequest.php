<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentToolsRequest extends FormRequest
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
            'tools' => ['required', 'array', 'max:32'],
            'tools.*.tool_key' => ['required', 'string', 'max:128'],
            'tools.*.enabled' => ['required', 'boolean'],
        ];
    }
}
