<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;

class SiswaDashboardController extends Controller
{
    /**
     * NOTE: Semua data di controller ini masih DUMMY (statis) karena
     * struktur database untuk modul kelas/booking/poin/sertifikat/dll
     * belum final. Setiap method sudah diberi komentar bagian mana yang
     * nanti diganti query Eloquent sesungguhnya.
     */

    public function dashboard()
    {
        // TODO: ganti dengan query real, contoh:
        // $totalPoin = auth()->user()->poinTransaksi()->sum('jumlah_poin');
        $stats = [
            'total_poin' => 1240,
            'peringkat' => 4,
            'kelas_minggu_ini' => 3,
        ];

        $jadwalTerdekat = [
            ['judul' => 'HSK 2 Reguler — bersama Ms. Dinda', 'waktu' => 'Senin, 19:00', 'platform' => 'online'],
            ['judul' => 'Kelas Privat — bersama Mr. Wei', 'waktu' => 'Rabu, 16:00 · Ruang Hanzi 03', 'platform' => 'offline'],
        ];

        $leaderboard = [
            ['nama' => 'Alya Putri', 'poin' => 1860, 'rank' => 1],
            ['nama' => 'Bagas W.', 'poin' => 1510, 'rank' => 2],
            ['nama' => 'Cindy L.', 'poin' => 1400, 'rank' => 3],
            ['nama' => 'Dina Anggraini (kamu)', 'poin' => 1240, 'rank' => 4],
        ];

        return view('siswa.dashboard', [
            'title' => 'Dashboard',
            'subtitle' => '你好, selamat belajar hari ini',
            'stats' => $stats,
            'jadwalTerdekat' => $jadwalTerdekat,
            'leaderboard' => $leaderboard,
        ]);
    }

    public function program()
    {
        // TODO: ganti dengan Materi::where('program_id', auth()->user()->program_id)->get();
        $videoList = [
            ['judul' => 'Unit 1 — Sapaan Dasar', 'durasi' => '12 menit', 'status' => 'ditonton', 'poin' => 20],
            ['judul' => 'Unit 2 — Angka & Waktu', 'durasi' => '15 menit', 'status' => 'belum', 'poin' => 20],
        ];

        return view('siswa.program', [
            'title' => 'Program Belajar',
            'subtitle' => 'Materi & modul HSK Hybrid',
            'videoList' => $videoList,
        ]);
    }

    public function booking()
    {
        // TODO: slot privat ambil dari Kelas::where('kategori_kelas','privat')->...
        $slotPrivat = [
            ['label' => 'Sen 16:00', 'status' => 'kosong'],
            ['label' => 'Sen 18:00 · Penuh', 'status' => 'penuh'],
            ['label' => 'Rab 16:00 ✓', 'status' => 'dipilih'],
            ['label' => 'Rab 19:00', 'status' => 'kosong'],
            ['label' => 'Jum 15:00', 'status' => 'kosong'],
            ['label' => 'Jum 17:00 · Penuh', 'status' => 'penuh'],
            ['label' => 'Sab 10:00', 'status' => 'kosong'],
            ['label' => 'Sab 13:00', 'status' => 'kosong'],
        ];

        $kelasReguler = [
            ['judul' => 'HSK 1 Reguler — Ms. Dinda', 'jadwal' => 'Selasa & Kamis, 19:00 · Online'],
            ['judul' => 'HSK 2 Reguler — Mr. Wei', 'jadwal' => 'Senin, 19:00 · Online'],
        ];

        return view('siswa.booking', [
            'title' => 'Booking Kelas',
            'subtitle' => 'Pilih kategori kelas kamu',
            'slotPrivat' => $slotPrivat,
            'kelasReguler' => $kelasReguler,
        ]);
    }

    public function kelasSaya()
    {
        // TODO: JadwalSiswa::where('siswa_id', auth()->id())->with('kelas')->get();
        $kelasSaya = [
            ['judul' => 'HSK 2 Reguler — Ms. Dinda', 'waktu' => 'Senin, 19:00', 'platform' => 'online', 'detail' => 'Online'],
            ['judul' => 'Kelas Privat — Mr. Wei', 'waktu' => 'Rabu, 16:00', 'platform' => 'offline', 'detail' => 'Ruang Hanzi 03'],
        ];

        return view('siswa.kelas-saya', [
            'title' => 'Kelas Saya',
            'subtitle' => 'Detail & pelaksanaan kelas',
            'kelasSaya' => $kelasSaya,
        ]);
    }

    public function profil()
    {
        $riwayatPoin = [
            ['ket' => 'Menonton Unit 1 — Sapaan Dasar', 'poin' => '+20', 'tipe' => 'tambah'],
            ['ket' => 'Ditukar reward: Stiker Hanzi', 'poin' => '-50', 'tipe' => 'kurang'],
        ];

        return view('siswa.profil', [
            'title' => 'Profil',
            'subtitle' => 'Kelola akun kamu',
            'riwayatPoin' => $riwayatPoin,
        ]);
    }

    public function notifikasi()
    {
        $belumDibaca = [
            ['judul' => 'Kelas dimulai 1 jam lagi — HSK 2 Reguler', 'ket' => '19:00 · Laoshi: Ms. Dinda · Link Zoom sudah tersedia'],
            ['judul' => 'Kelas Privat besok — Offline', 'ket' => 'Rabu 16:00 · Laoshi: Mr. Wei · Ruang Hanzi 03'],
            ['judul' => 'Materi yang perlu disiapkan', 'ket' => 'Bawa buku HSK 2 Unit 5 & alat tulis sebelum kelas besok'],
        ];

        $sudahDibaca = [
            ['judul' => 'Link Zoom kelas HSK 1 telah dibuat', 'ket' => 'Senin lalu · Laoshi: Ms. Dinda'],
            ['judul' => 'Materi terakhir tersedia untuk dipelajari ulang', 'ket' => 'Unit 4 — Keluarga & Kerabat'],
            ['judul' => 'Kamu naik ke peringkat #4 Leaderboard', 'ket' => '+20 poin dari menonton Unit 1'],
        ];

        return view('siswa.notifikasi', [
            'title' => 'Notifikasi',
            'subtitle' => 'Pengingat kelas otomatis',
            'belumDibaca' => $belumDibaca,
            'sudahDibaca' => $sudahDibaca,
        ]);
    }

    public function sertifikat()
    {
        // TODO: $sertifikat = auth()->user()->sertifikat()->first();
        // hanya ada isinya jika program siswa tipe_program = 'hsk'
        $adaSertifikat = true; // dummy toggle, nanti dari status program siswa

        return view('siswa.sertifikat', [
            'title' => 'Sertifikat',
            'subtitle' => 'Download sertifikat kelulusan',
            'adaSertifikat' => $adaSertifikat,
        ]);
    }

    public function progresReport()
    {
        $laporan = [
            ['judul' => 'Progress Report — Kelas Reguler', 'periode' => 'Periode Jan–Mar 2026 · Terbit setiap 3 bulan'],
            ['judul' => 'Progress Report — Kelas HSK', 'periode' => 'Periode Jan–Feb 2026 · Terbit setiap 2 bulan'],
        ];

        return view('siswa.progress-report', [
            'title' => 'Progress Report',
            'subtitle' => 'Laporan perkembangan belajar',
            'laporan' => $laporan,
        ]);
    }
}