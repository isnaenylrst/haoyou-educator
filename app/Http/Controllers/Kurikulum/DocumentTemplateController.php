<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentTemplateController extends Controller
{
    /** Jenis kelas yang boleh punya template Progress Report. */
    public const CLASS_TYPES = [
        'Regular (Non-HSK)',
        'HSK 1',
        'HSK 2',
        'HSK 3',
        'HSK 4',
        'HSK 5',
        'HSK 6',
    ];

    private const MESSAGES = [
        'name.in'       => 'Jenis kelas tidak valid.',
        'name.unique'   => 'Template untuk jenis kelas ini sudah ada. Gunakan "Upload Ulang" pada template tersebut.',
        'file.required' => 'File template wajib diunggah.',
        'file.mimes'    => 'Template harus berformat DOC, DOCX, XLS, XLSX, atau PDF.',
        'file.max'      => 'Ukuran file maksimal 10 MB.',
        'file.uploaded' => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
    ];

    private const ATTRIBUTES = [
        'name'   => 'jenis kelas',
        'file'   => 'file template',
        'status' => 'status',
    ];

    /** Upload baru. Jika jenis kelas yang sama sudah ada, file lamanya DIGANTI (tidak dobel). */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', Rule::in(self::CLASS_TYPES)],
            'file' => ['required', 'file', 'mimes:doc,docx,xls,xlsx,pdf', 'max:10240'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $path = $request->file('file')->store('documents/templates', 'public');

        $template = DocumentTemplate::firstOrNew([
            'name'          => $request->name,
            'document_type' => 'Progress Report',
        ]);

        $replaced = $template->exists;
        $oldPath  = $replaced ? $template->file_path : null;

        try {
            $template->file_path   = $path;
            $template->uploaded_by = Auth::id();
            $template->status      = 'Active';
            $template->save();
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan template. Silakan coba lagi.');
        }

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('kurikulum.sop')->with(
            'success',
            $replaced ? 'Template berhasil diperbarui (file lama diganti).' : 'Template Progress Report berhasil ditambahkan.'
        );
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        abort_unless($documentTemplate->document_type === 'Progress Report', 404);

        $request->validate([
            'name' => [
                'required',
                Rule::in(self::CLASS_TYPES),
                Rule::unique('document_templates', 'name')
                    ->where('document_type', 'Progress Report')
                    ->ignore($documentTemplate->id),
            ],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'file'   => ['nullable', 'file', 'mimes:doc,docx,xls,xlsx,pdf', 'max:10240'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $oldPath = $documentTemplate->file_path;
        $newPath = $request->hasFile('file')
            ? $request->file('file')->store('documents/templates', 'public')
            : null;

        try {
            $documentTemplate->name   = $request->name;
            $documentTemplate->status = $request->status;
            if ($newPath) {
                $documentTemplate->file_path = $newPath;
            }
            $documentTemplate->uploaded_by = Auth::id();
            $documentTemplate->save();
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui template. Silakan coba lagi.');
        }

        if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('kurikulum.sop')->with('success', 'Template berhasil diperbarui.');
    }

    public function download(DocumentTemplate $documentTemplate)
    {
        abort_unless($documentTemplate->document_type === 'Progress Report', 404);

        if (!$documentTemplate->file_exists) {
            return back()->with('error', 'File template tidak ditemukan di server.');
        }

        $name = (Str::slug($documentTemplate->name) ?: 'template-' . $documentTemplate->id)
            . '.' . $documentTemplate->file_extension;

        return response()->download(storage_path('app/public/' . $documentTemplate->file_path), $name);
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        abort_unless($documentTemplate->document_type === 'Progress Report', 404);

        $path = $documentTemplate->file_path;

        try {
            $documentTemplate->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus template. Silakan coba lagi.');
        }

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('kurikulum.sop')->with('success', 'Template berhasil dihapus.');
    }
}