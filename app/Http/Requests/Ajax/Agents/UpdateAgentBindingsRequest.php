<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentBindingsRequest extends FormRequest
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
            'bindings' => ['required', 'array', 'max:16'],
            'bindings.*.credential_id' => ['required', 'integer'],
            'bindings.*.priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'bindings.*.enabled' => ['required', 'boolean'],
        ];
    }
}
