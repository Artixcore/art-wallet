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
    'fallback' => [
        'title' => 'Notice',
    ],
];
