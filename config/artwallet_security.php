<?php

return [

    'challenge_ttl_seconds' => (int) env('ARTWALLET_CHALLENGE_TTL', 600),

    /**
     * Extra entropy for session binding hashes (optional). Falls back to APP_KEY when empty.
     */
    'session_binding_pepper' => env('ARTWALLET_SESSION_PEPPER', ''),

];
