<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guru hanya BISA LIHAT & DOWNLOAD SOP yang sudah diupload Kurikulum.
    | Tidak ada upload/edit/hapus di sisi Guru.
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $sops = Document::where('document_type', 'SOP')
            ->latest('uploaded_at')
            ->get();

        return view('guru.sop', compact('sops'));
    }

    public function show(Document $document)
    {
        if ($document->document_type !== 'SOP') {
            abort(404);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        return response()->file(
            Storage::disk('public')->path($document->file_path)
        );
    }

    public function download(Document $document)
    {
        if ($document->document_type !== 'SOP') {
            abort(404);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download(
            Storage::disk('public')->path($document->file_path),
            $document->title . '.pdf'
        );
    }
}