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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'ai' => [
        'url' => env('AI_SERVICE_URL'),
        'ws_url' => env('AI_WS_URL'),
        'ws_token_secret' => env('AI_WS_TOKEN_SECRET'),
        'ws_token_ttl' => env('AI_WS_TOKEN_TTL', 120),
        'internal_key' => env('AI_INTERNAL_KEY', 'default_internal_key'),
        'stream' => env('AI_STREAM_NAME', 'ai:jobs'),
        'group' => env('AI_WORKER_GROUP', 'default'),
    ],


];
