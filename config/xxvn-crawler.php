<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the xxvnapi.com API
    |
    */
    'api' => [
        'base_url' => 'https://xxvnapi.com/api',
        'page_from' => 3,
        'page_to' => 1,
        'delay' => 1, // Delay in seconds between API requests
        'timeout' => 60, // Timeout for API requests in seconds
        'retries' => 3, // Number of retries on failure
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for image processing
    |
    */
    'images' => [
        'max_dimensions' => 500, // Maximum width/height for images
        'quality' => 85, // JPEG quality (0-100)
        'storage_path' => 'public', // Storage disk
        'save_directory' => 'movies', // Directory within storage
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
    ],
];
