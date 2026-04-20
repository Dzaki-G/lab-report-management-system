<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\FormPengujian;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpuDocumentService
{
    protected string $templatePath;
    protected string $outputPath;

    public function __construct()
    {
        $this->templatePath = base_path('doc/templates/SPU-001.docx');
        $this->outputPath = storage_path('app/spu-documents');
        
        // Create output directory if not exists
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Generate SPU document from template
     */
    public function generate(FormPengujian $form): string
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        try {
            $templateProcessor = new TemplateProcessor($this->templatePath);
            
            // Replace simple placeholders - now using dedicated fields
            $templateProcessor->setValue('NO_SPU', $form->no_spu ?? $form->form_number);
            $templateProcessor->setValue('NO_TERIMA_SAMPEL', $form->no_terima_sampel ?? $form->form_number);
            $templateProcessor->setValue('PERIHAL', $this->generatePerihal($form));
            $templateProcessor->setValue('TANGGAL', now()->translatedFormat('j F Y'));
            
            // Generate unique filename
            $filename = "SPU-{$form->form_number}-" . now()->format('YmdHis') . ".docx";
            $outputFile = $this->outputPath . '/' . $filename;
            
            // Save the document
            $templateProcessor->saveAs($outputFile);
            
            Log::info("Generated SPU document: {$filename}");
            
            return $filename;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate SPU: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate "Perihal" text from parameters
     * Properly formatted: "Analisis A, B, dan C" for 3+ params
     */
    protected function generatePerihal(FormPengujian $form): string
    {
        $parameters = [];
        
        foreach ($form->samples as $sample) {
            foreach ($sample->sampleParameters as $sp) {
                if ($sp->parameter && !in_array($sp->parameter->name, $parameters)) {
                    $parameters[] = $sp->parameter->name;
                }
            }
        }
        
        if (empty($parameters)) {
            return 'Analisis Sampel';
        }
        
        $count = count($parameters);
        
        if ($count === 1) {
            return 'Analisis ' . $parameters[0];
        }
        
        if ($count === 2) {
            return 'Analisis ' . $parameters[0] . ' dan ' . $parameters[1];
        }
        
        // 3 or more: "A, B, dan C"
        $last = array_pop($parameters);
        return 'Analisis ' . implode(', ', $parameters) . ', dan ' . $last;
    }

    /**
     * Get the full path to a generated document
     */
    public function getFilePath(string $filename): string
    {
        return $this->outputPath . '/' . $filename;
    }

    /**
     * Check if document exists
     */
    public function documentExists(string $filename): bool
    {
        return file_exists($this->getFilePath($filename));
    }

    /**
     * Delete a generated document
     */
    public function delete(string $filename): bool
    {
        $path = $this->getFilePath($filename);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}
