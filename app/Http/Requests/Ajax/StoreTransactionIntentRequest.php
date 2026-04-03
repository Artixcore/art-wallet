<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionIntentRequest extends FormRequest
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
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'to_address' => ['required', 'string', 'max:128'],
            'amount_atomic' => ['required', 'string', 'max:80', 'regex:/^\d+$/'],
            'memo' => ['nullable', 'string', 'max:512'],
            'idempotency_client_key' => ['nullable', 'string', 'max:128'],
        ];
    }
}
