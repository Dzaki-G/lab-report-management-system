<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormPengujian;
use App\Services\SpuDocumentService;
use Illuminate\Support\Facades\Log;

class SpuController extends Controller
{
    protected \App\Services\GoogleDocsService $googleDocsService;

    public function __construct(\App\Services\GoogleDocsService $googleDocsService)
    {
        $this->googleDocsService = $googleDocsService;
    }

    /**
     * Generate SPU document for a form (Manual Trigger)
     */
    public function generate(FormPengujian $form)
    {
        try {
            // Generate the document (Unsigned)
            $result = $this->googleDocsService->generateSpuWithTable($form);
            
            // Update the form with document info
            $form->update([
                'spu_unsigned_doc_id' => $result['id'],
                'spu_generated_at' => now(),
            ]);
            
            return back()->with('success', 'SPU (Google Docs) berhasil di-generate!');
            
        } catch (\Exception $e) {
            Log::error('Failed to generate SPU: ' . $e->getMessage());
            return back()->with('error', 'Gagal generate SPU: ' . $e->getMessage());
        }
    }

    /**
     * View/Download SPU document
     * Redirects to Google Docs
     */
    public function download(FormPengujian $form)
    {
        // Prioritize Signed SPU if exists
        if ($form->spu_signed_doc_id) {
            $url = "https://docs.google.com/document/d/{$form->spu_signed_doc_id}/edit";
            return redirect()->away($url);
        }

        // Fallback to Unsigned SPU
        if ($form->spu_unsigned_doc_id) {
            $url = "https://docs.google.com/document/d/{$form->spu_unsigned_doc_id}/edit";
            return redirect()->away($url);
        }

        return back()->with('error', 'Dokumen SPU belum tersedia.');
    }
    
    /**
     * View Unsigned SPU specifically
     */
    public function viewUnsigned(FormPengujian $form)
    {
        if ($form->spu_unsigned_doc_id) {
            $url = "https://docs.google.com/document/d/{$form->spu_unsigned_doc_id}/edit";
            return redirect()->away($url);
        }
        return back()->with('error', 'SPU Unsigned belum tersedia.');
    }

    /**
     * Regenerate SPU document
     */
    public function regenerate(FormPengujian $form)
    {
        // Note: We don't delete the old file in Google Drive automatically to be safe,
        // or we could implementing delete logic in Service.
        // For now, simple regenerate and update reference.
        
        return $this->generate($form);
    }
}
