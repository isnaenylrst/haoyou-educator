<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Upload Template Progress Report
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'file' => 'required|mimes:doc,docx,xls,xlsx,pdf|max:10240',
        ]);

        $path = $request->file('file')
            ->store('documents/templates', 'public');

        DocumentTemplate::create([

            'name' => $request->name,

            'document_type' => 'Progress Report',

            'file_path' => $path,

            'uploaded_by' => Auth::id(),

            'status' => 'Active',

        ]);

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'Template Progress Report berhasil diupload.');
    }

    /*
    |--------------------------------------------------------------------------
    | Download Template
    |--------------------------------------------------------------------------
    */

    public function download(DocumentTemplate $documentTemplate)
    {
        if (!Storage::disk('public')->exists($documentTemplate->file_path)) {

            return back()->with('error', 'File tidak ditemukan.');

        }

        return response()->download(

            Storage::disk('public')->path($documentTemplate->file_path),

            basename($documentTemplate->file_path)

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Template
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $request->validate([

            'name' => 'required|max:255',

            'file' => 'nullable|mimes:doc,docx,xls,xlsx,pdf|max:10240',

        ]);

        $documentTemplate->name = $request->name;

        if ($request->hasFile('file')) {

            if (
                $documentTemplate->file_path &&
                Storage::disk('public')->exists($documentTemplate->file_path)
            ) {

                Storage::disk('public')->delete($documentTemplate->file_path);

            }

            $documentTemplate->file_path = $request->file('file')
                ->store('documents/templates', 'public');
        }

        $documentTemplate->uploaded_by = Auth::id();

        $documentTemplate->save();

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'Template berhasil diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus Template
    |--------------------------------------------------------------------------
    */

    public function destroy(DocumentTemplate $documentTemplate)
    {

        if (
            $documentTemplate->file_path &&
            Storage::disk('public')->exists($documentTemplate->file_path)
        ) {

            Storage::disk('public')->delete($documentTemplate->file_path);

        }

        $documentTemplate->delete();

        return redirect()
            ->route('kurikulum.sop')
            ->with('success', 'Template berhasil dihapus.');
    }
}