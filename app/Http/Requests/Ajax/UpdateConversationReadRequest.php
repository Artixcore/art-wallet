<?php

namespace App\Http\Requests\Ajax;

class UpdateConversationReadRequest extends AjaxFormRequest
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
            'last_read_message_id' => ['required', 'integer', 'exists:messages,id'],
        ];
    }
}
