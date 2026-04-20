<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormPengujian;
use App\Services\GoogleDriveService;

class TestGenerateSpu extends Command
{
    protected $signature = 'google:test-spu {form_id?}';
    protected $description = 'Test SPU generation for a form';

    public function handle()
    {
        $formId = $this->argument('form_id');
        
        if (!$formId) {
            // Get the first form
            $form = FormPengujian::first();
            if (!$form) {
                $this->error('No forms found in database');
                return Command::FAILURE;
            }
        } else {
            $form = FormPengujian::find($formId);
            if (!$form) {
                $this->error("Form with ID {$formId} not found");
                return Command::FAILURE;
            }
        }
        
        $this->info("Testing SPU generation for form: {$form->form_number}");
        
        try {
            $service = new GoogleDriveService();
            $templateId = config('google.spu_template_id');
            
            if (!$templateId) {
                $this->error('Template ID not configured. Add GOOGLE_SPU_TEMPLATE_ID to .env');
                return Command::FAILURE;
            }
            
            $this->info("Using template ID: {$templateId}");
            $this->info("Generating SPU document...");
            
            $documentId = $service->generateSpuDocument($form, $templateId);
            
            // Update form
            $form->update([
                'google_doc_id' => $documentId,
                'spu_generated_at' => now(),
            ]);
            
            $this->newLine();
            $this->info('✅ SPU generated successfully!');
            $this->info("Document ID: {$documentId}");
            $this->info("View: " . $service->getDocumentLink($documentId));
            $this->info("Download PDF: " . $service->getExportLink($documentId));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
