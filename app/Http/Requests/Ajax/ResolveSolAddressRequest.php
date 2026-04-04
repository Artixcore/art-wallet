<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax;

class ResolveSolAddressRequest extends AjaxFormRequest
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
            'sol_address' => ['required', 'string', 'max:128'],
        ];
    }
}
