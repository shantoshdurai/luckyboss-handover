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

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'luckyboss-617d2'),
        'project_number' => env('FIREBASE_PROJECT_NUMBER', '655783263537'),
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyAtJpQWHICsqtYljEwFpjweM6AWx-r-w8g'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'luckyboss-617d2.firebaseapp.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'luckyboss-617d2.firebasestorage.app'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '655783263537'),
        'app_id' => env('FIREBASE_APP_ID', '1:655783263537:web:cfe4a6c244d88b7e158410'),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-Q1CP691KL8'),
        'apps' => [
            'employer' => 'com.app.luckybossemployer',
            'seeker' => 'com.userapp.luckyboss',
        ],
    ],


    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],
];
