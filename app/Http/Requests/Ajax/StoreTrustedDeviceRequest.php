<?php

namespace App\Http\Requests\Ajax;

use App\Services\DeviceTrustService;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrustedDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'public_key' => ['required', 'string', 'max:128'],
            'device_label_ciphertext' => ['nullable', 'string', 'max:65535'],
            'fingerprint_signals_json' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $pk = $this->input('public_key');
            if (! is_string($pk)) {
                return;
            }
            if (! app(DeviceTrustService::class)->verifyPublicKeyFormat($pk)) {
                $validator->errors()->add('public_key', __('Invalid device public key.'));
            }
        });
    }
}
