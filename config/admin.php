<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator (local / first deploy)
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env
    | before running php artisan db:seed. Change the password after first login
    | in production.
    |
    */

    'name' => env('ADMIN_NAME', 'Administrator'),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
