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
    protected string $sp3FolderId;
    protected string $lhpFolderId;

    /**
     * Sanitize a string the use as Google Drive file name.
     * Google Drive does not allow '/' in file names.
     */
    protected function sanitizeFileName(string $name): string
    {
        $name = str_replace(['/', '\\'], '-', $name);
        $name = preg_replace('/[\x00-\x1f]/', '', $name);
        return $name;
    }

    public function __construct()
    {
        $this->client = new Client();

        // Fix for Windows SSL error 60 (MUST BE FIRST)
        // NOTE: disabling certificate verification is a known security concern —
        // left as-is here since it was a deliberate local-dev workaround, but
        // worth revisiting (e.g. only disable when app()->environment('local'))
        // before this ever runs anywhere production-like.
        $this->client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = config('services.google.refresh_token');

        if ($clientId && $clientSecret && $refreshToken) {
            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->client->refreshToken($refreshToken);
        } else {
            $this->client->setAuthConfig(base_path(env('GOOGLE_SERVICE_ACCOUNT_PATH')));
        }

        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Docs::DOCUMENTS);

        $this->driveService = new Drive($this->client);
        $this->docsService = new Docs($this->client);

        $this->templatesFolderId = env('GOOGLE_TEMPLATES_FOLDER_ID');
        $this->sp3FolderId = env('GOOGLE_SP3_FOLDER_ID');
        // New — output folder for the generated LHP documents.
        // Add GOOGLE_LHP_FOLDER_ID to your .env before using generateLhp().
        $this->lhpFolderId = env('GOOGLE_LHP_FOLDER_ID');
    }

    /**
     * Copy a template and create a new document
     */
    public function copyTemplate(string $templateFileId, string $newName, string $targetFolderId): array
    {
        try {
            $templateFile = $this->driveService->files->get($templateFileId, ['fields' => 'mimeType']);
            $mimeType = $templateFile->getMimeType();

            Log::info("Template mimeType: {$mimeType}");

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

            Log::info("Template is not native Google Docs, downloading and converting...");

            $response = $this->driveService->files->get($templateFileId, ['alt' => 'media']);
            $fileContent = $response->getBody()->getContents();

            $newFile = new Drive\DriveFile([
                'name' => $newName,
                'parents' => [$targetFolderId],
                'mimeType' => 'application/vnd.google-apps.document',
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
     * Replace placeholders in a Google Doc.
     * Used both for initial generation AND for the lightweight signature
     * patch on the LHP doc (see patchLhpSignature below) — same mechanism,
     * no new file, no delete.
     */
    public function replacePlaceholders(string $documentId, array $replacements): void
    {
        try {
            $requests = [];

            foreach ($replacements as $placeholder => $value) {
                $searchTexts = ['${' . $placeholder . '}', '{{' . $placeholder . '}}'];

                foreach ($searchTexts as $searchText) {
                    $requests[] = new Docs\Request([
                        'replaceAllText' => [
                            'containsText' => [
                                'text' => $searchText,
                                'matchCase' => true,
                            ],
                            'replaceText' => (string) ($value ?? ''),
                        ],
                    ]);
                }
            }

            if (!empty($requests)) {
                $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                    'requests' => $requests,
                ]);

                $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
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
     * List all files in templates folder (debugging utility)
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
     * Generate SP3 document with table.
     * UNCHANGED from before — SP3 is still generated once, at form creation,
     * as a real Google Doc that analysts reference while working.
     */
    public function generateSp3WithTable(string $sp3Number, array $samples, string $parameterName, string $perihal, ?string $noSppp = null, ?string $ik = null, ?string $analystName = null): array
    {
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

        $docName = $this->sanitizeFileName($sp3Number);
        $result = $this->copyTemplate($templateId, $docName, $this->sp3FolderId);

        $replacements = [
            'NO_SP3' => $sp3Number,
            'PERIHAL' => $perihal,
            'PARAMETER' => $parameterName,
            'TANGGAL' => now()->translatedFormat('j F Y'),
        ];

        if ($noSppp) {
            $replacements['NO_SPPP'] = $noSppp;
        }

        $this->replacePlaceholders($result['id'], $replacements);

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

        $last = array_pop($parameters);
        return 'Analisis ' . implode(', ', $parameters) . ', dan ' . $last;
    }

    /**
     * Insert table data dynamically using Google Docs API.
     * Generic engine — used for SP3's table AND the LHP results table.
     * Template must have a row containing the given placeholder (e.g. "{{ROW}}").
     *
     * @param string $documentId Document ID
     * @param string $placeholder Placeholder text to find (e.g. "{{ROW}}")
     * @param array $data Array of rows, each row is array of cell values
     * @return int|null The tableStartIndex of the populated table, or null if not found
     */
    public function insertTableDataDynamic(string $documentId, string $placeholder, array $data): ?int
    {
        if (empty($data)) {
            $this->replaceTextSimple($documentId, $placeholder, '');
            return null;
        }

        try {
            $document = $this->docsService->documents->get($documentId);
            $tableInfo = $this->findTableInfo($document, $placeholder);

            if (!$tableInfo) {
                Log::warning("Template row with placeholder '{$placeholder}' not found");
                return null;
            }

            $tableStartIndex = $tableInfo['tableStartIndex'];

            if (count($data) > 0) {
                $insertRowRequests = [];

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
                    $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                        'requests' => $insertRowRequests,
                    ]);
                    $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
                }
            }

            $this->replaceTextSimple($documentId, $placeholder, '');

            $document = $this->docsService->documents->get($documentId);
            $body = $document->getBody();
            $content = $body->getContent();

            foreach ($content as $element) {
                if (!$element->getTable()) continue;
                if ($element->getStartIndex() != $tableInfo['tableStartIndex']) continue;

                $table = $element->getTable();
                $tableRows = $table->getTableRows();

                $startRowIndex = $tableInfo['rowIndex'];

                // Process each cell individually with a fresh document read each time.
                // Index-based insertText shifts all subsequent positions in the doc,
                // so stale indices — even within the same row — cause out-of-bounds
                // errors. Re-reading per cell is slower but guaranteed correct.
                // Order: last row → first row, rightmost col → leftmost col,
                // so insertions never disturb indices for cells we haven't touched yet.
                for ($dataIndex = count($data) - 1; $dataIndex >= 0; $dataIndex--) {
                    $rowData = $data[$dataIndex];

                    // Collect non-empty (colIndex, text) pairs for this row
                    $colsToInsert = [];
                    for ($colIndex = 0; $colIndex < count($rowData); $colIndex++) {
                        $textToInsert = (string) ($rowData[$colIndex] ?? '');
                        // Strip newlines from all columns EXCEPT column 1 (Sample Name/Code)
                        if ($colIndex !== 1) {
                            $textToInsert = str_replace(["\r\n", "\r", "\n"], ' ', $textToInsert);
                        }
                        if ($textToInsert !== '') {
                            $colsToInsert[] = ['col' => $colIndex, 'text' => $textToInsert];
                        }
                    }

                    if (empty($colsToInsert)) {
                        continue;
                    }

                    // Right-to-left within the row
                    usort($colsToInsert, fn($a, $b) => $b['col'] - $a['col']);

                    foreach ($colsToInsert as $colEntry) {
                        // Fresh read every cell so indices are always accurate
                        $freshDoc   = $this->docsService->documents->get($documentId);
                        $freshTable = $this->findTableByStartIndex($freshDoc, $tableInfo['tableStartIndex']);
                        if (!$freshTable) {
                            Log::warning("Table not found at startIndex {$tableInfo['tableStartIndex']} on fresh read");
                            continue;
                        }

                        $freshRows  = $freshTable->getTableRows();
                        $rowIndex   = $startRowIndex + $dataIndex;
                        if (!isset($freshRows[$rowIndex])) {
                            Log::warning("Row {$rowIndex} not found after fresh read");
                            continue;
                        }

                        $freshCells = $freshRows[$rowIndex]->getTableCells();
                        if (!isset($freshCells[$colEntry['col']])) {
                            continue;
                        }

                        $cellContent = $freshCells[$colEntry['col']]->getContent();
                        if (empty($cellContent)) {
                            continue;
                        }

                        $para = $cellContent[0];
                        if (!$para || !$para->getParagraph()) {
                            continue;
                        }

                        $insertIndex = $para->getEndIndex() - 1;
                        if ($insertIndex < 0) {
                            Log::warning("Skipping negative insertIndex {$insertIndex}");
                            continue;
                        }

                        Log::info("insertText row={$rowIndex} col={$colEntry['col']} index={$insertIndex} text='" . substr($colEntry['text'], 0, 30) . "'");
                        $this->docsService->documents->batchUpdate(
                            $documentId,
                            new Docs\BatchUpdateDocumentRequest([
                                'requests' => [
                                    new Docs\Request([
                                        'insertText' => [
                                            'location' => ['index' => $insertIndex],
                                            'text'     => $colEntry['text'],
                                        ],
                                    ]),
                                ],
                            ])
                        );
                    }
                }

                break;
            }

            return $tableStartIndex;
        } catch (\Exception $e) {
            Log::error("Failed to insert table data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find a table element by its known startIndex.
     */
    private function findTableByStartIndex($document, int $startIndex)
    {
        $content = $document->getBody()->getContent();
        foreach ($content as $element) {
            if ($element->getTable() && $element->getStartIndex() == $startIndex) {
                return $element->getTable();
            }
        }
        return null;
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
     * Populate SP3 table with sample data using DYNAMIC row insertion.
     * UNCHANGED. Template must have a row with placeholder: {{ROW}}
     * Kolom: No, Kode Sampel, Parameter Uji, IK, Analis, Tgl Paraf
     * IK dan Analis hanya muncul di baris pertama.
     */
    public function populateSp3Table(string $documentId, array $samples, string $parameterName, ?string $ik = null, ?string $analystName = null): void
    {
        $tableData = [];
        $no = 1;

        foreach ($samples as $sample) {
            $isFirstRow = ($no === 1);

            $tableData[] = [
                $no++,
                $sample['sample_code'],
                $parameterName,
                $isFirstRow ? ($ik ?? '') : '',
                $isFirstRow ? ($analystName ?? '') : '',
                '',
            ];
        }

        $this->insertTableDataDynamic($documentId, '{{ROW}}', $tableData);
    }

    /**
     * Generate the LHP (Laporan Hasil Pengujian) document.
     *
     * This is the ONLY point in the whole lifecycle this document is
     * created — never regenerated afterward. Called once, when Kepala
     * Divisi's approval of the last pending SP3 completes (see
     * KepalaDivisiController::maybeGenerateLhp()).
     *
     * Page 1: form/customer info (placeholders).
     * Page 2: results table, grouped by sample — columns confirmed as:
     *   No | Nama Sampel/Kode Sampel | Parameter Uji | Satuan | Hasil* | Metode Uji
     * The footnote text under the table is expected to already exist as
     * static content in the template — it is NOT generated here.
     *
     * Leaves ${UPA_NAME} / ${UPA_TANGGAL} placeholders blank for
     * patchLhpSignature() to fill in later.
     */
    public function generateLhp(FormPengujian $form, ?string $divisiName = null): array
    {
        $form->load([
            'samples.sampleParameters.parameter',
            'samples.sampleParameters.analysisResult',
        ]);

        $templateId = config('services.google.lhp_template_id');
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('LHP-Template');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('LHP-Template.docx');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('temporaryLHP_1');
        }
        if (!$templateId) {
            $templateId = $this->getTemplateIdByName('temporaryLHP_1.docx');
        }

        if (!$templateId) {
            throw new \Exception('LHP template not found in Google Drive');
        }

        $docName = $this->sanitizeFileName("LHP-{$form->form_number}");
        $result = $this->copyTemplate($templateId, $docName, $this->lhpFolderId);

        // Auto-generate LHP number if not already set
        if (!$form->lhp_number) {
            $form->update(['lhp_number' => \App\Models\FormPengujian::generateLhpNumber()]);
            $form->refresh();
        }

        // Derive TANGGAL_ANALISIS as range from received_date to LHP generation date
        $tanggalAnalisis = '-';
        if ($form->received_date) {
            $receivedDate = \Carbon\Carbon::parse($form->received_date);
            $lhpDate = now();

            $receivedFormatted = $receivedDate->translatedFormat('j F Y');
            $lhpFormatted = $lhpDate->translatedFormat('j F Y');

            $tanggalAnalisis = ($receivedFormatted === $lhpFormatted)
                ? $receivedFormatted
                : "{$receivedFormatted} - {$lhpFormatted}";
        }

        // Collect unique instruments from analysis results (analyst-entered), fall back to parameter default
        $instruments = collect();
        foreach ($form->samples as $sample) {
            foreach ($sample->sampleParameters as $sp) {
                $instrument = $sp->analysisResult?->instrument
                    ?: $sp->parameter?->instrument;
                if ($instrument) {
                    $instruments->push($instrument);
                }
            }
        }
        $instrumentText = $instruments->unique()->filter()->implode(', ') ?: '-';

        // Page 1 — form/customer info
        $this->replacePlaceholders($result['id'], [
            'NO_LHP'             => $form->lhp_number ?? '-',
            'FORM_NUMBER'        => $form->form_number,
            'NO_TERIMA_SAMPEL'   => $form->no_terima_sampel ?? '-',
            'CUSTOMER_NAME'      => $form->customer_name ?? '-',
            'CUSTOMER_ADDRESS'   => $form->customer_address ?? '-',
            'CUSTOMER_INSTITUTION' => $form->customer_institution ?? '-',
            'CUSTOMER_POSITION'  => $form->customer_position ?? '-',
            'CUSTOMER_PHONE'     => $form->customer_phone ?? '-',
            'CONTACT_PERSON'     => $form->contact_person ?? '-',
            'RECEIVED_DATE'      => $form->received_date ? \Carbon\Carbon::parse($form->received_date)->translatedFormat('j F Y') : '-',
            'TANGGAL_ANALISIS'   => $tanggalAnalisis,
            'PERIHAL'            => $this->generatePerihal($form),
            'TANGGAL'            => now()->translatedFormat('j F Y'),
            'DIVISI_NAME'        => $divisiName ?? '',
            'INSTRUMENT'         => $instrumentText,
            'SAMPLE_TYPE'        => $form->sample_type ?? '-',
            'SAMPLE_MATRIX'      => $form->sample_matrix ?? '-',
            'SAMPLE_NAME_LABEL'  => $form->sample_name_label ?? '-',
            'SAMPLE_FORM'        => $form->sample_form ?? '-',
            'SAMPLE_PACKING'     => $form->sample_packing ?? '-',
            'SAMPLE_COUNT'       => $form->sample_count ?? $form->samples->count(),
            // Left blank on purpose — filled later by patchLhpSignature()
            'UPA_NAME'           => '',
            'UPA_TANGGAL'        => '',
        ]);

        // Page 2 — results table, grouped by sample
        $this->populateLhpTable($result['id'], $form);

        return $result;
    }

    /**
     * Populate the LHP results table.
     * Columns: No | Nama Sampel/Kode Sampel | Parameter Uji | Satuan | Hasil | Metode Uji
     * "No" and "Nama Sampel/Kode Sampel" only appear on the first row of
     * each sample's group (blank on subsequent rows for that sample) —
     * mirrors the reference table layout provided.
     */
    private function populateLhpTable(string $documentId, FormPengujian $form): void
    {
        $tableData = [];
        $sampleGroups = []; // Track row ranges for each sample to merge later
        $no = 1;
        $currentRow = 0;

        foreach ($form->samples as $sample) {
            $isFirstInGroup = true;
            $sampleLabel = $sample->sample_code . "\n" . $sample->sample_name;
            $groupStartRow = $currentRow;
            $rowsInGroup = 0;

            foreach ($sample->sampleParameters as $sp) {
                if (!$sp->parameter) {
                    continue;
                }

                $result = $sp->analysisResult;
                $unit = $result?->result_unit ?? $sp->parameter->default_unit ?? '-';
                $hasil = $result?->result_value ?? '-';
                $metode = $sp->method ?? $sp->parameter->default_method ?? '-';

                // 6-cell structure (after merging sample column in template):
                // No | Sample (merged) | Parameter | Unit | Results | Method
                $tableData[] = [
                    $isFirstInGroup ? $no . '.' : '',
                    $isFirstInGroup ? $sampleLabel : '',
                    $sp->parameter->name,
                    $unit,
                    $hasil,
                    $metode,
                ];

                $isFirstInGroup = false;
                $currentRow++;
                $rowsInGroup++;
            }

            // Only track groups with multiple rows (need merging)
            if ($rowsInGroup > 1) {
                $sampleGroups[] = [
                    'startRow' => $groupStartRow,
                    'rowCount' => $rowsInGroup,
                ];
            }

            $no++;
        }

        // insertTableDataDynamic returns the tableStartIndex it used
        $tableStartIndex = $this->insertTableDataDynamic($documentId, '{{ROW}}', $tableData);

        // Now merge cells for each sample group using the correct table
        if (!empty($sampleGroups) && $tableStartIndex !== null) {
            $this->mergeLhpTableCells($documentId, $sampleGroups, $tableStartIndex);
        }

        // Apply tight padding to Parameter/Unit/Results/Method columns
        if ($tableStartIndex !== null) {
            $this->applyTightCellPadding($documentId, $tableStartIndex, count($tableData));
            $this->applyMinimumRowHeight($documentId, $tableStartIndex, count($tableData));
        }
    }

    /**
     * Merge cells vertically for sample groups in the LHP table.
     * Merges column 0 (No.) and column 1 (Sample Name/Code) across multiple parameter rows.
     */
    private function mergeLhpTableCells(string $documentId, array $sampleGroups, int $tableStartIndex): void
    {
        try {
            $document = $this->docsService->documents->get($documentId);
            $content = $document->getBody()->getContent();

            // Find the specific table by its startIndex
            $table = null;
            foreach ($content as $element) {
                if ($element->getTable() && $element->getStartIndex() == $tableStartIndex) {
                    $table = $element->getTable();
                    $tableRowCount = count($table->getTableRows());
                    Log::info("Found results table at index {$tableStartIndex} with {$tableRowCount} rows");

                    // Debug: check cell counts per row
                    foreach ($table->getTableRows() as $rowIdx => $row) {
                        $cellCount = count($row->getTableCells());
                        Log::info("  Row {$rowIdx}: {$cellCount} cells");
                    }
                    break;
                }
            }

            if (!$table) {
                Log::warning("Could not find results table at index {$tableStartIndex} for merging");
                return;
            }

            // Data starts at row 2 (after 2 header rows)
            $dataStartRow = 2;

            $requests = [];

            foreach ($sampleGroups as $group) {
                $startRow = $dataStartRow + $group['startRow'];

                Log::info("Merging sample group: startRow={$startRow}, rowSpan={$group['rowCount']}, columnIndex=0");

                // Merge column 0 (No.) vertically
                $requests[] = new Docs\Request([
                    'mergeTableCells' => [
                        'tableRange' => [
                            'tableCellLocation' => [
                                'tableStartLocation' => ['index' => $tableStartIndex],
                                'rowIndex' => $startRow,
                                'columnIndex' => 0,
                            ],
                            'rowSpan' => $group['rowCount'],
                            'columnSpan' => 1,
                        ],
                    ],
                ]);

                // Merge column 1 (Sample Name/Code) vertically
                $requests[] = new Docs\Request([
                    'mergeTableCells' => [
                        'tableRange' => [
                            'tableCellLocation' => [
                                'tableStartLocation' => ['index' => $tableStartIndex],
                                'rowIndex' => $startRow,
                                'columnIndex' => 1,
                            ],
                            'rowSpan' => $group['rowCount'],
                            'columnSpan' => 1,
                        ],
                    ],
                ]);
            }

            if (!empty($requests)) {
                Log::info("Executing " . count($requests) . " merge requests");
                $this->docsService->documents->batchUpdate(
                    $documentId,
                    new Docs\BatchUpdateDocumentRequest(['requests' => $requests])
                );
                Log::info("Successfully merged cells for " . count($sampleGroups) . " sample groups");
            }
        } catch (\Exception $e) {
            Log::error("Failed to merge LHP table cells: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            // Non-fatal - table is already populated, just not merged
        }
    }

    /**
     * Apply tight vertical padding to Parameter/Unit/Results/Method columns.
     * Reduces top/bottom cell padding to make rows more compact.
     */
    private function applyTightCellPadding(string $documentId, int $tableStartIndex, int $dataRowCount): void
    {
        try {
            $requests = [];
            $dataStartRow = 2; // After 2 header rows
            $columnsToTighten = [2, 3, 4, 5]; // Parameter, Unit, Results, Method
            $paddingPt = 2; // 2 points of padding (very tight)

            for ($rowIdx = $dataStartRow; $rowIdx < $dataStartRow + $dataRowCount; $rowIdx++) {
                foreach ($columnsToTighten as $colIdx) {
                    $requests[] = new Docs\Request([
                        'updateTableCellStyle' => [
                            'tableCellStyle' => [
                                'paddingTop' => ['magnitude' => $paddingPt, 'unit' => 'PT'],
                                'paddingBottom' => ['magnitude' => $paddingPt, 'unit' => 'PT'],
                            ],
                            'fields' => 'paddingTop,paddingBottom',
                            'tableRange' => [
                                'tableCellLocation' => [
                                    'tableStartLocation' => ['index' => $tableStartIndex],
                                    'rowIndex' => $rowIdx,
                                    'columnIndex' => $colIdx,
                                ],
                                'rowSpan' => 1,
                                'columnSpan' => 1,
                            ],
                        ],
                    ]);
                }
            }

            if (!empty($requests)) {
                $this->docsService->documents->batchUpdate(
                    $documentId,
                    new Docs\BatchUpdateDocumentRequest(['requests' => $requests])
                );
                Log::info("Applied tight padding to " . count($requests) . " cells");
            }
        } catch (\Exception $e) {
            Log::error("Failed to apply tight cell padding: " . $e->getMessage());
            // Non-fatal - table is already populated and merged
        }
    }

    /**
     * Set minimum row height for data rows.
     * Makes the table more compact by reducing row height.
     * 0.1 cm ≈ 2.83 PT, using 3 PT for minimal height.
     */
    private function applyMinimumRowHeight(string $documentId, int $tableStartIndex, int $dataRowCount): void
    {
        try {
            $requests = [];
            $dataStartRow = 2; // After 2 header rows
            $minHeightPt = 3; // 3 points ≈ 0.1 cm

            for ($rowIdx = $dataStartRow; $rowIdx < $dataStartRow + $dataRowCount; $rowIdx++) {
                $requests[] = new Docs\Request([
                    'updateTableRowStyle' => [
                        'tableRowStyle' => [
                            'minRowHeight' => ['magnitude' => $minHeightPt, 'unit' => 'PT'],
                        ],
                        'fields' => 'minRowHeight',
                        'tableStartLocation' => ['index' => $tableStartIndex],
                        'rowIndices' => [$rowIdx],
                    ],
                ]);
            }

            if (!empty($requests)) {
                $this->docsService->documents->batchUpdate(
                    $documentId,
                    new Docs\BatchUpdateDocumentRequest(['requests' => $requests])
                );
                Log::info("Applied minimum row height to " . count($requests) . " rows");
            }
        } catch (\Exception $e) {
            Log::error("Failed to apply minimum row height: " . $e->getMessage());
            // Non-fatal - table is already populated and merged
        }
    }

    /**
     * Upload a signature image to Google Drive, make it publicly readable,
     * and return the file ID and public URL.
     */
    public function uploadSignatureImage(string $imageContent, string $mimeType, string $fileName): array
    {
        $signaturesFolderId = env('GOOGLE_SIGNATURES_FOLDER_ID', $this->templatesFolderId);

        $file = new Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$signaturesFolderId],
        ]);

        $result = $this->driveService->files->create($file, [
            'data' => $imageContent,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        // Make publicly readable so Google Docs API can fetch it for replaceImage
        $permission = new Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $this->driveService->permissions->create($result->id, $permission);

        return [
            'id' => $result->id,
            'url' => "https://drive.google.com/uc?export=view&id={$result->id}",
        ];
    }

    /**
     * Replace an inline image in a Google Doc identified by its alt text.
     * The template must have a placeholder image with alt text matching $altText.
     */
    public function replaceImageByAltText(string $documentId, string $altText, string $imageUri): void
    {
        $doc = $this->docsService->documents->get($documentId);
        $inlineObjects = $doc->getInlineObjects();

        if (!$inlineObjects) {
            return;
        }

        foreach ($inlineObjects as $objectId => $inlineObject) {
            $embeddedObject = $inlineObject->getInlineObjectProperties()?->getEmbeddedObject();
            if (!$embeddedObject) {
                continue;
            }

            $title = $embeddedObject->getTitle() ?? '';
            $description = $embeddedObject->getDescription() ?? '';

            if ($title === $altText || $description === $altText) {
                $requests = [
                    new Docs\Request([
                        'replaceImage' => [
                            'imageObjectId' => $objectId,
                            'uri' => $imageUri,
                            'imageReplaceMethod' => 'CENTER_CROP',
                        ],
                    ]),
                ];

                $batchUpdateRequest = new Docs\BatchUpdateDocumentRequest([
                    'requests' => $requests,
                ]);

                $this->docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
                return;
            }
        }

        Log::warning("replaceImageByAltText: no image with alt text '{$altText}' found in doc {$documentId}");
    }

    /**
     * Patch the UPA signature onto the already-generated LHP doc.
     * Replaces text placeholders AND the {{UPA_SIGNATURE}} placeholder image.
     */
    public function patchLhpSignature(string $documentId, string $upaName, string $upaDate, ?string $signatureImageUri = null): void
    {
        $this->replacePlaceholders($documentId, [
            'UPA_NAME' => $upaName,
            'UPA_TANGGAL' => $upaDate,
        ]);

        if ($signatureImageUri) {
            $this->replaceImageByAltText($documentId, '{{UPA_SIGNATURE}}', $signatureImageUri);
        }
    }

    // Getter methods for folder IDs
    public function getSp3FolderId(): string
    {
        return $this->sp3FolderId;
    }

    public function getLhpFolderId(): string
    {
        return $this->lhpFolderId;
    }

    public function getTemplatesFolderId(): string
    {
        return $this->templatesFolderId;
    }
}