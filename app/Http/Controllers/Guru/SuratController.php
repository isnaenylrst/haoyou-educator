<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuratController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Surat dari Kurikulum untuk guru yang sedang login.
    | Guru hanya bisa membuka surat yang ditujukan kepadanya, dan setiap
    | kali membuka, surat otomatis ditandai sudah dibaca.
    |--------------------------------------------------------------------------
    */

    private function guard(): void
    {
        abort_if(!Auth::user()->teacher, 403, 'Akun Anda belum terhubung dengan data guru.');
    }

    /** Pastikan surat ini memang untuk guru yang login. */
    private function myLetter(Document $document): Document
    {
        $this->guard();

        abort_unless(in_array($document->document_type, Document::LETTER_TYPES, true), 404);

        $isRecipient = DB::table('document_recipients')
            ->where('document_id', $document->id)
            ->where('user_id', Auth::id())
            ->exists();

        abort_unless($isRecipient, 403, 'Surat ini bukan untuk Anda.');

        return $document;
    }

    private function markRead(Document $document): void
    {
        DB::table('document_recipients')
            ->where('document_id', $document->id)
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    private function fileName(Document $document): string
    {
        return (Str::slug($document->title) ?: 'surat-' . $document->id) . '.' . $document->file_extension;
    }

    // ------------------------------------------------------------------ DAFTAR

    public function index(Request $request)
    {
        $this->guard();

        $request->validate([
            'status' => ['nullable', Rule::in(['unread'])],
            'type'   => ['nullable', Rule::in(Document::LETTER_TYPES)],
        ]);

        $status = $request->query('status');
        $type   = $request->query('type');

        $letters = Document::letters()
            ->join('document_recipients as r', 'r.document_id', '=', 'documents.id')
            ->where('r.user_id', Auth::id())
            ->select('documents.*', 'r.read_at as my_read_at')
            ->with('uploader')
            ->when($status === 'unread', fn ($q) => $q->whereNull('r.read_at'))
            ->when($type, fn ($q) => $q->where('documents.document_type', $type))
            ->orderByRaw('r.read_at IS NOT NULL')        // yang belum dibuka di atas
            ->orderByDesc('documents.uploaded_at')
            ->paginate(10)
            ->withQueryString();

        $unreadCount = Document::unreadLettersFor(Auth::id());

        return view('guru.surat', compact('letters', 'unreadCount', 'status', 'type'));
    }

    // ------------------------------------------------------------------ BUKA / UNDUH

    public function show(Document $document)
    {
        $this->myLetter($document);
        abort_unless($document->file_exists, 404, 'File surat tidak ditemukan di server.');

        $this->markRead($document);

        return response()->file(storage_path('app/public/' . $document->file_path));
    }

    public function download(Document $document)
    {
        $this->myLetter($document);

        if (!$document->file_exists) {
            return back()->with('error', 'File surat tidak ditemukan di server.');
        }

        $this->markRead($document);

        return response()->download(storage_path('app/public/' . $document->file_path), $this->fileName($document));
    }

    // ------------------------------------------------------------------ TANDAI DIBACA

    public function read(Document $document)
    {
        $this->myLetter($document);
        $this->markRead($document);

        return back()->with('success', 'Surat ditandai sudah dibaca.');
    }

    public function readAll()
    {
        $this->guard();

        DB::table('document_recipients')
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Semua surat ditandai sudah dibaca.');
    }
}