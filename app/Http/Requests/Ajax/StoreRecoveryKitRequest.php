<?php

namespace App\Http\Requests\Ajax;

use App\Services\RecoveryBlobValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreRecoveryKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'recovery_kit' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedKit(RecoveryBlobValidator $validator): array
    {
        $kit = $this->input('recovery_kit');
        if (! is_array($kit)) {
            throw ValidationException::withMessages(['recovery_kit' => ['Invalid payload.']]);
        }

        $validated = $validator->validateRecoveryKitEnvelope($kit);
        $hint = $validated['aad_hint'];
        $parts = explode('|', $hint);
        if (count($parts) !== 3 || $parts[0] !== 'artwallet-recovery-kit-v1') {
            throw ValidationException::withMessages(['aad_hint' => ['Invalid AAD binding.']]);
        }
        $uid = (int) $parts[1];
        if ($uid !== (int) $this->user()->id) {
            throw ValidationException::withMessages(['aad_hint' => ['AAD user mismatch.']]);
        }
        if ((int) $parts[2] !== (int) $validated['kit_version']) {
            throw ValidationException::withMessages(['kit_version' => ['Kit version does not match AAD.']]);
        }

        return $validated;
    }
}
