<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDeviceChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'challenge_public_uuid' => ['required', 'uuid'],
            'login_trusted_device_id' => ['required', 'integer', 'min:1'],
            'signature' => ['required', 'string', 'max:4096'],
        ];
    }
}
