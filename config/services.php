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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/client/login/google/callback' : null),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/client/login/facebook/callback' : null),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/client/login/apple/callback' : null),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'project' => env('GEMINI_PROJECT'),
        'project_number' => env('GEMINI_PROJECT_NUMBER'),
        'location' => env('GEMINI_LOCATION', 'us-central1'),
        'credentials_path' => env('GEMINI_CREDENTIALS_PATH', env('GOOGLE_APPLICATION_CREDENTIALS')),
        'model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash'),
        'image_fallback_models' => env('GEMINI_IMAGE_FALLBACK_MODELS', 'gemini-2.0-flash-preview-image-generation,gemini-2.0-flash-exp-image-generation'),
        'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),
    ],

    'image_storage' => [
        'provider' => env('IMAGE_STORAGE_PROVIDER', env('GOOGLE_DRIVE_FOLDER_ID') ? 'google_drive' : 'local'), // local | google_drive
        'local_disk' => env('IMAGE_STORAGE_LOCAL_DISK', 'public'),
        'google_drive_auth_mode' => env('GOOGLE_DRIVE_AUTH_MODE', 'auto'), // auto | oauth | service_account
        'google_drive_credentials' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON_PATH', storage_path('app/credentials.json'))),
        'service_account_json' => base_path(env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON_PATH')),
        'google_drive_oauth_client_id' => env('GOOGLE_DRIVE_OAUTH_CLIENT_ID', env('GOOGLE_DRIVE_CLIENT_ID', '')),
        'google_drive_oauth_client_secret' => env('GOOGLE_DRIVE_OAUTH_CLIENT_SECRET', ''),
        'google_drive_oauth_refresh_token' => env('GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN', ''),
        'google_drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
        'google_drive_fallback_to_local_on_error' => env('GOOGLE_DRIVE_FALLBACK_TO_LOCAL_ON_ERROR', true),
        'google_drive_http_timeout' => (int) env('GOOGLE_DRIVE_HTTP_TIMEOUT', 20),
        'google_drive_connect_timeout' => (int) env('GOOGLE_DRIVE_CONNECT_TIMEOUT', 8),
    ],

    'thefactory_hka' => [
        'base_url' => env('TFHKA_BASE_URL', 'https://demoemisionv2.thefactoryhka.com.ve'),
        'username' => env('TFHKA_USER', env('TFHKA_TOKEN_USER', '')),
        'password' => env('TFHKA_PASSWORD', env('TFHKA_TOKEN_PASSWORD', '')),
        'default_serie' => env('TFHKA_SERIE', ''),
        'default_document_type' => env('TFHKA_DOCUMENT_TYPE', '01'),
        'default_sale_type' => env('TFHKA_SALE_TYPE', 'Interna'),
        'default_payment_type' => env('TFHKA_PAYMENT_TYPE', 'Inmediato'),
        'default_branch' => env('TFHKA_BRANCH', '0001'),
        'default_currency' => env('TFHKA_CURRENCY', 'BSD'),
        'default_foreign_currency' => env('TFHKA_FOREIGN_CURRENCY', 'USD'),
        'default_exchange_rate' => (float) env('TFHKA_EXCHANGE_RATE', 1),
        'auto_next_number' => env('TFHKA_AUTO_NEXT_NUMBER', true),
        'signature_secret' => env('TFHKA_SIGNATURE_SECRET', env('APP_KEY', '')),
        'enforce_signature_validation' => env('TFHKA_ENFORCE_SIGNATURE_VALIDATION', true),
        'timeout' => (int) env('TFHKA_TIMEOUT', 25),
        'verify_ssl' => env('TFHKA_VERIFY_SSL', true),
    ],

];
