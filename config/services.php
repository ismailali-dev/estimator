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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'revenuecat' => [
        'webhook_secret' => env('REVENUECAT_WEBHOOK_SECRET'),
    ],

    'ezsubcontractor' => [
        'publish_url' => env('EZSUBCONTRACTOR_PUBLISH_URL'),
        'signup_url' => env('EZSUBCONTRACTOR_SIGNUP_URL'),
        'status_url' => env('EZSUBCONTRACTOR_STATUS_URL'),
        'sync_url' => env('EZSUBCONTRACTOR_SYNC_URL'),
        'desync_url' => env('EZSUBCONTRACTOR_DESYNC_URL'),
        'default_password' => env('EZSUBCONTRACTOR_DEFAULT_PASSWORD'),
        'token' => env('EZSUBCONTRACTOR_API_TOKEN'),
        'timeout' => env('EZSUBCONTRACTOR_TIMEOUT', 20),
    ],

];
