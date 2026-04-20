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

    'google' => [
        // OAuth keys
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
        
        // Legacy (Service Account) - kept for reference or fallback
        'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH'),
        
        // Folders
        'templates_folder_id' => env('GOOGLE_TEMPLATES_FOLDER_ID'),
        'spu_folder_id' => env('GOOGLE_SPU_FOLDER_ID'),
        'sp3_folder_id' => env('GOOGLE_SP3_FOLDER_ID'),
        'lcp_folder_id' => env('GOOGLE_LCP_FOLDER_ID'),
        'lhp_folder_id' => env('GOOGLE_LHP_FOLDER_ID'),
        
        // Direct template IDs (fallback)
        'spu_unsigned_template_id' => env('GOOGLE_SPU_UNSIGNED_TEMPLATE_ID'),
        'spu_signed_template_id' => env('GOOGLE_SPU_SIGNED_TEMPLATE_ID'),
        'spu_signed_full_template_id' => env('GOOGLE_SPU_SIGNED_FULL_TEMPLATE_ID'),
        'sp3_template_id' => env('GOOGLE_SP3_TEMPLATE_ID'),
        'sp3_signed_template_id' => env('GOOGLE_SP3_SIGNED_TEMPLATE_ID'),
    ],

];
