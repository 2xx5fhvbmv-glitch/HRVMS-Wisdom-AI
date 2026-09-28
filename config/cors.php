<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Mobile app doesn't need CORS (Passport bearer tokens, not a browser
    // caller). Only add explicit https origins here, never '*', never with
    // supports_credentials=true (S5-01) — this was previously live with a
    // wildcard origin on api/* and the unused sanctum/csrf-cookie path
    // (Sanctum's frontend-stateful middleware is commented out in Kernel.php,
    // so nothing legitimately depends on it).
    'paths' => [],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
