<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Messaging\Actions\ResolveSolAddressForMessagingAction;
use App\Domain\Messaging\Enums\ContactResolutionStatus;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\ResolveSolAddressRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use Illuminate\Http\JsonResponse;

class MessagingSolAddressAjaxController extends Controller
{
    public function resolve(ResolveSolAddressRequest $request, ResolveSolAddressForMessagingAction $action): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return AjaxEnvelope::error(
                AjaxResponseCode::Unauthorized,
                __('Unauthorized.'),
                NotificationSeverity::Danger,
            )->toJsonResponse(401);
        }

        $result = $action->execute($user, (string) $request->input('sol_address'));

        return $this->toEnvelope($result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function toEnvelope(array $result): JsonResponse
    {
        /** @var ContactResolutionStatus $status */
        $status = $result['status'];

        $baseData = [
            'contact_resolution_status' => $status->value,
            'sol_address_normalized' => $result['sol_address_normalized'],
            'recipient' => $result['recipient'],
            'existing_conversation' => $result['existing_conversation'],
            'client_behavior' => $result['client_behavior'],
        ];

        $meta = [
            'retryable' => false,
            'blind_denial' => false,
        ];

        return match ($status) {
            ContactResolutionStatus::ResolvedArtwalletUser => AjaxEnvelope::ok(
                __('We found an ArtWallet contact for this address.'),
                data: $baseData,
                meta: $meta,
            )->toJsonResponse(),
            ContactResolutionStatus::InvalidAddress => AjaxEnvelope::error(
                AjaxResponseCode::SolAddressInvalid,
                __('This does not look like a valid Solana address.'),
                NotificationSeverity::Warning,
                meta: array_merge($meta, ['client_behavior' => 'fix_input']),
            )->toJsonResponse(422),
            ContactResolutionStatus::SelfAddress => AjaxEnvelope::error(
                AjaxResponseCode::MessagingSelfConversation,
                __('You cannot start a conversation with your own Solana address.'),
                NotificationSeverity::Warning,
                meta: $meta,
            )->toJsonResponse(422),
            ContactResolutionStatus::NotFound,
            ContactResolutionStatus::PrivacyRestricted => AjaxEnvelope::ok(
                __('We could not find an ArtWallet user linked to this verified address, or discovery is restricted.'),
                data: array_merge($baseData, [
                    'contact_resolution_status' => ContactResolutionStatus::NotFound->value,
                ]),
                meta: array_merge($meta, ['blind_denial' => true]),
            )->toJsonResponse(),
            ContactResolutionStatus::MessagingKeyRequired => AjaxEnvelope::error(
                AjaxResponseCode::MessagingKeyRequired,
                __('This user has not registered a messaging identity key yet.'),
                NotificationSeverity::Warning,
                meta: array_merge($meta, ['client_behavior' => 'peer_rekey']),
                data: $baseData,
            )->toJsonResponse(422),
            ContactResolutionStatus::DmRequiresApproval => AjaxEnvelope::error(
                AjaxResponseCode::MessagingDmRequiresApproval,
                __('This user requires approval before new direct messages.'),
                NotificationSeverity::Warning,
                meta: $meta,
                data: $baseData,
            )->toJsonResponse(422),
        };
    }
}
