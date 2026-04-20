<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDocsService;
use App\Models\FormPengujian;

class TestGoogleDocs extends Command
{
    protected $signature = 'test:google-docs {--form= : Form ID to test with}';
    protected $description = 'Test Google Docs connection and document generation';

    public function handle()
    {
        $this->info('Testing Google Docs API connection...');
        
        try {
            $service = new GoogleDocsService();
            $this->info('✓ GoogleDocsService initialized successfully');
            
            // Test 1: List all files in templates folder
            $this->info('');
            $templatesFolderId = config('services.google.templates_folder_id');
            $this->info("Templates Folder ID: {$templatesFolderId}");
            $this->info('Files in Templates folder:');
            $files = $service->listTemplatesFolder();
            if (empty($files)) {
                $this->warn('  No files found in Templates folder');
            } else {
                foreach ($files as $file) {
                    $this->info("  - {$file['name']} (ID: {$file['id']})");
                }
            }
            
            // Test 2: Check configured templates
            $this->info('');
            $this->info('Checking configured templates...');
            
            $templates = [
                'Unsigned SPU' => config('services.google.spu_unsigned_template_id'),
                'Signed SPU' => config('services.google.spu_signed_template_id'),
                'SP3 Template' => config('services.google.sp3_template_id'),
            ];
            
            foreach ($templates as $name => $id) {
                if ($id) {
                    // Try to get file metadata to verify access
                    try {
                        $file = $service->getFileMetadata($id);
                        $this->info("✓ {$name}: Accessible (ID: {$id}) - {$file->getName()}");
                    } catch (\Exception $e) {
                        $this->error("✗ {$name}: Inaccessible (ID: {$id})");
                        $this->line("  Error: " . $e->getMessage());
                    }
                } else {
                    $this->warn("✗ {$name}: Not configured in .env");
                }
            }
            
            // Test 2: Generate SPU if form ID provided
            $formId = $this->option('form');
            if ($formId) {
                $this->info('');
                $this->info("Generating SPU for form ID: {$formId}...");
                
                $form = FormPengujian::find($formId);
                if (!$form) {
                    $this->error("Form not found with ID: {$formId}");
                    return 1;
                }
                
                $result = $service->generateSpuWithTable($form);
                $this->info("✓ SPU Generated!");
                $this->info("  Document ID: {$result['id']}");
                $this->info("  URL: {$result['url']}");
                
                // Save to form
                $form->update(['spu_unsigned_doc_id' => $result['id']]);
                $this->info("✓ Document ID saved to form");
            } else {
                $this->info('');
                $this->info('Tip: Run with --form=ID to test document generation');
            }
            
            $this->info('');
            $this->info('Google Docs API test completed!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('');
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
