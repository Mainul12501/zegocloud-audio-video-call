<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ZegoCloud Credentials
    |--------------------------------------------------------------------------
    |
    | Your ZegoCloud application credentials. You can obtain these from
    | your ZegoCloud console at https://console.zegocloud.com
    |
    */

    'zegocloud' => [
        'app_id' => env('ZEGOCLOUD_APP_ID'),
        'server_secret' => env('ZEGOCLOUD_SERVER_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM) and Apple Push
    | Notification service (APNs) for mobile app integration.
    |
    */

    'push_notifications' => [
        'enabled' => env('ZEGO_PUSH_NOTIFICATIONS_ENABLED', true),

        'fcm' => [
            'server_key' => env('FCM_SERVER_KEY'),
        ],

        'apn' => [
            'key_id' => env('APN_KEY_ID'),
            'team_id' => env('APN_TEAM_ID'),
            'bundle_id' => env('APN_BUNDLE_ID'),
            'key_path' => env('APN_KEY_PATH'),
            'production' => env('APN_PRODUCTION', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes for the calling system.
    |
    */

    'routes' => [
        'web' => [
            'enabled' => true,
            'prefix' => 'call',
            'middleware' => ['web', 'auth'],
        ],

        'api' => [
            'enabled' => true,
            'prefix' => 'api/call',
            'middleware' => ['api', 'auth:sanctum'],
        ],

        'mobile' => [
            'enabled' => true,
            'prefix' => 'api/mobile/call',
            'middleware' => ['api', 'auth:sanctum'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database tables and relationships.
    |
    */

    'database' => [
        'calls_table' => 'calls',
        'users_table' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The User model that will be used for authentication and relationships.
    |
    */

    'user_model' => env('ZEGO_USER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time broadcasting events.
    |
    */

    'broadcasting' => [
        'enabled' => env('ZEGO_BROADCASTING_ENABLED', true),
        'driver' => env('BROADCAST_DRIVER', 'pusher'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Call Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for calls.
    |
    */

    'call_settings' => [
        'max_call_duration' => env('ZEGO_MAX_CALL_DURATION', 3600), // in seconds
        'auto_end_missed_calls' => env('ZEGO_AUTO_END_MISSED_CALLS', true),
        'missed_call_timeout' => env('ZEGO_MISSED_CALL_TIMEOUT', 60), // in seconds
        'enable_call_history' => env('ZEGO_ENABLE_CALL_HISTORY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the UI components.
    |
    */

    'ui' => [
        'theme' => env('ZEGO_UI_THEME', 'default'), // default, dark, light
        'call_button_position' => env('ZEGO_CALL_BUTTON_POSITION', 'right'), // left, right
        'show_profile_photos' => env('ZEGO_SHOW_PROFILE_PHOTOS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for the calling system.
    |
    */

    'security' => [
        'enable_encryption' => env('ZEGO_ENABLE_ENCRYPTION', true),
        'allowed_domains' => env('ZEGO_ALLOWED_DOMAINS', '*'),
    ],

];
