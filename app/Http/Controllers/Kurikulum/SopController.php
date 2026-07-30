<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Menampilkan halaman SOP
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $sops = Document::where('document_type', 'SOP')
            ->latest()
            ->get();
            $templates = DocumentTemplate::where(
            'template_type',
            'Progress Report'
        )
        ->where('status', 'Active')
        ->latest()
        ->get();

    return view(
        'kurikulum.sop',
        compact(
            'sops',
            'templates'
        )
    );
    }

    /*
    |--------------------------------------------------------------------------
    | Upload SOP Baru
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'file'  => 'required|mimes:pdf|max:5120',
        ]);

        $path = $request->file('file')
            ->store('documents/sop', 'public');

        Document::create([

            'title'         => $request->title,

            'document_type' => 'SOP',

            'file_path'     => $path,

            'visibility'    => 'Teacher',

            'uploaded_by'   => Auth::id(),

            'user_id'       => Auth::id(),

            'uploaded_at'   => now(),

        ]);

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'SOP berhasil diupload.');
    }

    /*
    |--------------------------------------------------------------------------
    | Download / Buka SOP
    |--------------------------------------------------------------------------
    */

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {

            return back()->with('error', 'File tidak ditemukan.');

        }

        return response()->download(
            Storage::disk('public')->path($document->file_path),
            $document->title . '.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mengambil data SOP untuk edit
    |--------------------------------------------------------------------------
    */

    public function edit(Document $document)
    {
        return response()->json($document);
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Ulang SOP
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Document $document)
    {

        $request->validate([

            'title' => 'required|max:255',

            'file' => 'nullable|mimes:pdf|max:5120',

        ]);

        $document->title = $request->title;

        if ($request->hasFile('file')) {

            if (
                $document->file_path &&
                Storage::disk('public')->exists($document->file_path)
            ) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->file_path = $request->file('file')
                ->store('documents/sop', 'public');
        }

        $document->uploaded_by = Auth::id();

        $document->uploaded_at = now();

        $document->save();

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'SOP berhasil diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus SOP
    |--------------------------------------------------------------------------
    */

    public function destroy(Document $document)
    {

        if (
            $document->file_path &&
            Storage::disk('public')->exists($document->file_path)
        ) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'SOP berhasil dihapus.');
    }
}