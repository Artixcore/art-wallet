<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Use Vite dev server (public/hot)
    |--------------------------------------------------------------------------
    |
    | When true, asset URLs follow `public/hot` (created by `npm run dev`).
    | When false, Laravel always uses the manifest under public/build — even
    | if a stale `public/hot` file is left on disk from an old dev session.
    | Set to true only while `npm run dev` is running.
    |
    */

    'use_hot_file' => env('VITE_USE_HOT', false),

];
