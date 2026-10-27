<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SopController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guru hanya BISA LIHAT & DOWNLOAD SOP dan Template Progress Report
    | yang sudah diupload Kurikulum. Tidak ada upload/edit/hapus di sisi Guru.
    |
    | Aturan akses SOP (daftar, buka, unduh): bertipe SOP DAN visibilitas Teacher/Public.
    | SOP berstatus Private/Student tidak tampil dan tidak bisa dibuka lewat URL.
    |--------------------------------------------------------------------------
    */

    private const PER_PAGE = 12;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $sops = Document::sopForTeachers()
            ->when($q !== '', function ($query) use ($q) {
                // escape % _ \ supaya input dicari apa adanya, bukan sebagai wildcard SQL
                $query->where('title', 'like', '%' . addcslashes($q, '%_\\') . '%');
            })
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $total = Document::sopForTeachers()->count();

        $templates = DocumentTemplate::progressReport()->active()->orderBy('name')->get();

        return view('guru.sop', compact('sops', 'templates', 'q', 'total'));
    }

    public function show(Document $document)
    {
        $this->authorizeSop($document);
        abort_unless($document->file_exists, 404, 'File tidak ditemukan di server.');

        return response()->file(Storage::disk('public')->path($document->file_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->sopName($document) . '"',
        ]);
    }

    public function download(Document $document)
    {
        $this->authorizeSop($document);

        if (!$document->file_exists) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download(
            Storage::disk('public')->path($document->file_path),
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

        return response()->download(Storage::disk('public')->path($documentTemplate->file_path), $name);
    }

    /** Hanya SOP yang memang ditujukan untuk guru (bukan dokumen lain, bukan SOP Private/Student). */
    private function authorizeSop(Document $document): void
    {
        abort_unless($document->isSopForTeachers(), 404);
    }

    private function sopName(Document $document): string
    {
        return (Str::slug($document->title) ?: 'sop-' . $document->id) . '.pdf';
    }
}