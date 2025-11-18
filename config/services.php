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

    'esewa' => [
        'merchant_id' => env('ESEWA_MERCHANT_ID', 'EPAYTEST'),
        'secret_key' => env('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'),
        'api_url' => env('ESEWA_API_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, vonage, http
        'enabled' => env('SMS_ENABLED', true),
        'api_key' => env('SMS_API_KEY'),
        'api_secret' => env('SMS_API_SECRET'),
        'from' => env('SMS_FROM'),
        'base_url' => env('SMS_BASE_URL'),
    ],

];
