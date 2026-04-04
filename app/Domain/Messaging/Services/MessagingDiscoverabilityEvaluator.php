<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\MessagingContactPair;
use App\Models\MessagingPrivacySetting;
use App\Models\User;

final class MessagingDiscoverabilityEvaluator
{
    public function __construct(
        private readonly DirectConversationLookupService $pairHelper,
    ) {}

    /**
     * Whether $target can be discovered by $searcher via verified Sol address lookup.
     */
    public function allowsDiscovery(User $searcher, User $target, MessagingPrivacySetting $privacy): bool
    {
        $mode = (string) $privacy->discoverable_by_sol_address;

        if ($mode === 'off') {
            return false;
        }

        if ($mode === 'all_verified_users') {
            return true;
        }

        if ($mode === 'contacts_only') {
            [$low, $high] = $this->pairHelper->orderedPair((int) $searcher->id, (int) $target->id);

            return MessagingContactPair::query()
                ->where('user_low_id', $low)
                ->where('user_high_id', $high)
                ->exists();
        }

        return false;
    }
}
