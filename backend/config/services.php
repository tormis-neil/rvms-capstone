<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Firebase Cloud Messaging (FR-21)
     |--------------------------------------------------------------------------
     | Server-side push via Google's HTTP v1 API. When either value is missing
     | the app falls back to the log transport, so every notification trigger
     | still works locally without Firebase credentials.
     |
     | FIREBASE_CREDENTIALS is a path to the service-account JSON. Keep it in
     | storage/app (already gitignored) — it is a private key.
     |
     | FIREBASE_CA_BUNDLE is optional and only needed on a machine whose PHP
     | has no certificate store of its own — the usual XAMPP-on-Windows case,
     | where every HTTPS call to Google fails with "cURL error 60: SSL
     | certificate problem". Point it at a cacert.pem. Leave it blank on a
     | properly configured server and PHP's own store is used.
     */
    'fcm' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'ca_bundle' => env('FIREBASE_CA_BUNDLE'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
