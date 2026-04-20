<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Docs;
use App\Models\FormPengujian;
use Illuminate\Support\Facades\Log;

class GoogleDocsService
{
    protected Client $client;
    protected Drive $driveService;
    protected Docs $docsService;
    
    protected string $templatesFolderId;
    protected string $spuFolderId;
    protected string $sp3FolderId;

    /**
     * Sanitize a string the use as Google Drive file name.
     * Google Drive does not allow '/' in file names.
     */
    protected function sanitizeFileName(string $name): string
    {
        // Replace / and \ with - (most common issue)
        $name = str_replace(['/', '\\'], '-', $name);
        // Remove other potentially problematic characters
        $name = preg_replace('/[\x00-\x1f]/', '', $name);
        return $name;
    }


    public function __construct()
    {
        $this->client = new Client();
        
        // Fix for Windows SSL error 60 (MUST BE FIRST)
        $this->client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = config('services.google.refresh_token');
        
        if ($clientId && $clientSecret && $refreshToken) {
            // OAuth Authentication
            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->client->refreshToken($refreshToken);
        } else {
            // Fallback to Service Account (Legacy)
            $this->client->setAuthConfig(base_path(env('GOOGLE_SERVICE_ACCOUNT_PATH')));
        }
        
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Docs::DOCUMENTS);
        
        $this->driveService = new Drive($this->client);
        $this->docsService = new Docs($this->client);
        
        $this->templatesFolderId = env('GOOGLE_TEMPLATES_FOLDER_ID');
        $this->spuFolderId = env('GOOGLE_SPU_FOLDER_ID');
        $this->sp3FolderId = env('GOOGLE_SP3_FOLDER_ID');

    }

    /**
     * Copy a template and create a new document
     */
    public function copyTemplate(string $templateFileId, string $newName, string $targetFolderId): array
    {
        try {
            // First, check if the template is a native Google Doc or an uploaded file
            $templateFile = $this->driveService->files->get($templateFileId, ['fields' => 'mimeType']);
            $mimeType = $templateFile->getMimeType();
            
            Log::info("Template mimeType: {$mimeType}");
            
            // If it's already a Google Doc, just copy it
            if ($mimeType === 'application/vnd.google-apps.document') {
                $copy = new Drive\DriveFile([
                    'name' => $newName,
                    'parents' => [$targetFolderId],
                ]);
                $result = $this->driveService->files->copy($templateFileId, $copy);
                
                return [
                    'id' => $result->id,
                    'url' => "https://docs.google.com/document/d/{$result->id}/edit",
                ];
            }
            
            // If it's a DOCX or other Office format, we need to:
            // 1. Download the file content directly (NOT export - export only works for native docs)
            // 2. Re-upload as Google Docs native format
            Log::info("Template is not native Google Docs, downloading and converting...");
            
            // Download the file content directly using alt=media
            $response = $this->driveService->files->get($templateFileId, ['alt' => 'media']);
            $fileContent = $response->getBody()->getContents();
            
            // Create a new Google Doc by uploading with conversion
            $newFile = new Drive\DriveFile([
                'name' => $newName,
                'parents' => [$targetFolderId],
                'mimeType' => 'application/vnd.google-apps.document', // Convert to Google Docs
            ]);
            
            $result = $this->driveService->files->create($newFile, [
                'data' => $fileContent,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);
            
            Log::info("Created new Google Doc with ID: {$result->id}");
            
            return [
                'id' => $result->id,
                'url' => "https://docs.google.com/document/d/{$result->id}/edit",
            ];
        } catch (\Exception $e) {
            Log::error("Failed to copy template: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Replace placeholders in a Google Doc
     */
    public function replacePlaceholders(string $documentId, array $replacements): void
    {
        try {
            $requests = [];
            
            Log::info("Replacing placeholders in document: {$documentId}");
            Log::info("Replacements: " . json_encode($replacements));
            
            foreach ($replacements as $placeholder => $value) {
                $searchTexts = ['${' . $placeholder . '}', '{{' . $placeholder . '}}'];
                
                foreach ($searchTexts as $searchText) {
                    Log::info("Will replace: '{$searchText}' with '{$value}'");
                    
                    $requests[] = new Docs\Request([
                        'replaceAllText' => [
                            'containsText' => [
                                'text' => $searchText,
                                'matchCase' => true,
                            ],
                            'replaceText' => $value ?? '',
                        ],
                    ]);
                }
            }

            if (!empty($requests)) {
                $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                    'requests' => $requests,
                ]);
                
                $response = $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
                Log::info("BatchUpdate response: " . json_encode($response->getReplies()));
            }
        } catch (\Exception $e) {
            Log::error("Failed to replace placeholders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a file from Google Drive
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->driveService->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template file ID by name from templates folder
     */
    public function getTemplateIdByName(string $templateName): ?string
    {
        try {
            $query = "name = '{$templateName}' and '{$this->templatesFolderId}' in parents and trashed = false";
            $results = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
            ]);

            $files = $results->getFiles();
            return !empty($files) ? $files[0]->getId() : null;
        } catch (\Exception $e) {
            Log::error("Failed to find template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * List all files in templates folder (for debugging)
     */
    public function listTemplatesFolder(): array
    {
        try {
            $query = "'{$this->templatesFolderId}' in parents and trashed = false";
            $results = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
            ]);

            $files = [];
            foreach ($results->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                ];
            }
            return $files;
        } catch (\Exception $e) {
            Log::error("Failed to list templates folder: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get file metadata
     */
    public function getFileMetadata(string $fileId)
    {
        return $this->driveService->files->get($fileId);
    }

    /**
     * Generate SPU document (unsigned version)
     */
    public function generateSpuUnsigned(FormPengujian $form): array
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Find unsigned SPU template
        $templateId = $this->getTemplateIdByName('SPU-Template-Unsigned.docx');
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SPU-001.docx');
        }
        
        if (!$templateId) {
            throw new \Exception('SPU template not found in Google Drive');
        }

        // Copy template to SPU folder
        $docName = $this->sanitizeFileName("SPU-{$form->form_number}");
        $result = $this->copyTemplate($templateId, $docName, $this->spuFolderId);

        // Replace placeholders
        $this->replacePlaceholders($result['id'], [
            'NO_SPU' => $form->no_spu ?? $form->form_number,
            'NO_TERIMA_SAMPEL' => $form->no_terima_sampel ?? '-',
            'PERIHAL' => $this->generatePerihal($form),
            'TANGGAL' => now()->translatedFormat('j F Y'),
        ]);

        return $result;
    }

    /**
     * Generate SPU document (signed version) - replaces unsigned
     * Called when Kepala UPA approves/verifies the SPU
     */
    public function generateSpuSigned(FormPengujian $form): array
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Find signed SPU template
        $templateId = config('services.google.spu_signed_template_id'); // This should be the 'Signed UPA' template
        
        if (!$templateId) {
             // Fallbacks
            $templateId = $this->getTemplateIdByName('SPU-Template-Signed');
             if (!$templateId) {
                $templateId = $this->getTemplateIdByName('SPU-Template-Signed.docx');
            }
        }
        
        if (!$templateId) {
            throw new \Exception('Signed SPU template not found in Google Drive');
        }

        // Copy template to SPU folder
        $docName = $this->sanitizeFileName("SPU-{$form->form_number}");
        $result = $this->copyTemplate($templateId, $docName, $this->spuFolderId);

        // Replace placeholders - TANGGAL is filled NOW (approval date)
        $this->replacePlaceholders($result['id'], [
            'NO_SPU' => $form->no_spu ?? $form->form_number,
            'NO_TERIMA_SAMPEL' => $form->no_terima_sampel ?? '-',
            'PERIHAL' => $this->generatePerihal($form),
            'TANGGAL' => now()->translatedFormat('j F Y'), // Approval date
        ]);

        // Populate sample table (dynamic rows)
        $this->populateSpuTable($result['id'], $form);

        return $result;
    }

    /**
     * Generate SP3 document
     */
    public function generateSp3(string $sp3Number, array $sampleCodes, string $parameterName, string $perihal, string $noSpu): array
    {
        // ... (existing implementation if any, but we use generateSp3WithTable usually)
        // Kept for backward compatibility if needed, otherwise rely on generateSp3WithTable
        // For now, let's just make sure generateSp3WithTable is the main one.
        return $this->generateSp3WithTable($sp3Number, $sampleCodes, $parameterName, $perihal, $noSpu);
    }

    /**
     * Generate SP3 document with table
     */
    public function generateSp3WithTable(string $sp3Number, array $samples, string $parameterName, string $perihal, string $noSpu, ?string $noSppp = null, ?string $ik = null, ?string $analystName = null): array
    {
        // Find SP3 template
        $templateId = config('services.google.sp3_template_id');
        
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SP3-Template');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SP3-Template.docx');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SP3-001');
        }
        
        if (!$templateId) {
            throw new \Exception('SP3 template not found in Google Drive');
        }

        // Copy template to SP3 folder
        $docName = $this->sanitizeFileName($sp3Number);
        $result = $this->copyTemplate($templateId, $docName, $this->sp3FolderId);

        // Replace placeholders
        $replacements = [
            'NO_SP3' => $sp3Number,
            'PERIHAL' => $perihal,
            'NO_SPU' => $noSpu,
            'PARAMETER' => $parameterName,
            'TANGGAL' => now()->translatedFormat('j F Y'),
        ];
        
        if ($noSppp) {
             $replacements['NO_SPPP'] = $noSppp;
        }

        $this->replacePlaceholders($result['id'], $replacements);

        // Populate sample table using dedicated method
        $this->populateSp3Table($result['id'], $samples, $parameterName, $ik, $analystName);

        return $result;
    }
    
    /**
     * Generate "Perihal" text from parameters
     */
    public function generatePerihal(FormPengujian $form): string
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
     * Get document structure to find tables
     */
    public function getDocumentStructure(string $documentId): array
    {
        try {
            $document = $this->docsService->documents->get($documentId);
            return json_decode(json_encode($document->getBody()), true);
        } catch (\Exception $e) {
            Log::error("Failed to get document structure: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find table containing a placeholder text
     */
    public function findTableWithPlaceholder(string $documentId, string $placeholder): ?array
    {
        try {
            $document = $this->docsService->documents->get($documentId);
            $body = $document->getBody();
            $content = $body->getContent();

            foreach ($content as $element) {
                if ($element->getTable()) {
                    $table = $element->getTable();
                    $tableRows = $table->getTableRows();
                    
                    foreach ($tableRows as $rowIndex => $row) {
                        $cells = $row->getTableCells();
                        foreach ($cells as $cell) {
                            $cellContent = $cell->getContent();
                            foreach ($cellContent as $para) {
                                if ($para->getParagraph()) {
                                    $elements = $para->getParagraph()->getElements();
                                    foreach ($elements as $elem) {
                                        if ($elem->getTextRun()) {
                                            $text = $elem->getTextRun()->getContent();
                                            if (strpos($text, $placeholder) !== false) {
                                                return [
                                                    'tableStartIndex' => $element->getStartIndex(),
                                                    'tableEndIndex' => $element->getEndIndex(),
                                                    'templateRowIndex' => $rowIndex,
                                                    'rowStartIndex' => $row->getStartIndex(),
                                                    'rowEndIndex' => $row->getEndIndex(),
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Failed to find table: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Populate SPU table with sample data using DYNAMIC row insertion
     * 
     * STRUKTUR TABEL SPU:
     * - Group by Parameter: Sampel dikelompokkan berdasarkan parameter
     * - Parameter 1x per grup: Nama parameter hanya muncul di row pertama tiap grup
     * - Sampel bisa muncul berkali-kali: Jika sampel punya banyak parameter
     * - UNLIMITED ROWS: Menggunakan insertTableRow
     * 
     * Template harus punya row dengan placeholder: {{ROW}}
     */
    public function populateSpuTable(string $documentId, FormPengujian $form): void
    {
        $form->load(['samples.sampleParameters.parameter']);

        // Step 1: Build parameter -> samples mapping
        $parameterSamples = [];
        $parameterInfo = [];
        
        foreach ($form->samples as $sample) {
            foreach ($sample->sampleParameters as $sp) {
                if ($sp->parameter) {
                    $paramId = $sp->parameter->id;
                    
                    if (!isset($parameterSamples[$paramId])) {
                        $parameterSamples[$paramId] = [];
                        $parameterInfo[$paramId] = [
                            'name' => $sp->parameter->name,
                            'method' => $sp->parameter->default_method ?? '-',
                        ];
                    }
                    
                    $parameterSamples[$paramId][] = [
                        'sample_code' => $sample->sample_code,
                        'sample_name' => $sample->sample_name,
                    ];
                }
            }
        }

        // Step 2: Build table data (grouped by parameter)
        $tableData = [];
        $no = 1;
        
        foreach ($parameterSamples as $paramId => $samples) {
            $isFirstInGroup = true;
            
            foreach ($samples as $sample) {
                $tableData[] = [
                    $no++,
                    $sample['sample_code'],
                    $sample['sample_name'],
                    $isFirstInGroup ? $parameterInfo[$paramId]['name'] : '',
                    $isFirstInGroup ? $parameterInfo[$paramId]['method'] : '',
                ];
                $isFirstInGroup = false;
            }
        }

        // Step 3: Populate table dynamically
        $this->insertTableDataDynamic($documentId, '{{ROW}}', $tableData);
    }

    /**
     * Insert table data dynamically using Google Docs API
     * 
     * SIMPLIFIED APPROACH:
     * 1. For the first row: replace {{ROW}} with the actual data (using cell placeholders)
     * 2. For subsequent rows: duplicate the last data row and fill
     * 
     * Alternative simpler approach used here:
     * - Replace {{ROW}} placeholder with all rows as text
     * - Use newlines to separate rows within the same cell
     * 
     * @param string $documentId Document ID
     * @param string $placeholder Placeholder text to find (e.g. "{{ROW}}")
     * @param array $data Array of rows, each row is array of cell values
     */
    public function insertTableDataDynamic(string $documentId, string $placeholder, array $data): void
    {
        if (empty($data)) {
            // Just remove the placeholder
            $this->replaceTextSimple($documentId, $placeholder, '');
            return;
        }

        try {
            Log::info("insertTableDataDynamic: Processing " . count($data) . " rows");
            
            // Get document structure
            $document = $this->docsService->documents->get($documentId);
            $tableInfo = $this->findTableInfo($document, $placeholder);

            if (!$tableInfo) {
                Log::warning("Template row with placeholder '{$placeholder}' not found");
                return;
            }

            Log::info("Found table at index: " . $tableInfo['tableStartIndex'] . ", row: " . $tableInfo['rowIndex']);

            // Process rows one by one to avoid index issues
            // First, insert all the new rows we need (all at once)
            if (count($data) > 0) {
                $insertRowRequests = [];
                
                // Insert (count - 1) new rows because we'll use the template row for the first data
                for ($i = 0; $i < count($data) - 1; $i++) {
                    $insertRowRequests[] = new Docs\Request([
                        'insertTableRow' => [
                            'tableCellLocation' => [
                                'tableStartLocation' => [
                                    'index' => $tableInfo['tableStartIndex'],
                                ],
                                'rowIndex' => $tableInfo['rowIndex'],
                                'columnIndex' => 0,
                            ],
                            'insertBelow' => true,
                        ],
                    ]);
                }

                if (!empty($insertRowRequests)) {
                    Log::info("Inserting " . count($insertRowRequests) . " new rows");
                    $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                        'requests' => $insertRowRequests,
                    ]);
                    $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
                }
            }

            // Now fill data using replaceAllText for each unique placeholder
            // First, let's clear the {{ROW}} placeholder from the template row
            $this->replaceTextSimple($documentId, $placeholder, '');
            
            // Get fresh document structure
            $document = $this->docsService->documents->get($documentId);
            $body = $document->getBody();
            $content = $body->getContent();
            
            // Find our table again
            foreach ($content as $element) {
                if (!$element->getTable()) continue;
                if ($element->getStartIndex() != $tableInfo['tableStartIndex']) continue;
                
                $table = $element->getTable();
                $tableRows = $table->getTableRows();
                
                // Data rows start at the template row index
                $startRowIndex = $tableInfo['rowIndex'];
                
                // Build insert requests
                $textInsertRequests = [];
                
                for ($dataIndex = 0; $dataIndex < count($data); $dataIndex++) {
                    $rowIndex = $startRowIndex + $dataIndex;
                    if (!isset($tableRows[$rowIndex])) {
                        Log::warning("Row index {$rowIndex} not found in table");
                        continue;
                    }
                    
                    $row = $tableRows[$rowIndex];
                    $cells = $row->getTableCells();
                    $rowData = $data[$dataIndex];
                    
                    for ($colIndex = 0; $colIndex < count($cells) && $colIndex < count($rowData); $colIndex++) {
                        $cell = $cells[$colIndex];
                        $cellContent = $cell->getContent();
                        
                        // Get the text to insert - skip if empty
                        $textToInsert = (string) ($rowData[$colIndex] ?? '');
                        if ($textToInsert === '') {
                            continue; // Skip empty cells - API rejects empty insertText
                        }
                        
                        // Get the start index of the paragraph inside the cell
                        if (!empty($cellContent)) {
                            $para = $cellContent[0];
                            if ($para && $para->getParagraph()) {
                                // Insert at the start of the paragraph (after paragraph marker)
                                $insertIndex = $para->getStartIndex();
                                
                                $textInsertRequests[] = [
                                    'index' => $insertIndex,
                                    'text' => $textToInsert,
                                ];
                            }
                        }
                    }
                }
                
                // Execute insertions in REVERSE order to maintain correct indices
                usort($textInsertRequests, function($a, $b) {
                    return $b['index'] - $a['index']; // Descending order
                });
                
                $requests = [];
                foreach ($textInsertRequests as $req) {
                    $requests[] = new Docs\Request([
                        'insertText' => [
                            'location' => ['index' => $req['index']],
                            'text' => $req['text'],
                        ],
                    ]);
                }
                
                if (!empty($requests)) {
                    Log::info("Inserting text into " . count($requests) . " cells");
                    $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                        'requests' => $requests,
                    ]);
                    $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
                }
                
                break;
            }
            
            Log::info("Successfully inserted " . count($data) . " rows into table");
            
        } catch (\Exception $e) {
            Log::error("Failed to insert table data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find table info containing placeholder
     */
    private function findTableInfo($document, string $placeholder): ?array
    {
        $body = $document->getBody();
        $content = $body->getContent();

        foreach ($content as $element) {
            if (!$element->getTable()) continue;
            
            $table = $element->getTable();
            $tableRows = $table->getTableRows();
            
            foreach ($tableRows as $rowIndex => $row) {
                $cells = $row->getTableCells();
                foreach ($cells as $cell) {
                    $cellContent = $cell->getContent();
                    foreach ($cellContent as $para) {
                        if (!$para->getParagraph()) continue;
                        
                        $elements = $para->getParagraph()->getElements();
                        foreach ($elements as $elem) {
                            if (!$elem->getTextRun()) continue;
                            
                            $text = $elem->getTextRun()->getContent();
                            if (strpos($text, $placeholder) !== false) {
                                return [
                                    'tableStartIndex' => $element->getStartIndex(),
                                    'rowIndex' => $rowIndex,
                                    'rowCount' => count($tableRows),
                                    'colCount' => count($cells),
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Simple text replacement (without ${} wrapper)
     */
    public function replaceTextSimple(string $documentId, string $searchText, string $replaceText): void
    {
        try {
            $requests = [
                new Docs\Request([
                    'replaceAllText' => [
                        'containsText' => [
                            'text' => $searchText,
                            'matchCase' => true,
                        ],
                        'replaceText' => $replaceText,
                    ],
                ]),
            ];

            $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]);
            
            $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
        } catch (\Exception $e) {
            Log::error("Failed to replace text: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Populate SP3 table with sample data using DYNAMIC row insertion
     * Template harus punya row dengan placeholder: {{ROW}}
     * 
     * Kolom: No, Kode Sampel, Parameter Uji, IK, Analis, Tgl Paraf
     * Note: IK dan Analis hanya muncul di baris pertama
     */
    public function populateSp3Table(string $documentId, array $samples, string $parameterName, ?string $ik = null, ?string $analystName = null): void
    {
        // Build table data from samples
        $tableData = [];
        $no = 1;
        
        foreach ($samples as $sample) {
            $isFirstRow = ($no === 1);
            
            $tableData[] = [
                $no++,
                $sample['sample_code'],
                $parameterName,
                $isFirstRow ? ($ik ?? '') : '', // IK only on first row
                $isFirstRow ? ($analystName ?? '') : '', // Analis only on first row
                '', // Tgl Paraf - to be filled later
            ];
        }

        // Use dynamic table insertion
        $this->insertTableDataDynamic($documentId, '{{ROW}}', $tableData);
    }


    // New methods below


    /**
     * Update SP3 document with assignment info
     * To be called by Kepala Divisi or Admin
     */
    public function updateSp3Info(string $documentId, ?string $noSppp = '', ?string $ik = '', ?string $analystName = ''): void
    {
        $this->replacePlaceholders($documentId, [
            'NO_SPPP' => $noSppp ?? '',
            'IK' => $ik ?? '', // Assuming template has {{IK}} or {{METODE}}
            'METODE' => $ik ?? '', // Backup placeholder
            'ANALIS' => $analystName ?? '',
        ]);
    }

    /**
     * Sign SPU by Kepala Divisi
     * Replaces placeholder with signer name indicating approval
     */
    /**
     * Generate SPU document (signed full) - replaces signed UPA version
     * Called when Kepala Divisi signs the SPU
     */
    public function generateSpuSignedFull(FormPengujian $form): array
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Find signed FULL SPU template
        $templateId = config('services.google.spu_signed_full_template_id');
        
        if (!$templateId) {
            throw new \Exception('Signed Full SPU template ID not configured');
        }

        // Copy template to SPU folder
        $docName = $this->sanitizeFileName("SPU-{$form->form_number}");
        // This will create a NEW file. We might want to trash the old one later if needed.
        $result = $this->copyTemplate($templateId, $docName, $this->spuFolderId);

        // Replace placeholders (Same as before, just ensuring data consistency)
        $this->replacePlaceholders($result['id'], [
            'NO_SPU' => $form->no_spu ?? $form->form_number,
            'NO_TERIMA_SAMPEL' => $form->no_terima_sampel ?? '-',
            'PERIHAL' => $this->generatePerihal($form),
            'TANGGAL' => $form->spu_signed_at ? $form->spu_signed_at->translatedFormat('j F Y') : now()->translatedFormat('j F Y'),
            // TTD_DIVISI is now an IMAGE in the template, so no placeholder needed or it's already there
        ]);

        // Populate sample table (dynamic rows)
        $this->populateSpuTable($result['id'], $form);

        return $result;
    }

    /**
     * Generate SP3 document (signed) - replaces unsigned version
     * Called when Kepala Divisi approves the form (final approval)
     */
    public function generateSp3Signed(\App\Models\Sp3Document $sp3): array
    {
        // Find signed SP3 template
        $templateId = config('services.google.sp3_signed_template_id');
        
        if (!$templateId) {
            throw new \Exception('Signed SP3 template ID not configured');
        }

        // Get related data (include assignedAnalyst for ANALIS placeholder)
        $sp3->load(['parameter', 'samples', 'form', 'assignedAnalyst']);
        $form = $sp3->form;
        
        // Build samples array for table population
        $samples = $sp3->samples->map(function($sample) {
            return [
                'sample_id' => $sample->id,
                'sample_code' => $sample->sample_code,
                'sample_name' => $sample->sample_name,
            ];
        })->toArray();

        // Copy template to SP3 folder
        $docName = $sp3->sp3_number;
        $result = $this->copyTemplate($templateId, $docName, $this->sp3FolderId);

        // Replace placeholders (include backups for different template naming)
        $analystName = $sp3->assignedAnalyst ? $sp3->assignedAnalyst->full_name : '-';
        $ikValue = $sp3->ik ?? '-';
        
        $this->replacePlaceholders($result['id'], [
            'NO_SP3' => $sp3->sp3_number,
            'PERIHAL' => $this->generatePerihal($form),
            'NO_SPU' => $form->no_spu ?? $form->form_number,
            'NO_SPPP' => $sp3->no_sppp ?? '-',
            'IK' => $ikValue,
            'METODE' => $ikValue, // Backup placeholder
            'INSTRUKSI_KERJA' => $ikValue, // Another backup
            'PARAMETER' => $sp3->parameter->name ?? '-',
            'ANALIS' => $analystName,
            'NAMA_ANALIS' => $analystName, // Backup placeholder
            'TANGGAL' => now()->translatedFormat('j F Y'),
        ]);

        // Populate sample table with IK and Analis values for signed version
        $this->populateSp3Table($result['id'], $samples, $sp3->parameter->name ?? '', $ikValue, $analystName);

        return $result;
    }

    /**
     * Generate SPU document with table (unsigned version)
     */
    public function generateSpuWithTable(FormPengujian $form): array
    {
        $form->load(['samples.sampleParameters.parameter']);
        
        // Find unsigned SPU template
        $templateId = config('services.google.spu_unsigned_template_id');
        
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SPU-Template-Unsigned');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SPU-Template-Unsigned.docx');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('SPU-001');
        }
        
        if (!$templateId) {
            throw new \Exception('SPU template not found in Google Drive');
        }

        // Copy template to SPU folder
        $docName = $this->sanitizeFileName("SPU-{$form->form_number}");
        $result = $this->copyTemplate($templateId, $docName, $this->spuFolderId);

        // Replace simple placeholders
        // NOTE: TANGGAL is NOT filled here - it will be filled when Kepala UPA approves
        $this->replacePlaceholders($result['id'], [
            'NO_SPU' => $form->no_spu ?? $form->form_number,
            'NO_TERIMA_SAMPEL' => $form->no_terima_sampel ?? '-',
            'PERIHAL' => $this->generatePerihal($form),
            'TANGGAL' => '', // Will be filled on Kepala UPA approval
        ]);

        // Populate sample table
        $this->populateSpuTable($result['id'], $form);

        return $result;
    }



    // Getter methods for folder IDs
    public function getSpuFolderId(): string
    {
        return $this->spuFolderId;
    }

    public function getSp3FolderId(): string
    {
        return $this->sp3FolderId;
    }

    public function getTemplatesFolderId(): string
    {
        return $this->templatesFolderId;
    }
}
