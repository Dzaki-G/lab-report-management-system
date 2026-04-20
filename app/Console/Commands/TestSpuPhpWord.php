<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormPengujian;
use App\Services\SpuDocumentService;

class TestSpuPhpWord extends Command
{
    protected $signature = 'spu:test {form_id?}';
    protected $description = 'Test SPU document generation with PhpWord';

    public function handle()
    {
        $formId = $this->argument('form_id');
        
        if (!$formId) {
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
            $service = new SpuDocumentService();
            
            $this->info("Generating SPU document...");
            $filename = $service->generate($form);
            
            // Update form
            $form->update([
                'spu_file_path' => $filename,
                'spu_generated_at' => now(),
            ]);
            
            $this->newLine();
            $this->info('✅ SPU generated successfully!');
            $this->info("Filename: {$filename}");
            $this->info("Full path: " . $service->getFilePath($filename));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
