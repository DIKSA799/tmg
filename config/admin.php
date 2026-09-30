<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Console Path
    |--------------------------------------------------------------------------
    |
    | The console is mounted on an unguessable path instead of a predictable
    | "/admin" segment. Change ADMIN_PATH in the environment to rotate it.
    | The path is never linked from the public site.
    |
    */

    'path' => trim((string) env('ADMIN_PATH', 'ops-7f3a9c2e'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Seeded Administrator
    |--------------------------------------------------------------------------
    |
    | Credentials used by the AdminSeeder to provision the first operator.
    |
    */

    'user' => [
        'name' => env('ADMIN_NAME', 'TMG Administrator'),
        'username' => env('ADMIN_USERNAME', 'tmgadmin'),
        'email' => env('ADMIN_EMAIL', 'admin@tmg.local'),
        'password' => env('ADMIN_PASSWORD', 'TmgConsole!2026'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling
    |--------------------------------------------------------------------------
    |
    | "attempts,minutes" applied to the console login and console pages.
    |
    */

    'throttle' => [
        'login' => (string) env('ADMIN_LOGIN_THROTTLE', '5,1'),
        'pages' => (string) env('ADMIN_THROTTLE', '240,1'),
    ],

];
