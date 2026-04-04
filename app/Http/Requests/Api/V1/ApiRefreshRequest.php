<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ApiRefreshRequest extends FormRequest
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
            'refresh_token' => ['required', 'string', 'min:32', 'max:512'],
            'device_id' => ['required', 'string', 'max:128', 'regex:/^[a-zA-Z0-9._:-]+$/'],
        ];
    }
}
