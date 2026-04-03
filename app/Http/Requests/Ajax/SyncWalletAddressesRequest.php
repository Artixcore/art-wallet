<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class SyncWalletAddressesRequest extends FormRequest
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
            'addresses' => ['required', 'array', 'min:1', 'max:32'],
            'addresses.*.supported_network_id' => ['required', 'integer', 'exists:supported_networks,id'],
            'addresses.*.address' => ['required', 'string', 'max:128'],
            'addresses.*.derivation_path' => ['nullable', 'string', 'max:128'],
            'addresses.*.derivation_index' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'addresses.*.is_change' => ['nullable', 'boolean'],
        ];
    }
}
