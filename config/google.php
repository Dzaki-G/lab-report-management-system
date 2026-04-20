<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Service Account Path
    |--------------------------------------------------------------------------
    |
    | Path to the Google Service Account JSON credentials file.
    |
    */
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', 'storage/app/google/credentials.json'),

    /*
    |--------------------------------------------------------------------------
    | Google Drive Folder ID
    |--------------------------------------------------------------------------
    |
    | The folder ID where generated documents will be stored.
    |
    */
    'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | SPU Template ID
    |--------------------------------------------------------------------------
    |
    | The Google Docs document ID to use as SPU template.
    |
    */
    'spu_template_id' => env('GOOGLE_SPU_TEMPLATE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    |
    | Default document metadata settings.
    |
    */
    'document' => [
        'no_dokumen' => 'BO 01/IK 7.4.1/01',
        'edisi' => '1',
        'no_revisi' => '1',
        'tanggal_terbit' => '15 April 2019',
    ],
];
