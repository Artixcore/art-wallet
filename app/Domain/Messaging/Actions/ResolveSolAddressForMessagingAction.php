<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Enums\ContactResolutionStatus;
use App\Domain\Messaging\Services\AddressResolutionAuditLogger;
use App\Domain\Messaging\Services\DirectConversationLookupService;
use App\Domain\Messaging\Services\MessagingDiscoverabilityEvaluator;
use App\Domain\Messaging\Services\SolanaPublicKeyValidator;
use App\Models\MessagingPrivacySetting;
use App\Models\User;
use App\Models\VerifiedWalletAddress;
use InvalidArgumentException;

final class ResolveSolAddressForMessagingAction
{
    public function __construct(
        private readonly SolanaPublicKeyValidator $solana,
        private readonly MessagingDiscoverabilityEvaluator $discoverability,
        private readonly DirectConversationLookupService $directLookup,
        private readonly AddressResolutionAuditLogger $audit,
    ) {}

    /**
     * @return array{
     *   status: ContactResolutionStatus,
     *   sol_address_normalized: string|null,
     *   recipient: array{user_id?: int, display_name?: string, messaging_x25519_public_key?: string}|null,
     *   existing_conversation: array{conversation_id: int, public_id: string|null}|null,
     *   client_behavior: string,
     *   audit_outcome: string
     * }
     */
    public function execute(User $searcher, string $rawSolAddress): array
    {
        try {
            $normalized = $this->solana->validateAndNormalize($rawSolAddress);
        } catch (InvalidArgumentException) {
            return $this->failure(
                ContactResolutionStatus::InvalidAddress,
                null,
                null,
                'invalid_address',
                'none',
            );
        }

        $row = VerifiedWalletAddress::query()
            ->where('chain', 'SOL')
            ->where('address', $normalized)
            ->first();

        if ($row === null) {
            $this->audit->log($searcher, $normalized, 'not_found');

            return $this->failure(
                ContactResolutionStatus::NotFound,
                $normalized,
                null,
                'not_found',
                'show_invite_education',
            );
        }

        $target = User::query()->find($row->user_id);
        if ($target === null) {
            $this->audit->log($searcher, $normalized, 'not_found');

            return $this->failure(
                ContactResolutionStatus::NotFound,
                $normalized,
                null,
                'not_found',
                'show_invite_education',
            );
        }

        if ((int) $target->id === (int) $searcher->id) {
            $this->audit->log($searcher, $normalized, 'self_address');

            return [
                'status' => ContactResolutionStatus::SelfAddress,
                'sol_address_normalized' => $normalized,
                'recipient' => null,
                'existing_conversation' => null,
                'client_behavior' => 'none',
                'audit_outcome' => 'self_address',
            ];
        }

        $privacy = MessagingPrivacySetting::query()->firstOrCreate(
            ['user_id' => $target->id],
            [
                'read_receipts_enabled' => true,
                'typing_indicators_enabled' => true,
                'max_attachment_mb' => 10,
                'safety_warnings_enabled' => true,
                'discoverable_by_sol_address' => 'off',
                'require_dm_approval' => false,
                'hide_profile_until_dm_accepted' => true,
                'settings_version' => 1,
            ],
        );

        if (! $this->discoverability->allowsDiscovery($searcher, $target, $privacy)) {
            $this->audit->log($searcher, $normalized, 'privacy_restricted');

            return $this->failure(
                ContactResolutionStatus::PrivacyRestricted,
                $normalized,
                null,
                'privacy_restricted',
                'none',
            );
        }

        if ($privacy->require_dm_approval) {
            $existing = $this->directLookup->findConversationForPair((int) $searcher->id, (int) $target->id);
            if ($existing === null) {
                $this->audit->log($searcher, $normalized, 'dm_requires_approval');

                return [
                    'status' => ContactResolutionStatus::DmRequiresApproval,
                    'sol_address_normalized' => $normalized,
                    'recipient' => [
                        'user_id' => (int) $target->id,
                    ],
                    'existing_conversation' => null,
                    'client_behavior' => 'none',
                    'audit_outcome' => 'dm_requires_approval',
                ];
            }
        }

        if ($target->messaging_x25519_public_key === null || $target->messaging_x25519_public_key === '') {
            $this->audit->log($searcher, $normalized, 'messaging_key_required');

            return [
                'status' => ContactResolutionStatus::MessagingKeyRequired,
                'sol_address_normalized' => $normalized,
                'recipient' => [
                    'user_id' => (int) $target->id,
                ],
                'existing_conversation' => null,
                'client_behavior' => 'rekey',
                'audit_outcome' => 'messaging_key_required',
            ];
        }

        $existingConv = $this->directLookup->findConversationForPair((int) $searcher->id, (int) $target->id);

        $showName = ! $privacy->hide_profile_until_dm_accepted;

        $recipient = [
            'user_id' => (int) $target->id,
            'messaging_x25519_public_key' => (string) $target->messaging_x25519_public_key,
        ];
        if ($showName) {
            $recipient['display_name'] = (string) $target->name;
        }

        $this->audit->log($searcher, $normalized, 'resolved');

        return [
            'status' => ContactResolutionStatus::ResolvedArtwalletUser,
            'sol_address_normalized' => $normalized,
            'recipient' => $recipient,
            'existing_conversation' => $existingConv !== null ? [
                'conversation_id' => (int) $existingConv->id,
                'public_id' => $existingConv->public_id !== null ? (string) $existingConv->public_id : null,
            ] : null,
            'client_behavior' => $existingConv !== null ? 'open_thread' : 'create_conversation',
            'audit_outcome' => 'resolved',
        ];
    }

    /**
     * @return array{
     *   status: ContactResolutionStatus,
     *   sol_address_normalized: string|null,
     *   recipient: array{user_id?: int, display_name?: string, messaging_x25519_public_key?: string}|null,
     *   existing_conversation: array{conversation_id: int, public_id: string|null}|null,
     *   client_behavior: string,
     *   audit_outcome: string
     * }
     */
    private function failure(
        ContactResolutionStatus $status,
        ?string $normalized,
        ?array $recipient,
        string $auditOutcome,
        string $clientBehavior,
    ): array {
        return [
            'status' => $status,
            'sol_address_normalized' => $normalized,
            'recipient' => $recipient,
            'existing_conversation' => null,
            'client_behavior' => $clientBehavior,
            'audit_outcome' => $auditOutcome,
        ];
    }
}
