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
    'meta' => [
        'app_id'       => env('META_APP_ID'),
        'app_secret'   => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'api_version'  => env('META_API_VERSION', 'v19.0'),
        'mock_mode'    => env('META_MOCK_MODE', true),
    ],

    'n8n' => [
        'webhook_create'   => env('N8N_WEBHOOK_CREATE'),
        'webhook_activate' => env('N8N_WEBHOOK_ACTIVATE'),
        'webhook_pause'    => env('N8N_WEBHOOK_PAUSE'),
        'secret'           => env('N8N_WEBHOOK_SECRET', 'changeme'),
        'timeout'          => env('N8N_TIMEOUT', 10), // secondes
        'mock_mode'        => env('N8N_MOCK_MODE', true), // true = simule N8N sans appel réel
    ],

];
