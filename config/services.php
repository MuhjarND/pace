<?php

return [
    'travel' => [
        'routing_url' => env('TRAVEL_ROUTING_URL', 'https://router.project-osrm.org'),
        'routing_profile' => env('TRAVEL_ROUTING_PROFILE', 'driving'),
    ],
    'kasuari' => [
        'case_status_url' => env('KASUARI_CASE_STATUS_URL'),
        'token' => env('KASUARI_CASE_STATUS_TOKEN'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'enabled' => env('FONNTE_ENABLED', false),
        'public_url' => env('PTSP_PUBLIC_URL'),
        'webhook_secret' => env('FONNTE_WEBHOOK_SECRET'),
    ],

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

];
