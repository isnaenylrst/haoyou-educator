<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LetterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    | Menampilkan:
    | - Statistik surat
    | - Daftar guru
    | - Riwayat surat
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil semua surat
        |--------------------------------------------------------------------------
        */

        $letters = Document::with([
            'uploader',
            'user'
        ])
        ->whereIn('document_type', [
            'SURAT',
            'SURAT_LIBUR',
            'SURAT_DINAS',
            'LOA',
        ])
        ->latest('uploaded_at')
        ->get();


        /*
        |--------------------------------------------------------------------------
        | Daftar guru
        |--------------------------------------------------------------------------
        */

        $teachers = Teacher::where('status', 'Active')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $totalLetters = Document::whereIn('document_type', [
            'SURAT_LIBUR',
            'SURAT_DINAS',
            'LOA',
        ])->count();


        $lettersThisMonth = Document::whereIn('document_type', [
            'SURAT_LIBUR',
            'SURAT_DINAS',
            'LOA',
        ])
        ->whereMonth('uploaded_at', now()->month)
        ->whereYear('uploaded_at', now()->year)
        ->count();


        /*
        |--------------------------------------------------------------------------
        | Belum Dibaca
        |--------------------------------------------------------------------------
        | Untuk sementara belum ada tabel pembacaan surat.
        |
        | Jadi nilainya 0.
        |
        | Nanti dapat dibuat sistem document_reads
        | agar setiap guru mempunyai status baca sendiri.
        |--------------------------------------------------------------------------
        */

        $unreadLetters = 0;


        return view(
            'kurikulum.surat',
            compact(
                'letters',
                'teachers',
                'totalLetters',
                'lettersThisMonth',
                'unreadLetters'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    | Menyimpan surat baru
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'jenis_surat' => [
                'required',
                Rule::in([
                    'SURAT_LIBUR',
                    'SURAT_DINAS',
                    'LOA',
                ]),
            ],

            'penerima' => [
                'required',
                Rule::in([
                    'SEMUA_GURU',
                    'GURU_TERTENTU',
                ]),
            ],

            'teacher_id' => [
                'nullable',
                'required_if:penerima,GURU_TERTENTU',
                'exists:teachers,id',
            ],

            'judul' => [
                'required',
                'string',
                'max:255',
            ],

            'isi' => [
                'nullable',
                'string',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],

        ]);


        try {

            DB::transaction(function () use ($request) {

                /*
                |--------------------------------------------------------------------------
                | Upload file
                |--------------------------------------------------------------------------
                */

                $filePath = $request
                    ->file('file')
                    ->store('documents', 'public');


                /*
                |--------------------------------------------------------------------------
                | Tentukan penerima
                |--------------------------------------------------------------------------
                */

                $userId = null;

                if (
                    $request->penerima === 'GURU_TERTENTU'
                    && $request->teacher_id
                ) {

                    $teacher = Teacher::findOrFail(
                        $request->teacher_id
                    );

                    $userId = $teacher->user_id;
                }


                /*
                |--------------------------------------------------------------------------
                | Simpan Document
                |--------------------------------------------------------------------------
                */

                Document::create([

                    /*
                    | Jika semua guru:
                    | user_id = null
                    |
                    | Jika guru tertentu:
                    | user_id = user_id guru tersebut
                    */

                    'user_id' => $userId,

                    'document_template_id' => null,

                    'title' => $request->judul,

                    /*
                    | Simpan jenis surat
                    */

                    'document_type' => $request->jenis_surat,

                    'description' => $request->isi,

                    'file_path' => $filePath,

                    /*
                    | Simpan tipe penerima
                    */

                    'visibility' => $request->penerima === 'SEMUA_GURU'
                        ? 'Teacher'
                        : 'Private',

                    'uploaded_by' => Auth::id(),

                    'uploaded_at' => now(),

                ]);

            });


            return redirect()
                ->route('kurikulum.surat')
                ->with(
                    'success',
                    'Surat berhasil dikirim.'
                );


        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal mengirim surat: ' . $e->getMessage()
                );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    | Menghapus surat dan file
    |--------------------------------------------------------------------------
    */

    public function destroy(Document $document)
    {
        /*
        |--------------------------------------------------------------------------
        | Pastikan hanya dokumen surat yang boleh dihapus dari halaman ini
        |--------------------------------------------------------------------------
        */

        if (
            !in_array($document->document_type, [
                'SURAT',
                'SURAT_LIBUR',
                'SURAT_DINAS',
                'LOA',
            ])
        ) {

            abort(404);

        }


        try {

            DB::transaction(function () use ($document) {

                /*
                |--------------------------------------------------------------------------
                | Hapus file
                |--------------------------------------------------------------------------
                */

                if (
                    $document->file_path &&
                    Storage::disk('public')->exists(
                        $document->file_path
                    )
                ) {

                    Storage::disk('public')->delete(
                        $document->file_path
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Hapus database
                |--------------------------------------------------------------------------
                */

                $document->delete();

            });


            return redirect()
                ->route('kurikulum.surat')
                ->with(
                    'success',
                    'Surat berhasil dihapus.'
                );


        } catch (\Exception $e) {

            return back()
                ->with(
                    'error',
                    'Gagal menghapus surat: ' . $e->getMessage()
                );

        }
    }
}