<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\HandlesStudent;
use App\Models\ClassEnrollment;
use App\Models\Document;
use App\Models\Material;
use App\Models\PrivateBooking;
use App\Models\ProgressReport;
use App\Models\ProgramLevel;   
use App\Models\ProgramVideo;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class SiswaDashboardController extends Controller
{
    use HandlesStudent;

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {
        $student = $this->student();

        // Poin & peringkat (students.points, hanya siswa Active)
        $totalPoin = $student->points ?? 0;

        $peringkat = Student::where('status', 'Active')
            ->where('points', '>', $totalPoin)
            ->count() + 1;

        // Kelas reguler yang sudah punya kelas (bukan Waiting Class)
        $enrollments = ClassEnrollment::with([
            'class.teacher',
            'class.schedules',
            'class.programPackage.program',
        ])
            ->where('student_id', $student->id)
            ->where('status', 'Active')
            ->whereNotNull('class_id')
            ->get();

        // Kelas minggu ini = semua sesi (reguler + privat) Senin–Minggu minggu ini
        $sesiMingguIni = $this->sessionsBetween(
            $student,
            $enrollments,
            now()->startOfWeek(),
            now()->endOfWeek()
        );

        $stats = [
            'total_poin'       => $totalPoin,
            'peringkat'        => $peringkat,
            'kelas_minggu_ini' => $sesiMingguIni->count(),
        ];

        // Jadwal terdekat = 3 sesi berikutnya (urut waktu) dalam 14 hari ke depan
        $jadwalTerdekat = $this->sessionsBetween(
            $student,
            $enrollments,
            now(),
            now()->addDays(14)->endOfDay()
        )
            ->filter(fn ($s) => $s['at']->gte(now()))
            ->sortBy('at')
            ->take(3)
            ->map(fn ($s) => [
                'judul'    => $s['judul'],
                'waktu'    => $this->tanggalId($s['at']) . ' · ' . $s['at']->format('H:i')
                              . ($s['room'] ? ' · ' . $s['room'] : ''),
                'platform' => $s['platform'],
            ])
            ->values()
            ->all();

        // Leaderboard top 10
        $leaderboard = Student::where('status', 'Active')
            ->orderByDesc('points')
            ->take(10)
            ->get()
            ->map(fn ($item, $i) => [
                'rank' => $i + 1,
                'nama' => $item->name,
                'poin' => $item->points ?? 0,
            ])
            ->all();

        $progressReports = ProgressReport::with(['teacher', 'enrollment.class'])
            ->where('student_id', $student->id)
            ->where('status', 'Submitted')
            ->latest('created_at')
            ->get();

        $documents = $this->sertifikatQuery()->get();

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

    /**
     * Semua sesi (kelas reguler berulang + booking privat) di rentang tanggal.
     * Jadwal reguler (class_schedules) hanya berisi hari & jam mingguan,
     * jadi tanggalnya dihitung di sini.
     */
    private function sessionsBetween(Student $student, $enrollments, Carbon $from, Carbon $to)
    {
        $sessions = collect();

        foreach ($enrollments as $enrollment) {
            $class = $enrollment->class;

            if (! $class || ! in_array($class->status, ['Open', 'Running'])) {
                continue;
            }

            for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
                // hormati start_date / end_date kelas
                if ($class->start_date && $d->lt($class->start_date->copy()->startOfDay())) {
                    continue;
                }
                if ($class->end_date && $d->gt($class->end_date->copy()->endOfDay())) {
                    continue;
                }

                foreach ($class->schedules as $schedule) {
                    if ($schedule->day !== $this->hariId($d)) {
                        continue;
                    }

                    $at = Carbon::parse($d->format('Y-m-d') . ' ' . $schedule->start_time);

                    $judul = $class->class_name ?? 'Kelas';
                    if ($class->teacher) {
                        $judul .= ' — bersama ' . $class->teacher->name;
                    }

                    $sessions->push([
                        'at'       => $at,
                        'judul'    => $judul,
                        'room'     => $schedule->room,
                        'platform' => strtolower($class->delivery_mode ?? 'offline'),
                    ]);
                }
            }
        }

        $bookings = PrivateBooking::with('teacher')
            ->where('student_id', $student->id)
            ->where('status', 'Scheduled')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        foreach ($bookings as $b) {
            $sessions->push([
                'at'       => $b->startsAt(),
                'judul'    => 'Kelas Privat' . ($b->teacher ? ' — bersama ' . $b->teacher->name : ''),
                'room'     => null,
                'platform' => strtolower($b->delivery_mode),
            ]);
        }

        return $sessions;
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRAM BELAJAR
    |--------------------------------------------------------------------------
    | Materi diunggah Kurikulum per LEVEL (materials.level_id -> program_levels).
    | Level siswa diambil dari kelas / paket yang dia ikuti + current_level_id.
    */

    public function program()
    {
        $student = $this->student();

        $enrollments = ClassEnrollment::with([
            'class.level',
            'class.programPackage.program',
            'class.programPackage.level',
        ])
            ->where('student_id', $student->id)
            ->whereIn('status', ['Active', 'Completed', 'Waiting Class'])
            ->get();

        $levelIds = $enrollments
            ->flatMap(fn ($e) => [
                $e->class?->level_id,
                $e->class?->programPackage?->level_id,
                $e->programPackage?->level_id,
            ])
            ->push($student->current_level_id)
            ->filter()
            ->unique()
            ->values();

        $materials = Material::with(['level.program', 'vocabs'])
            ->whereIn('level_id', $levelIds)
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->get();
        
            // ---------- VIDEO PEMBELAJARAN ----------
            $currentLevel = $this->currentLevel($student, $levelIds);

            // Video budaya: semua bab di level siswa, urut bab
            $cultureVideos = $currentLevel
                ? ProgramVideo::where('type', 'culture')
                    ->where('level_id', $currentLevel->id)
                    ->where('is_published', true)
                    ->orderBy('chapter_number')
                    ->get()
                : collect();

            // Video replay: 1 per level
            $replayVideo = $currentLevel
                ? ProgramVideo::where('type', 'replay')
                    ->where('level_id', $currentLevel->id)
                    ->where('is_published', true)
                    ->first()
                : null;

            return view('Siswa.program', [
                'title'         => 'Program Belajar',
                'student'       => $student,
                'enrollments'   => $enrollments,
                'materials'     => $materials,
                'cultureVideos' => $cultureVideos,
                'replayVideo'   => $replayVideo,
     ]);
}
    /** Kumpulan level_id milik siswa (logika sama seperti sebelumnya). */
private function resolveLevelIds(Student $student, $enrollments)
{
    return $enrollments
        ->flatMap(fn ($e) => [
            $e->class?->level_id,
            $e->class?->programPackage?->level_id,
            $e->programPackage?->level_id,
        ])
        ->push($student->current_level_id)
        ->filter()
        ->unique()
        ->values();
}

/** Level aktif siswa: current_level_id, fallback ke level pertama yang ditemukan. */
private function currentLevel(Student $student, $levelIds): ?ProgramLevel
{
    return ProgramLevel::with('program')
        ->find($student->current_level_id ?? $levelIds->first());
}

/**
 * Streaming video (hanya bisa ditonton, file tidak ada di folder publik).
 * Hanya siswa yang berhak yang bisa membukanya.
 */
public function streamVideo(ProgramVideo $video)
{
    $student = $this->student();

    abort_unless($video->is_published, 404);

    $enrollments = ClassEnrollment::with('class.programPackage')
        ->where('student_id', $student->id)
        ->whereIn('status', ['Active', 'Completed', 'Waiting Class'])
        ->get();

    $levelIds = $this->resolveLevelIds($student, $enrollments);
    $audience = $this->currentLevel($student, $levelIds)?->program?->audience ?? 'hsk';

    $allowed = $video->type === 'culture'
        ? $video->audience === $audience
        : $levelIds->contains($video->level_id);

    abort_unless($allowed, 403);

    $path = Storage::disk('local')->path($video->video_path);
    abort_unless(file_exists($path), 404);

    // response()->file() mendukung Range request, jadi video bisa di-seek
    return response()->file($path, [
        'Content-Type'        => 'video/mp4',
        'Content-Disposition' => 'inline',
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | KELAS SAYA
    |--------------------------------------------------------------------------
    */

    public function kelasSaya()
    {
        $student = $this->student();

        $enrollments = ClassEnrollment::with([
            'class.programPackage.program',
            'class.schedules',
            'class.teacher',
            'privatePackage',
        ])
            ->where('student_id', $student->id)
            ->get();

        $privateBookings = PrivateBooking::with('teacher')
            ->where('student_id', $student->id)
            ->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        return view('Siswa.kelas-saya', compact('student', 'enrollments', 'privateBookings'));
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRESS REPORT
    |--------------------------------------------------------------------------
    | Siswa hanya melihat yang sudah Submitted (Draft = belum diterbitkan).
    */

    public function progressReport()
    {
        $student = $this->student();

        $laporan = ProgressReport::with([
            'teacher',
            'enrollment.class.programPackage.program',
        ])
            ->where('student_id', $student->id)
            ->where('status', 'Submitted')
            ->orderByDesc('created_at')
            ->get();

        return view('Siswa.progress-report', compact('student', 'laporan'));
    }

    /*
    |--------------------------------------------------------------------------
    | SERTIFIKAT
    |--------------------------------------------------------------------------
    | documents.document_type = 'Certificate', milik user ini,
    | dan visibility Student / Public.
    */

    public function sertifikat()
    {
        $student = $this->student();

        $documents = $this->sertifikatQuery()
            ->with('programLevel.program')
            ->get();

        return view('Siswa.sertifikat', compact('student', 'documents'));
    }

    private function sertifikatQuery()
    {
        return Document::where('user_id', Auth::id())
            ->where('document_type', 'Certificate')
            ->whereIn('visibility', ['Student', 'Public'])
            ->latest('uploaded_at');
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI
    |--------------------------------------------------------------------------
    */

    public function notifikasi()
    {
        $user = Auth::user();

        $map = fn ($n) => [
            'judul' => $n->data['title'] ?? 'Notifikasi',
            'ket'   => $n->data['message'] ?? '',
            'waktu' => $n->created_at?->diffForHumans(),
            'url'   => $n->data['url'] ?? null,
        ];

        $belumDibaca = $user->unreadNotifications()->latest()->take(50)->get()->map($map);
        $sudahDibaca = $user->readNotifications()->latest()->take(30)->get()->map($map);

        return view('Siswa.notifikasi', compact('belumDibaca', 'sudahDibaca'));
    }

    public function notifikasiTandaiDibaca()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('notifikasi.index');
    }
}
