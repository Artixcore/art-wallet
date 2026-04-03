<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authorization driver (preservation / cutover)
    |--------------------------------------------------------------------------
    |
    | artgate — use the container's PermissionResolverInterface as registered
    |           by ArtGate (default).
    |
    | compare — wrap the resolver to compare the full ArtGate stack with a
    |           baseline DatabasePermissionResolver; log mismatches; always
    |           return the trusted (decorated) decision. Use on staging only.
    |
    | legacy — alias for artgate when no separate legacy stack exists.
    |
    */

    'driver' => env('RBAC_DRIVER', 'artgate'),

];
