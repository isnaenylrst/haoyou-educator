<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\Document;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * "Folder" = nilai kolom documents.document_type, jadi memakai tabel yang
 * sama dengan fitur dokumen di sisi guru/siswa (tanpa tabel baru).
 */
class DokumenController extends Controller
{
    public const FOLDERS = [
        'cv'               => ['type' => 'CV',                   'label' => 'CV Karyawan',    'icon' => 'fa-id-card'],
        'sertifikat-guru'  => ['type' => 'Teacher Certificate',  'label' => 'Sertifikat Guru', 'icon' => 'fa-certificate'],
        'perjanjian'       => ['type' => 'Agreement',            'label' => 'Perjanjian',     'icon' => 'fa-file-signature'],
        'sertifikat-siswa' => ['type' => 'Certificate',          'label' => 'Sertifikat Siswa', 'icon' => 'fa-award'],
        'sop'              => ['type' => 'SOP',                  'label' => 'SOP',            'icon' => 'fa-list-check'],
        'surat-cuti'       => ['type' => 'Teacher Leave Letter', 'label' => 'Surat Cuti Guru', 'icon' => 'fa-envelope-open-text'],
        'foto'             => ['type' => 'Photo',                'label' => 'Foto',           'icon' => 'fa-image'],
        'lainnya'          => ['type' => 'Other',                'label' => 'Lainnya',        'icon' => 'fa-folder-open'],
    ];

    public const VISIBILITIES = [
        'Private' => 'Hanya admin',
        'Teacher' => 'Guru',
        'Student' => 'Siswa',
        'Public'  => 'Publik',
    ];

    public function index(Request $request)
    {
        $folder = $request->query('folder');
        if ($folder !== null && $folder !== '' && ! isset(self::FOLDERS[$folder])) {
            return redirect()->route('admin.dokumen');
        }
        $folder = $folder ?: null;

        $search = trim((string) $request->query('search', ''));
        $ownerId = $request->integer('owner_id') ?: null;

        $counts = Document::selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        $stats = [
            'total'    => (int) $counts->sum(),
            'folder'   => $counts->filter()->count(),
            'cv'       => (int) $counts->get('CV', 0),
            'bulan_ini' => Document::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)->count(),
        ];

        $documents = Document::query()
            ->with(['user.teacher', 'user.curriculum', 'user.student', 'uploader'])
            ->when($folder, fn ($q) => $q->where('document_type', self::FOLDERS[$folder]['type']))
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($ownerId, fn ($q) => $q->where('user_id', $ownerId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.dokumen', [
            'folders'      => self::FOLDERS,
            'visibilities' => self::VISIBILITIES,
            'folder'       => $folder,
            'counts'       => $counts,
            'stats'        => $stats,
            'documents'    => $documents,
            'owners'       => $this->ownerOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'folder'      => ['required', Rule::in(array_keys(self::FOLDERS))],
            'title'       => ['required', 'string', 'max:255'],
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'visibility'  => ['required', Rule::in(array_keys(self::VISIBILITIES))],
            'description' => ['nullable', 'string', 'max:1000'],
            'file'        => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'user_id'       => $data['user_id'] ?? null,
            'title'         => $data['title'],
            'document_type' => self::FOLDERS[$data['folder']]['type'],
            'description'   => $data['description'] ?? null,
            'file_path'     => $path,
            'visibility'    => $data['visibility'],
            'uploaded_by'   => $request->user()->id,
            'uploaded_at'   => now(),
        ]);

        return redirect()
            ->route('admin.dokumen', ['folder' => $data['folder']])
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    /** Ubah info dokumen / pindah folder (file tidak diganti). */
    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'folder'      => ['required', Rule::in(array_keys(self::FOLDERS))],
            'title'       => ['required', 'string', 'max:255'],
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'visibility'  => ['required', Rule::in(array_keys(self::VISIBILITIES))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $document->update([
            'user_id'       => $data['user_id'] ?? null,
            'title'         => $data['title'],
            'document_type' => self::FOLDERS[$data['folder']]['type'],
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

    /** Pemilik dokumen karyawan: guru & tim kurikulum (value = users.id). */
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
