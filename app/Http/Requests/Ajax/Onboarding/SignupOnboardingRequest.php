<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignupOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:'.User::class.',username'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
