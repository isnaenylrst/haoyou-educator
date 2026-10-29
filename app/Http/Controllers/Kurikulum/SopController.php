<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SopController extends Controller
{
    private const MESSAGES = [
        'title.unique'  => 'Judul SOP ini sudah ada. Gunakan judul lain, atau pakai "Upload Ulang" pada SOP yang sama.',
        'file.required' => 'File SOP wajib diunggah.',
        'file.mimes'    => 'File SOP harus berformat PDF.',
        'file.max'      => 'Ukuran file maksimal 5 MB.',
        'file.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
    ];

    private const ATTRIBUTES = [
        'title' => 'judul SOP',
        'file'  => 'file SOP',
    ];

    /** Pastikan dokumen yang diakses memang SOP, bukan surat/dokumen lain. */
    private function sop(Document $document): Document
    {
        abort_unless($document->document_type === 'SOP', 404);

        return $document;
    }

    private function titleRules(?Document $document = null): array
    {
        return [
            'required', 'string', 'max:255',
            Rule::unique('documents', 'title')
                ->where('document_type', 'SOP')
                ->ignore($document?->id),
        ];
    }

    private function fileName(Document $document): string
    {
        return (Str::slug($document->title) ?: 'sop-' . $document->id) . '.pdf';
    }

    // ------------------------------------------------------------------ LIST

    public function index()
    {
        $sops = Document::sop()->with('uploader')->latest('uploaded_at')->get();

        $templates = DocumentTemplate::progressReport()
            ->with('uploader')
            ->orderBy('name')
            ->get();

        $classTypes = DocumentTemplateController::CLASS_TYPES;

        return view('kurikulum.sop', compact('sops', 'templates', 'classTypes'));
    }

    // ------------------------------------------------------------------ SIMPAN

    public function store(Request $request)
    {
        $request->validate([
            'title' => $this->titleRules(),
            'file'  => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $path = $request->file('file')->store('documents/sop', 'public');

        try {
            Document::create([
                'title'         => trim($request->title),
                'document_type' => 'SOP',
                'file_path'     => $path,
                'visibility'    => 'Teacher',
                'uploaded_by'   => Auth::id(),
                'uploaded_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);   // jangan sisakan file yatim
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan SOP. Silakan coba lagi.');
        }

        return redirect()->route('kurikulum.sop')->with('success', 'SOP berhasil diupload.');
    }

    public function update(Request $request, Document $document)
    {
        $this->sop($document);

        $request->validate([
            'title' => $this->titleRules($document),
            'file'  => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $oldPath = $document->file_path;
        $newPath = $request->hasFile('file')
            ? $request->file('file')->store('documents/sop', 'public')
            : null;

        try {
            $document->title = trim($request->title);
            if ($newPath) {
                $document->file_path = $newPath;
            }
            $document->uploaded_by = Auth::id();
            $document->uploaded_at = now();
            $document->save();
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui SOP. Silakan coba lagi.');
        }

        // file lama dihapus setelah database sukses
        if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('kurikulum.sop')->with('success', 'SOP berhasil diperbarui.');
    }

    public function destroy(Document $document)
    {
        $this->sop($document);

        $path = $document->file_path;

        try {
            $document->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus SOP. Silakan coba lagi.');
        }

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('kurikulum.sop')->with('success', 'SOP berhasil dihapus.');
    }

    // ------------------------------------------------------------------ FILE

    /** Buka PDF di browser (butuh login). */
    public function show(Document $document)
    {
        $this->sop($document);
        abort_unless($document->file_exists, 404, 'File SOP tidak ditemukan di server.');

        return response()->file(storage_path('app/public/' . $document->file_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->fileName($document) . '"',
        ]);
    }

    public function download(Document $document)
    {
        $this->sop($document);

        if (!$document->file_exists) {
            return back()->with('error', 'File SOP tidak ditemukan di server.');
        }

        return response()->download(
            storage_path('app/public/' . $document->file_path),
            $this->fileName($document)
        );
    }
}