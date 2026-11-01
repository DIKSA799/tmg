<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'geocoding' => [
        'enabled' => env('GEOCODING_ENABLED', true),
        'endpoint' => env('GEOCODING_ENDPOINT', 'https://api.bigdatacloud.net/data/reverse-geocode-client'),
        'timeout' => (int) env('GEOCODING_TIMEOUT', 5),
        'ttl' => (int) env('GEOCODING_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Termii (SMS)
    |--------------------------------------------------------------------------
    |
    | Credentials for the Termii messaging API. The sender ID must be an
    | alphanumeric ID approved on the Termii account (3-11 characters), and the
    | base URL is the account-specific host shown on the Termii dashboard.
    |
    */

    'termii' => [
        'enabled' => (bool) env('TERMII_ENABLED', true),
        'key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'TMG'),
        'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        'channel' => env('TERMII_CHANNEL', 'generic'),
        'type' => env('TERMII_TYPE', 'plain'),
        'timeout' => (int) env('TERMII_TIMEOUT', 10),
        'batch_size' => (int) env('TERMII_BATCH_SIZE', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sendchamp (SMS)
    |--------------------------------------------------------------------------
    |
    | Credentials for the Sendchamp messaging API. `route` selects the delivery
    | route: dnd (transactional), non_dnd (promotional) or international.
    |
    */

    'sendchamp' => [
        'enabled' => (bool) env('SENDCHAMP_ENABLED', true),
        'key' => env('SENDCHAMP_API_KEY'),
        'sender_id' => env('SENDCHAMP_SENDER_ID', 'Sendchamp'),
        'base_url' => env('SENDCHAMP_BASE_URL', 'https://api.sendchamp.com/api/v1'),
        'route' => env('SENDCHAMP_ROUTE', 'non_dnd'),
        'timeout' => (int) env('SENDCHAMP_TIMEOUT', 10),
        'batch_size' => (int) env('SENDCHAMP_BATCH_SIZE', 25),
    ],

];
