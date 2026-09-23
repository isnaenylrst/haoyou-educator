<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Material;
use App\Models\ClassEnrollment;
use App\Models\Program;
use App\Models\Document;
use App\Models\ProgressReport;
use Carbon\Carbon;

class SiswaDashboardController extends Controller
{
    /**
     * Dashboard Siswa
     */
    public function dashboard()
    {
        // User yang sedang login
        $user = Auth::user();

        // Cari data student berdasarkan user_id
        $student = Student::with('user')
            ->where('user_id', $user->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | TOTAL POIN
        |--------------------------------------------------------------------------
        */

        $totalPoin = $student->points ?? 0;


        /*
        |--------------------------------------------------------------------------
        | PERINGKAT LEADERBOARD
        |--------------------------------------------------------------------------
        */

        $peringkat = Student::where('status', 'Active')
            ->where('points', '>', $totalPoin)
            ->count() + 1;


        /*
        |--------------------------------------------------------------------------
        | ENROLLMENT / KELAS SISWA
        |--------------------------------------------------------------------------
        */

        $enrollments = ClassEnrollment::with([
            'class.teacher',
            'class.schedules',
            'class.programPackage.program'
        ])
        ->where('student_id', $student->id)
        ->where('status', 'Active')
        ->get();


        /*
        |--------------------------------------------------------------------------
        | KELAS MINGGU INI
        |--------------------------------------------------------------------------
        |
        | Kita hitung berdasarkan jadwal kelas.
        |
        */

        $hariSekarang = Carbon::now();

        $awalMinggu = $hariSekarang->copy()->startOfWeek();
        $akhirMinggu = $hariSekarang->copy()->endOfWeek();

        $hariIndonesia = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $hariMingguIni = [];

        for (
            $tanggal = $awalMinggu->copy();
            $tanggal <= $akhirMinggu;
            $tanggal->addDay()
        ) {
            $hariMingguIni[] = $hariIndonesia[$tanggal->format('l')];
        }

        $kelasMingguIni = 0;

        foreach ($enrollments as $enrollment) {

            if (!$enrollment->class) {
                continue;
            }

            foreach ($enrollment->class->schedules as $schedule) {

                if (in_array($schedule->day, $hariMingguIni)) {
                    $kelasMingguIni++;
                }

            }
        }


        /*
        |--------------------------------------------------------------------------
        | STATISTIK
        |--------------------------------------------------------------------------
        */

        $stats = [
            'total_poin' => $totalPoin,
            'peringkat' => $peringkat,
            'kelas_minggu_ini' => $kelasMingguIni,
        ];


        /*
        |--------------------------------------------------------------------------
        | JADWAL TERDEKAT
        |--------------------------------------------------------------------------
        */

        $jadwalTerdekat = [];

        foreach ($enrollments as $enrollment) {

            if (!$enrollment->class) {
                continue;
            }

            $class = $enrollment->class;

            foreach ($class->schedules as $schedule) {

                $judul = $class->class_name ?? 'Kelas';

                if ($class->teacher) {
                    $judul .= ' — bersama ' . $class->teacher->name;
                }

                $waktu = $schedule->day;

                if ($schedule->start_time) {
                    $waktu .= ', ' .
                        Carbon::parse($schedule->start_time)->format('H:i');
                }

                if ($schedule->room) {
                    $waktu .= ' · ' . $schedule->room;
                }

                /*
                | delivery_mode biasanya:
                | Online / Offline
                */

                $platform = strtolower(
                    $class->delivery_mode ?? 'offline'
                );

                $jadwalTerdekat[] = [
                    'judul' => $judul,
                    'waktu' => $waktu,
                    'platform' => $platform,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | BATASI 3 JADWAL
        |--------------------------------------------------------------------------
        */

        $jadwalTerdekat = collect($jadwalTerdekat)
            ->take(3)
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | LEADERBOARD
        |--------------------------------------------------------------------------
        */

        $leaderboardData = Student::where('status', 'Active')
            ->orderByDesc('points')
            ->take(10)
            ->get();

        $leaderboard = [];

        foreach ($leaderboardData as $index => $item) {

            $leaderboard[] = [
                'rank' => $index + 1,
                'nama' => $item->name,
                'poin' => $item->points ?? 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PROGRESS REPORT
        |--------------------------------------------------------------------------
        */

        $progressReports = ProgressReport::with([
            'teacher',
            'enrollment.class'
        ])
        ->where('student_id', $student->id)
        ->latest('uploaded_at')
        ->get();


        /*
        |--------------------------------------------------------------------------
        | DOKUMEN / SERTIFIKAT
        |--------------------------------------------------------------------------
        */

        $documents = Document::where('user_id', $user->id)
            ->latest('uploaded_at')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | KIRIM KE VIEW
        |--------------------------------------------------------------------------
        */

        return view('Siswa.dashboard', compact(
            'student',
            'stats',
            'jadwalTerdekat',
            'leaderboard',
            'enrollments',
            'progressReports',
            'documents'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | PROGRAM
    |--------------------------------------------------------------------------
    */

   public function program()
{
   $user = Auth::user();


    $student = $user->student;

    if (!$student) {
        abort(403, 'Data siswa tidak ditemukan.');
    }

    // Ambil enrollment siswa
    $enrollments = ClassEnrollment::with([
        'class.programPackage.program'
    ])
    ->where('student_id', $student->id)
    ->get();

    // Ambil ID package yang diikuti siswa
    $packageIds = $enrollments
        ->pluck('class.program_package_id')
        ->filter()
        ->unique();

    // Ambil materi sesuai package siswa
    $materials = Material::with([
        'programPackage.program'
    ])
    ->whereIn('program_package_id', $packageIds)
    ->orderBy('meeting_number')
    ->get();

    return view('Siswa.program', compact(
        'student',
        'enrollments',
        'materials'
    ));
}

    /*
    |--------------------------------------------------------------------------
    | KELAS SAYA
    |--------------------------------------------------------------------------
    */

    public function kelasSaya()
{
    $user = Auth::user();

    $student = $user->student;

    if (!$student) {
        abort(404, 'Data siswa tidak ditemukan.');
    }

    $enrollments = ClassEnrollment::with([
        'class.programPackage.program',
        'class.schedules',
        'class.teacher',
    ])
    ->where('student_id', $student->id)
    ->get();

    return view('Siswa.kelas-saya', compact(
        'student',
        'enrollments'
    ));
}

    /*
    |--------------------------------------------------------------------------
    | PROGRESS REPORT
    |--------------------------------------------------------------------------
    */

    public function progressReport()
    {
    $user = Auth::user();

    $student = $user->student;

    if (!$student) {
        abort(403, 'Data siswa tidak ditemukan.');
    }

    // Ambil laporan perkembangan siswa
    $laporan = ProgressReport::with([
        'teacher',
        'enrollment.class.programPackage.program'
    ])
    ->where('student_id', $student->id)
    ->orderByDesc('uploaded_at')
    ->get();

    return view('Siswa.progress-report', compact(
        'student',
        'laporan'
    ));
}

    /*
    |--------------------------------------------------------------------------
    | SERTIFIKAT
    |--------------------------------------------------------------------------
    */

    public function sertifikat()
    {
        $user = Auth::user();

        $documents = Document::where('user_id', $user->id)
            ->latest('uploaded_at')
            ->get();

        return view('siswa.sertifikat', compact(
            'documents'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | PROFIL
    |--------------------------------------------------------------------------
    */

    public function profil()
{
    $user = Auth::user();
    $student = $user->student;

    if (!$student) {
        abort(404, 'Data siswa tidak ditemukan.');
    }

    // Karena belum ada tabel khusus riwayat poin,
    // sementara ambil total poin dari tabel students.
    $riwayatPoin = collect([
        [
            'judul' => 'Total Poin Siswa',
            'poin' => $student->points ?? 0,
            'tanggal' => $student->join_date ?? '-',
        ]
    ]);

    return view('Siswa.profil', compact(
        'student',
        'riwayatPoin'
    ));
}


    /*
    |--------------------------------------------------------------------------
    | BOOKING
    |--------------------------------------------------------------------------
    */

   

 public function booking()
{
    $slotPrivat = [];
    $kelasReguler = [];

    return view('Siswa.booking', compact(
        'slotPrivat',
        'kelasReguler'
    ));
}
    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI
    |--------------------------------------------------------------------------
    */

   public function notifikasi()
{
    $belumDibaca = collect([
        [
            'judul' => 'Jadwal Kelas Hari Ini',
            'ket'   => 'HSK 2 Reguler bersama Ms. Dinda pukul 19.00',
        ],
        [
            'judul' => 'Materi Baru',
            'ket'   => 'Materi Meeting 3 sudah tersedia.',
        ],
    ]);

    $sudahDibaca = collect([
        [
            'judul' => 'Progress Report',
            'ket'   => 'Progress Report bulan lalu telah diterbitkan.',
        ],
        [
            'judul' => 'Pembayaran Berhasil',
            'ket'   => 'Pembayaran paket belajar berhasil diverifikasi.',
        ],
    ]);

    return view('Siswa.notifikasi', compact(
        'belumDibaca',
        'sudahDibaca'
    ));
}
    }

