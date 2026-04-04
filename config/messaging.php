<?php

return [

    'max_ciphertext_bytes' => (int) env('MESSAGING_MAX_CIPHERTEXT_BYTES', 524_288),

    'max_attachment_bytes' => (int) env('MESSAGING_MAX_ATTACHMENT_BYTES', 25 * 1024 * 1024),

    /**
     * MIME hints the client may send for encrypted attachments (server cannot verify plaintext type).
     *
     * @var list<string>
     */
    'attachment_mime_hints_allowed' => [
        'application/octet-stream',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ],

    'attachment_manifest_version' => '1',

    'security_event_rate_per_minute' => (int) env('MESSAGING_SECURITY_EVENT_RATE', 30),
];
