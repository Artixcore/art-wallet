<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'device_id' => ['required', 'string', 'max:128', 'regex:/^[a-zA-Z0-9._:-]+$/'],
            'device_name' => ['nullable', 'string', 'max:128'],
            'platform' => ['nullable', 'string', 'max:32', 'in:ios,android,web,desktop,other'],
        ];
    }
}
