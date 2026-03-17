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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'project' => env('GEMINI_PROJECT'),
        'project_number' => env('GEMINI_PROJECT_NUMBER'),
        'model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash-image-preview'),
        'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),
    ],

    'image_storage' => [
        'provider' => env('IMAGE_STORAGE_PROVIDER', 'local'), // local | google_drive
        'local_disk' => env('IMAGE_STORAGE_LOCAL_DISK', 'public'),
        'google_drive_credentials' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', storage_path('app/credentials.json')),
        'google_drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
    ],

];
