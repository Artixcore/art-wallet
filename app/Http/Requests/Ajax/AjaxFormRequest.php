<?php

namespace App\Http\Requests\Ajax;

use App\Http\Responses\AjaxEnvelope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation failures return {@see AjaxEnvelope} JSON for consistent AJAX clients.
 */
abstract class AjaxFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            AjaxEnvelope::validationFailed(
                $validator->errors()->toArray(),
            )->toJsonResponse(422)
        );
    }
}
