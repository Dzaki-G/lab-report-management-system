<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Docs;
use Google\Service\Docs\Request as DocsRequest;
use Google\Service\Docs\BatchUpdateDocumentRequest;
use App\Models\FormPengujian;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;
    protected Drive $driveService;
    protected Docs $docsService;
    protected string $folderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(base_path(config('google.service_account_path')));
        $this->client->setScopes([
            Drive::DRIVE,
            Docs::DOCUMENTS,
        ]);
        
        // Workaround for SSL certificate issues on Windows (development only)
        if (config('app.env') === 'local') {
            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
            ]);
            $this->client->setHttpClient($httpClient);
        }
        
        $this->driveService = new Drive($this->client);
        $this->docsService = new Docs($this->client);
        $this->folderId = config('google.drive_folder_id');
    }

    /**
     * Copy the SPU template and fill with form data
     */
    public function generateSpuDocument(FormPengujian $form, string $templateId): ?string
    {
        try {
            // Step 1: Copy the template
            $copyMetadata = new Drive\DriveFile([
                'name' => "SPU-" . str_replace(['/', '\\'], '-', $form->form_number),
                'parents' => [$this->folderId],
            ]);
            
            $copiedFile = $this->driveService->files->copy($templateId, $copyMetadata);
            $documentId = $copiedFile->id;
            
            Log::info("Created SPU document copy: {$documentId}");
            
            // Step 2: Replace placeholders with form data
            $this->replaceSpuPlaceholders($documentId, $form);
            
            return $documentId;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate SPU document: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Replace placeholders in the SPU document
     */
    protected function replaceSpuPlaceholders(string $documentId, FormPengujian $form): void
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Prepare replacement data
        $replacements = [
            '{{NO_SPU}}' => $form->form_number,
            '{{NO_TERIMA_SAMPEL}}' => $form->form_number,
            '{{PERIHAL}}' => $this->generatePerihal($form),
            '{{TANGGAL}}' => now()->translatedFormat('j F Y'),
        ];

        // Build the requests
        $requests = [];
        foreach ($replacements as $placeholder => $value) {
            $requests[] = new DocsRequest([
                'replaceAllText' => [
                    'containsText' => [
                        'text' => $placeholder,
                        'matchCase' => true,
                    ],
                    'replaceText' => $value,
                ],
            ]);
        }

        // Execute batch update
        if (!empty($requests)) {
            $batchUpdateRequest = new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]);
            
            $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
        }
    }

    /**
     * Generate "Perihal" text from parameters
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
        
        return 'Analisis ' . implode(' dan ', $parameters);
    }

    /**
     * Get the web view link for a document
     */
    public function getDocumentLink(string $documentId): string
    {
        return "https://docs.google.com/document/d/{$documentId}/edit";
    }

    /**
     * Get the export link for a document (PDF)
     */
    public function getExportLink(string $documentId, string $format = 'pdf'): string
    {
        return "https://docs.google.com/document/d/{$documentId}/export?format={$format}";
    }

    /**
     * Delete a document from Google Drive
     */
    public function deleteDocument(string $documentId): bool
    {
        try {
            $this->driveService->files->delete($documentId);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete document {$documentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * List files in the SPU folder
     */
    public function listDocuments(): array
    {
        try {
            $results = $this->driveService->files->listFiles([
                'q' => "'{$this->folderId}' in parents",
                'fields' => 'files(id, name, createdTime, webViewLink)',
            ]);
            
            return $results->getFiles();
        } catch (\Exception $e) {
            Log::error("Failed to list documents: " . $e->getMessage());
            return [];
        }
    }
}
