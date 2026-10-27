<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Support\Str;

class SopController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guru hanya BISA LIHAT & DOWNLOAD SOP dan Template Progress Report
    | yang sudah diupload Kurikulum. Tidak ada upload/edit/hapus di sisi Guru.
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $sops = Document::sop()->latest('uploaded_at')->get();

        $templates = DocumentTemplate::progressReport()->active()->orderBy('name')->get();

        return view('guru.sop', compact('sops', 'templates'));
    }

    public function show(Document $document)
    {
        abort_unless($document->document_type === 'SOP', 404);
        abort_unless($document->file_exists, 404, 'File tidak ditemukan di server.');

        return response()->file(storage_path('app/public/' . $document->file_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->sopName($document) . '"',
        ]);
    }

    public function download(Document $document)
    {
        abort_unless($document->document_type === 'SOP', 404);

        if (!$document->file_exists) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download(
            storage_path('app/public/' . $document->file_path),
            $this->sopName($document)
        );
    }

    public function template(DocumentTemplate $documentTemplate)
    {
        abort_unless(
            $documentTemplate->document_type === 'Progress Report' && $documentTemplate->status === 'Active',
            404
        );

        if (!$documentTemplate->file_exists) {
            return back()->with('error', 'File template tidak ditemukan.');
        }

        $name = (Str::slug($documentTemplate->name) ?: 'template-' . $documentTemplate->id)
            . '.' . $documentTemplate->file_extension;

        return response()->download(storage_path('app/public/' . $documentTemplate->file_path), $name);
    }

    private function sopName(Document $document): string
    {
        return (Str::slug($document->title) ?: 'sop-' . $document->id) . '.pdf';
    }
}