<?php

namespace App\Http\Requests\Ajax;

class StoreMessagingDeviceRequest extends AjaxFormRequest
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
            'device_id' => ['required', 'uuid'],
            'device_ed25519_public_key_b64' => ['nullable', 'string', 'max:128'],
            'device_x25519_public_key_b64' => ['required', 'string', 'max:128'],
        ];
    }
}
