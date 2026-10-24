<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Senatorial districts
    |--------------------------------------------------------------------------
    |
    | The compact, committed dataset used by the seeder. It is generated from
    | Nigeria_polling_units.csv (774 LGAs -> 109 senatorial districts).
    |
    */

    'districts' => [
        'data' => database_path('data/senatorial_districts.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambassador accounts
    |--------------------------------------------------------------------------
    |
    | The TMG number (voter record reference) is the login password. It is
    | normalised to uppercase before hashing so sign-in is case-insensitive.
    |
    */

    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],

    'photo' => [
        'disk' => 'public',
        'size' => 600,
        'quality' => 82,
        'max_kilobytes' => 5120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Leaderboard
    |--------------------------------------------------------------------------
    */

    'leaderboard' => [
        'cache_seconds' => 10,
        'state_limit' => 5,
        'zone_limit' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default content (overridden by admin settings when present)
    |--------------------------------------------------------------------------
    */

    'terms' => [
        'title' => 'Nomination and voting terms',
        'body' => 'I confirm that I am a registered TMG Ambassador in this senatorial zone. I accept that I may nominate only myself, may cast only one vote in this zone, and may not trade, sell, or solicit votes for money or favour. I understand that nominations and votes are recorded against my account, that duplicate votes are rejected automatically, and that any attempt to manipulate the leaderboard may lead to my removal from the platform.',
    ],

    'notice' => [
        'enabled' => false,
        'title' => 'Information notice',
        'body' => 'Your registration details are processed to verify your identity, place you in the correct senatorial zone, and operate the TMG Ambassador nomination and voting process. Your name, polling unit, and photograph (where provided) are visible to other ambassadors in your zone. Your phone number and email address are never shown in full. Contact the TMG privacy desk for access, correction, or deletion requests.',
    ],

];
