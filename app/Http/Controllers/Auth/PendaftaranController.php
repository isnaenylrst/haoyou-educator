<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PendaftaranController extends Controller
{
    /**
     * Tampilkan Formulir Pendaftaran (calon siswa baru / leads).
     */
    public function create()
    {
        return view('auth.daftar');
    }

    /**
     * Simpan data calon siswa ke tabel candidate_students.
     * Sesuai flowchart: "Siswa Submit Formulir Pendaftaran" ->
     * "Selesai (Jalur Pendaftaran): Data Terkirim, Menunggu Verifikasi &
     *  menunggu Pemberian Akses oleh Admin (Manual/Offline)"
     *
     * status_lead & status_trial TIDAK diisi dari form publik — nilainya
     * otomatis default 'Inquiry' & 'Belum' sesuai migration, lalu dikelola
     * oleh Admin/CRM setelah ini (follow up, jadwalkan trial, dst).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date'],
            'no_hp' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'nama_ortu' => ['nullable', 'string', 'max:150'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'sekolah' => ['nullable', 'string', 'max:150'],
            'sumber' => ['nullable', 'string', 'max:100'],
            'kebutuhan_belajar' => ['required', 'string', 'max:255'],
            'available_schedule' => ['nullable', 'string', 'max:2000'],
            'alergi' => ['nullable', 'string', 'max:150'],
        ]);

        // Hitung usia otomatis dari tanggal_lahir jika diisi
        $usia = null;
        if (! empty($validated['tanggal_lahir'])) {
            $usia = Carbon::parse($validated['tanggal_lahir'])->age;
        }

        $candidate = CandidateStudent::create([
            ...$validated,
            'usia' => $usia,
            'sumber' => $validated['sumber'] ?? 'Website',
            // status_lead & status_trial pakai default migration ('Inquiry' & 'Belum')
        ]);

        // TODO: notifikasi ke Dashboard Admin/CRM bahwa ada lead baru masuk
        // Notification::route('mail', config('mail.admin_address'))
        //     ->notify(new LeadBaruNotification($candidate));

        return redirect()
            ->route('pendaftaran.sukses')
            ->with('candidate_id', $candidate->id);
    }

    /**
     * Halaman sukses: "Pendaftaran Berhasil! Silakan tunggu Admin
     * menghubungi Anda via WA/Email maksimal 1x24 Jam..."
     */
    public function sukses()
    {
        return view('auth.daftar-sukses');
    }
}