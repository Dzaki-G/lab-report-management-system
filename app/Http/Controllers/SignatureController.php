<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleDocsService;

class SignatureController extends Controller
{
    public function show()
    {
        return view('profile.signature', [
            'hasSignature' => (bool) auth()->user()->signature_drive_file_id,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'signature.required' => 'File tanda tangan wajib dipilih.',
            'signature.image'    => 'File harus berupa gambar.',
            'signature.mimes'    => 'Format file harus PNG, JPG, atau JPEG.',
            'signature.max'      => 'Ukuran file maksimal 2MB.',
        ]);

        $file = $request->file('signature');
        $mimeType = $file->getMimeType();
        $content  = file_get_contents($file->getRealPath());
        $fileName = 'signature_' . auth()->user()->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();

        $googleDocsService = new GoogleDocsService();

        // Delete old signature from Drive if one exists
        $user = auth()->user();
        if ($user->signature_drive_file_id) {
            $googleDocsService->deleteFile($user->signature_drive_file_id);
        }

        $result = $googleDocsService->uploadSignatureImage($content, $mimeType, $fileName);

        $user->update(['signature_drive_file_id' => $result['id']]);

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function destroy()
    {
        $user = auth()->user();
        if ($user->signature_drive_file_id) {
            $googleDocsService = new GoogleDocsService();
            $googleDocsService->deleteFile($user->signature_drive_file_id);
            $user->update(['signature_drive_file_id' => null]);
        }

        return back()->with('success', 'Tanda tangan berhasil dihapus.');
    }
}
