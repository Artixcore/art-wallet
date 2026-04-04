<?php

declare(strict_types=1);

return [

    'access_token_ttl_minutes' => (int) env('ARTWALLET_API_ACCESS_TTL', 45),

    'refresh_token_ttl_days' => (int) env('ARTWALLET_API_REFRESH_TTL_DAYS', 30),

];
