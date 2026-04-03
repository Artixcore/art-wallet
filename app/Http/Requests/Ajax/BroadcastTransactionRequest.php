<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastTransactionRequest extends FormRequest
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
            'server_nonce' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'signed_tx_hex' => ['required', 'string', 'max:2_000_000'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ];
    }
}
