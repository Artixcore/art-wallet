<?php

namespace App\Http\Requests\Ajax;

use App\Services\CryptoEnvelopeValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreConversationRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'max:32'],
            'public_id' => ['required', 'uuid'],
            'member_wraps' => ['required', 'array', 'min:1'],
            'member_wraps.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'member_wraps.*.wrapped_conv_key' => ['required', 'array'],
        ];
    }

    /**
     * @return list<array{user_id: int, wrapped_json: string}>
     */
    public function validatedWraps(CryptoEnvelopeValidator $crypto): array
    {
        $uid = (int) $this->user()->id;
        $publicId = (string) $this->input('public_id');
        $wraps = $this->input('member_wraps', []);
        $seen = [];
        $out = [];

        foreach ($wraps as $row) {
            $memberId = (int) ($row['user_id'] ?? 0);
            if (isset($seen[$memberId])) {
                throw ValidationException::withMessages(['member_wraps' => ['Duplicate member.']]);
            }
            $seen[$memberId] = true;

            /** @var array<string, mixed> $w */
            $w = $row['wrapped_conv_key'];
            $crypto->validateWrapEnvelope($w);
            $expectedInfo = 'wrap-v1|'.$publicId.'|'.$memberId;
            if (($w['info'] ?? '') !== $expectedInfo) {
                throw ValidationException::withMessages([
                    'member_wraps' => ['Wrap info must bind public_id and recipient user_id.'],
                ]);
            }
            $out[] = [
                'user_id' => $memberId,
                'wrapped_json' => json_encode($w, JSON_THROW_ON_ERROR),
            ];
        }

        if (! isset($seen[$uid])) {
            throw ValidationException::withMessages(['member_wraps' => ['Creator must be included with a wrap.']]);
        }

        return $out;
    }
}
