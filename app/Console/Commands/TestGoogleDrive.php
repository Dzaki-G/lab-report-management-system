<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;

class TestGoogleDrive extends Command
{
    protected $signature = 'google:test';
    protected $description = 'Test Google Drive API connection';

    public function handle()
    {
        $this->info('Testing Google Drive connection...');
        
        try {
            $service = new GoogleDriveService();
            
            $this->info('✅ Successfully connected to Google Drive API');
            $this->info('Folder ID: ' . config('google.drive_folder_id'));
            
            // Try to list files in the folder
            $this->info('Listing files in folder...');
            $files = $service->listDocuments();
            
            if (empty($files)) {
                $this->info('📁 Folder is empty (this is expected for a new setup)');
            } else {
                $this->info("📁 Found " . count($files) . " file(s):");
                foreach ($files as $file) {
                    $this->line("  - {$file->name} (ID: {$file->id})");
                }
            }
            
            $this->newLine();
            $this->info('🎉 Google Drive integration is working!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to connect: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Please check:');
            $this->warn('1. Credentials file exists at: ' . base_path(config('google.service_account_path')));
            $this->warn('2. Google Drive API is enabled in Google Cloud Console');
            $this->warn('3. Folder is shared with the service account email');
            
            return Command::FAILURE;
        }
    }
}
