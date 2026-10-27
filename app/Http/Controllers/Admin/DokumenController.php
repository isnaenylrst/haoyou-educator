<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * "Folder" = baris di tabel document_folders. Dokumen terhubung lewat
 * documents.document_type = document_folders.document_type.
 */
class DokumenController extends Controller
{
    public const VISIBILITIES = [
        'Private' => 'Hanya admin',
        'Teacher' => 'Guru',
        'Student' => 'Siswa',
        'Public'  => 'Publik',
    ];

    public const ICONS = [
        'fa-folder'        => 'Folder',
        'fa-file-lines'    => 'Dokumen',
        'fa-file-signature' => 'Perjanjian',
        'fa-certificate'   => 'Sertifikat',
        'fa-envelope-open-text' => 'Surat',
        'fa-image'         => 'Foto',
        'fa-chart-line'    => 'Laporan',
        'fa-book'          => 'Buku / Materi',
    ];

    public function index(Request $request)
    {
        $folders = DocumentFolder::orderBy('id')->get()->keyBy('slug');

        $folder = $request->query('folder') ?: null;
        if ($folder !== null && ! $folders->has($folder)) {
            return redirect()->route('admin.dokumen');
        }

        $counts = Document::selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        $current = $folder ? $folders[$folder] : null;
        $documents = null;
        $search = trim((string) $request->query('search', ''));
        $ownerId = $request->integer('owner_id') ?: null;

        if ($current) {
            $documents = Document::query()
                ->with(['user.teacher', 'user.curriculum', 'user.student', 'uploader'])
                ->where('document_type', $current->document_type)
                ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }))
                ->when($ownerId, fn ($q) => $q->where('user_id', $ownerId))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.dokumen', [
            'folders'      => $folders,
            'current'      => $current,
            'folder'       => $folder,
            'counts'       => $counts,
            'documents'    => $documents,
            'visibilities' => self::VISIBILITIES,
            'icons'        => self::ICONS,
            'owners'       => $this->ownerOptions(),
        ]);
    }

    /* ---------------------------- FOLDER ---------------------------- */

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:60', 'unique:document_folders,label'],
            'icon'  => ['nullable', Rule::in(array_keys(self::ICONS))],
        ]);

        $base = Str::slug($data['label']) ?: 'folder';
        $slug = $base;
        $i = 2;
        while (DocumentFolder::where('slug', $slug)->orWhere('document_type', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        DocumentFolder::create([
            'slug'          => $slug,
            'label'         => $data['label'],
            'icon'          => $data['icon'] ?? 'fa-folder',
            'document_type' => $slug,
        ]);

        return redirect()->route('admin.dokumen')->with('success', 'Folder berhasil dibuat.');
    }

    public function destroyFolder(DocumentFolder $folder)
    {
        if (Document::where('document_type', $folder->document_type)->exists()) {
            return back()->with('error', 'Folder masih berisi dokumen. Kosongkan dulu sebelum dihapus.');
        }

        $folder->delete();

        return redirect()->route('admin.dokumen')->with('success', 'Folder dihapus.');
    }

    /* --------------------------- DOKUMEN ---------------------------- */

    public function store(Request $request)
    {
        $data = $request->validate([
            'folder'      => ['required', 'exists:document_folders,slug'],
            'title'       => ['required', 'string', 'max:255'],
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'visibility'  => ['required', Rule::in(array_keys(self::VISIBILITIES))],
            'description' => ['nullable', 'string', 'max:1000'],
            'file'        => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png'],
        ]);

        $folder = DocumentFolder::where('slug', $data['folder'])->firstOrFail();
        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'user_id'       => $data['user_id'] ?? null,
            'title'         => $data['title'],
            'document_type' => $folder->document_type,
            'description'   => $data['description'] ?? null,
            'file_path'     => $path,
            'visibility'    => $data['visibility'],
            'uploaded_by'   => $request->user()->id,
            'uploaded_at'   => now(),
        ]);

        return redirect()
            ->route('admin.dokumen', ['folder' => $folder->slug])
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    /** Ubah info dokumen / pindah folder (file tidak diganti). */
    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'folder'      => ['required', 'exists:document_folders,slug'],
            'title'       => ['required', 'string', 'max:255'],
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'visibility'  => ['required', Rule::in(array_keys(self::VISIBILITIES))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $folder = DocumentFolder::where('slug', $data['folder'])->firstOrFail();

        $document->update([
            'user_id'       => $data['user_id'] ?? null,
            'title'         => $data['title'],
            'document_type' => $folder->document_type,
            'description'   => $data['description'] ?? null,
            'visibility'    => $data['visibility'],
        ]);

        return back()->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Document $document)
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }

    public function lihat(Document $document)
    {
        if (! $this->fileExists($document)) {
            return $this->missingFile();
        }

        return Storage::disk('public')->response($document->file_path, $this->fileName($document));
    }

    public function unduh(Document $document)
    {
        if (! $this->fileExists($document)) {
            return $this->missingFile();
        }

        return Storage::disk('public')->download($document->file_path, $this->fileName($document));
    }

    /* ------------------------------------------------------------------ */

    private function ownerOptions(): array
    {
        return [
            'Guru'      => Teacher::orderBy('name')->get(['user_id', 'name']),
            'Kurikulum' => Curriculum::orderBy('name')->get(['user_id', 'name']),
        ];
    }

    private function fileExists(Document $document): bool
    {
        return $document->file_path && Storage::disk('public')->exists($document->file_path);
    }

    private function missingFile()
    {
        return back()->with('error', 'File dokumen tidak ditemukan di penyimpanan.');
    }

    private function fileName(Document $document): string
    {
        $ext = pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Str::slug($document->title) . ($ext ? '.' . $ext : '');
    }
}