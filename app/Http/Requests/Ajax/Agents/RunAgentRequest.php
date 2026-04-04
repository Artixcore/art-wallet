<?php

namespace App\Http\Requests\Ajax\Agents;

use Illuminate\Foundation\Http\FormRequest;

class RunAgentRequest extends FormRequest
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
        ];
    }
}
