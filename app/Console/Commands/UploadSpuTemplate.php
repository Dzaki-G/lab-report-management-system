<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Drive;

class UploadSpuTemplate extends Command
{
    protected $signature = 'google:upload-template {file}';
    protected $description = 'Upload SPU template to Google Drive and return Document ID';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Uploading template to Google Drive...');
        
        try {
            $client = new Client();
            $client->setAuthConfig(base_path(config('google.service_account_path')));
            $client->setScopes([Drive::DRIVE]);
            
            // Workaround for SSL certificate issues on Windows (development only)
            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
            ]);
            $client->setHttpClient($httpClient);
            
            $driveService = new Drive($client);
            $folderId = config('google.drive_folder_id');
            
            // Create file metadata
            $fileMetadata = new Drive\DriveFile([
                'name' => 'SPU-Template',
                'mimeType' => 'application/vnd.google-apps.document', // Convert to Google Docs
                'parents' => [$folderId],
            ]);
            
            // Upload with conversion to Google Docs
            $content = file_get_contents($filePath);
            $file = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'uploadType' => 'multipart',
                'fields' => 'id, name, webViewLink',
            ]);
            
            $this->newLine();
            $this->info('✅ Template uploaded successfully!');
            $this->newLine();
            $this->info('Template ID: ' . $file->id);
            $this->info('View Link: ' . $file->webViewLink);
            $this->newLine();
            $this->warn('⚠️  IMPORTANT: Add placeholders to the template!');
            $this->warn('Open the Google Docs link above and add these placeholders:');
            $this->line('  {{NO_SPU}} - untuk nomor SPU');
            $this->line('  {{NO_TERIMA_SAMPEL}} - untuk nomor terima sampel');
            $this->line('  {{PERIHAL}} - untuk perihal analisis');
            $this->line('  {{TANGGAL}} - untuk tanggal dokumen');
            $this->newLine();
            $this->warn("After adding placeholders, add this to your .env file:");
            $this->line("GOOGLE_SPU_TEMPLATE_ID=\"{$file->id}\"");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to upload: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
