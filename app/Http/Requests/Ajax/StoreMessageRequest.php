<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Validation\Validator;

class StoreMessageRequest extends AjaxFormRequest
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
        $maxCt = (int) config('messaging.max_ciphertext_bytes', 524_288);
        $maxAttach = (int) config('messaging.max_attachment_bytes', 26214400);

        return [
            'ciphertext' => ['required', 'string', 'max:'.($maxCt * 2)],
            'nonce' => ['required', 'string', 'max:64'],
            'alg' => ['required', 'string', 'max:32'],
            'version' => ['required', 'string', 'max:16'],
            'client_message_id' => ['nullable', 'uuid'],
            'idempotency_key' => ['nullable', 'uuid', 'max:64'],
            'attachment_ciphertext' => ['nullable', 'file', 'max:'.$maxAttach],
            'enc_manifest' => ['nullable', 'array'],
            'mime_hint' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('enc_manifest', 'required', fn () => $this->hasFile('attachment_ciphertext'));
    }
}
