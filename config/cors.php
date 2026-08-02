<?php

/**
 * CORS Configuration
 *
 * The frontend is served same-origin by Laravel, but these settings keep
 * cross-origin access working when the API is called from other origins.
 * In production, restrict 'allowed_origins' to your actual domain.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development — allow any localhost origin
        'http://localhost:3000',
        'http://localhost:5500',
        'http://localhost:5501',
        'http://127.0.0.1:5500',
        'http://127.0.0.1:5501',
        'http://127.0.0.1:3000',
        // Add your production domain here:
        // 'https://smartattend.sumas.edu.ng',
    ],

    'allowed_origins_patterns' => [
        // Allow all localhost ports during development
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
