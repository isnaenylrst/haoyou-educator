<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LetterController extends Controller
{
    private const PER_PAGE = 10;

    private const MESSAGES = [
        'jenis_surat.required' => 'Pilih jenis surat.',
        'teacher_ids.required_if' => 'Pilih minimal satu guru penerima.',
        'teacher_ids.min'      => 'Pilih minimal satu guru penerima.',
        'judul.required'       => 'Judul surat wajib diisi.',
        'file.required'        => 'File surat wajib diunggah.',
        'file.mimes'           => 'File harus berformat PDF, JPG, JPEG, atau PNG.',
        'file.max'             => 'Ukuran file maksimal 10 MB.',
        'file.uploaded'        => 'Upload gagal. Periksa upload_max_filesize dan post_max_size di php.ini.',
    ];

    private const ATTRIBUTES = [
        'jenis_surat' => 'jenis surat',
        'teacher_ids' => 'guru penerima',
        'judul'       => 'judul surat',
        'isi'         => 'isi surat',
        'file'        => 'file surat',
    ];

    /** Pastikan dokumen yang diakses memang surat, bukan SOP atau dokumen lain. */
    private function letter(Document $document): Document
    {
        abort_unless(in_array($document->document_type, Document::LETTER_TYPES, true), 404);

        return $document;
    }

    private function fileName(Document $document): string
    {
        return (Str::slug($document->title) ?: 'surat-' . $document->id) . '.' . $document->file_extension;
    }

    // ------------------------------------------------------------------ LIST

    public function index(Request $request)
    {
        $request->validate([
            'type' => ['nullable', Rule::in(Document::LETTER_TYPES)],
            'q'    => ['nullable', 'string', 'max:100'],
        ]);

        $type = $request->query('type');
        $q    = trim((string) $request->query('q', ''));

        $letters = Document::letters()
            ->with(['uploader', 'recipients.teacher'])
            ->withCount([
                'recipients as recipients_total',
                'recipients as recipients_read' => fn ($x) => $x->whereNotNull('document_recipients.read_at'),
            ])
            ->when($type, fn ($x) => $x->where('document_type', $type))
            ->when($q !== '', fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
            }))
            ->latest('uploaded_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $teachers = Teacher::where('status', 'Active')
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->get();

        $teachersWithoutAccount = Teacher::where('status', 'Active')->whereNull('user_id')->count();

        $totalLetters     = Document::letters()->count();
        $lettersThisMonth = Document::letters()
            ->whereMonth('uploaded_at', now()->month)
            ->whereYear('uploaded_at', now()->year)
            ->count();

        // Jumlah penerima yang belum membuka suratnya
        $unreadLetters = DB::table('document_recipients as r')
            ->join('documents as d', 'd.id', '=', 'r.document_id')
            ->whereIn('d.document_type', Document::LETTER_TYPES)
            ->whereNull('r.read_at')
            ->count();

        return view('kurikulum.surat', compact(
            'letters', 'teachers', 'teachersWithoutAccount',
            'totalLetters', 'lettersThisMonth', 'unreadLetters', 'type', 'q'
        ));
    }

    // ------------------------------------------------------------------ KIRIM

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis_surat' => ['required', Rule::in(Document::LETTER_TYPES)],
            'penerima'    => ['required', Rule::in(['SEMUA_GURU', 'GURU_TERTENTU'])],
            'teacher_ids' => ['nullable', 'required_if:penerima,GURU_TERTENTU', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'judul'       => ['required', 'string', 'max:255'],
            'isi'         => ['nullable', 'string', 'max:5000'],
            'file'        => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], self::MESSAGES, self::ATTRIBUTES);

        // Tentukan guru penerima (hanya guru aktif yang akunnya terhubung)
        $teachers = Teacher::where('status', 'Active')
            ->whereNotNull('user_id')
            ->when($data['penerima'] === 'GURU_TERTENTU', fn ($x) => $x->whereIn('id', $data['teacher_ids'] ?? []))
            ->get();

        if ($teachers->isEmpty()) {
            return back()->withInput()->withErrors([
                'teacher_ids' => 'Tidak ada guru penerima yang aktif dan akun loginnya terhubung.',
            ]);
        }

        $userIds = $teachers->pluck('user_id')->unique()->values();
        $path    = $request->file('file')->store('documents', 'public');

        try {
            DB::transaction(function () use ($data, $path, $userIds) {
                $document = Document::create([
                    // satu penerima: user_id diisi (kompatibel data lama); banyak: null
                    'user_id'              => $data['penerima'] === 'GURU_TERTENTU' && $userIds->count() === 1 ? $userIds->first() : null,
                    'document_template_id' => null,
                    'title'                => trim($data['judul']),
                    'document_type'        => $data['jenis_surat'],
                    'description'          => $data['isi'] ?? null,
                    'file_path'            => $path,
                    'visibility'           => $data['penerima'] === 'SEMUA_GURU' ? 'Teacher' : 'Private',
                    'uploaded_by'          => Auth::id(),
                    'uploaded_at'          => now(),
                ]);

                $document->recipients()->attach($userIds->all());
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);   // jangan sisakan file yatim
            report($e);

            return back()->withInput()->with('error', 'Gagal mengirim surat. Silakan coba lagi.');
        }

        return redirect()->route('kurikulum.surat')
            ->with('success', 'Surat berhasil dikirim ke ' . $userIds->count() . ' guru.');
    }

    // ------------------------------------------------------------------ UBAH

    /** Ubah judul, jenis, isi, atau ganti file. Jika file diganti, status baca direset. */
    public function update(Request $request, Document $document)
    {
        $this->letter($document);

        $data = $request->validate([
            'jenis_surat' => ['required', Rule::in(Document::LETTER_TYPES)],
            'judul'       => ['required', 'string', 'max:255'],
            'isi'         => ['nullable', 'string', 'max:5000'],
            'file'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $oldPath = $document->file_path;
        $newPath = $request->hasFile('file') ? $request->file('file')->store('documents', 'public') : null;

        try {
            DB::transaction(function () use ($document, $data, $newPath) {
                $attrs = [
                    'document_type' => $data['jenis_surat'],
                    'title'         => trim($data['judul']),
                    'description'   => $data['isi'] ?? null,
                ];

                if ($newPath) {
                    $attrs['file_path']   = $newPath;
                    $attrs['uploaded_at'] = now();
                }

                $document->update($attrs);

                if ($newPath) {
                    // Surat versi baru: guru perlu membukanya lagi
                    DB::table('document_recipients')
                        ->where('document_id', $document->id)
                        ->update(['read_at' => null, 'updated_at' => now()]);
                }
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui surat. Silakan coba lagi.');
        }

        if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', $newPath
            ? 'Surat diperbarui. Karena file diganti, status baca guru direset.'
            : 'Surat diperbarui.');
    }

    // ------------------------------------------------------------------ HAPUS

    public function destroy(Document $document)
    {
        $this->letter($document);

        $path = $document->file_path;

        try {
            $document->delete();   // baris penerima ikut terhapus (cascade)
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal menghapus surat. Silakan coba lagi.');
        }

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('kurikulum.surat')->with('success', 'Surat berhasil dihapus.');
    }

    // ------------------------------------------------------------------ BERKAS (butuh login)

    public function file(Document $document)
    {
        $this->letter($document);
        abort_unless($document->file_exists, 404, 'File surat tidak ditemukan di server.');

        return response()->file(storage_path('app/public/' . $document->file_path));
    }

    public function download(Document $document)
    {
        $this->letter($document);

        if (!$document->file_exists) {
            return back()->with('error', 'File surat tidak ditemukan di server.');
        }

        return response()->download(storage_path('app/public/' . $document->file_path), $this->fileName($document));
    }
}