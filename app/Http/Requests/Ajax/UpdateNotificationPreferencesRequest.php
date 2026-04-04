<?php

namespace App\Http\Requests\Ajax;

use App\Domain\Notifications\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $categories = array_map(fn (NotificationCategory $c) => $c->value, NotificationCategory::cases());

        return [
            'preferences' => ['required', 'array', 'max:32'],
            'preferences.*.category' => ['required', 'string', Rule::in($categories)],
            'preferences.*.toast_enabled' => ['required', 'boolean'],
            'preferences.*.persist_enabled' => ['required', 'boolean'],
            'preferences.*.email_enabled' => ['required', 'boolean'],
        ];
    }
}
