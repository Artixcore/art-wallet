<?php

return [
    'tx' => [
        'broadcast_success' => [
            'title' => 'Transaction sent',
            'body' => 'Broadcast submitted. TxID: :txid',
        ],
        'broadcast_failed' => [
            'title' => 'Broadcast failed',
            'body' => 'The network could not accept this transaction. Verify the details and try again.',
        ],
    ],
    'recovery_kit' => [
        'saved' => [
            'title' => 'Recovery kit saved',
            'body' => 'Your encrypted recovery kit has been updated.',
        ],
    ],
    'security' => [
        'generic' => [
            'title' => 'Security notice',
            'body' => 'Review your security settings if you did not perform this action.',
        ],
    ],
    'settings' => [
        'security_policy_relaxed' => [
            'title' => 'Session policy changed',
            'body' => 'Your session or notification policy was relaxed. If this was not you, secure your account immediately.',
        ],
        'transaction_policy_relaxed' => [
            'title' => 'Transaction confirmation policy changed',
            'body' => 'Confirmation rules for wallet :wallet_id were relaxed. Review that wallet if you did not make this change.',
            'body_fallback' => 'Transaction confirmation rules were relaxed. Review your wallet settings if you did not make this change.',
        ],
        'messaging_privacy_weakened' => [
            'title' => 'Messaging safety warnings disabled',
            'body' => 'Some safety prompts were turned off. Phishing and risky attachments are easier to miss.',
        ],
        'risk_threshold_relaxed' => [
            'title' => 'Risk alert threshold raised',
            'body' => 'Large transaction alerts will trigger less often. Verify this matches your intent.',
        ],
    ],
    'messaging' => [
        'new_message' => [
            'title' => 'New encrypted message',
            'body' => 'You have a new message in conversation :conversation_public_id.',
            'body_generic' => 'You have a new encrypted message.',
        ],
        'send_failed' => [
            'title' => 'Message could not be sent securely',
            'body' => 'The server did not accept your encrypted message. Nothing was delivered as a successful send.',
        ],
    ],
    'fallback' => [
        'title' => 'Notice',
    ],
];
