<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Consultation;
use App\Models\Document;
use App\Models\Material;
use App\Models\ProgressReport;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
//use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Profil milik user yang sedang login
        //$curriculum = Auth::user()->curriculum;

        // Kartu statistik (angka sama dengan halaman Review Pengajuan)
        $stats = [
            'guruAktif'  => Teacher::where('status', 'Active')->count(),
            'materi'     => TeacherMaterial::where('status', 'Pending')->count(),
            'jurnal'     => TeachingJournal::where('status', 'Pending')->count(),
            'report'     => ProgressReport::where('status', 'Submitted')->whereNull('reviewed_at')->count(),
            'cuti'       => TeacherLeave::where('status', 'Pending')->count(),
            'sertifikat' => Certificate::pendingReview()->count(),
        ];
        $stats['totalPending'] = $stats['materi'] + $stats['jurnal'] + $stats['report']
            + $stats['cuti'] + $stats['sertifikat'];

        $konsultasiBelumTerjadwal = Consultation::where('status', 'Belum Terjadwal')->count();

        // Daftar "Perlu Ditindaklanjuti": gabungan semua jenis pengajuan, terbaru dulu
        $pending = collect()
            ->concat(
                TeacherMaterial::with('teacher')->where('status', 'Pending')->latest()->take(8)->get()
                    ->map(fn ($i) => [
                        'who'  => $i->teacher?->name ?? '-',
                        'what' => 'Upload LP & PPT: ' . $i->ppt_title,
                        'time' => $i->created_at,
                        'tab'  => 'material',
                    ])
            )
            ->concat(
                TeachingJournal::with(['teacher', 'class'])->where('status', 'Pending')->latest()->take(8)->get()
                    ->map(fn ($i) => [
                        'who'  => $i->teacher?->name ?? '-',
                        'what' => 'Jurnal kelas ' . ($i->class?->class_name ?? '-'),
                        'time' => $i->created_at,
                        'tab'  => 'jurnal',
                    ])
            )
            ->concat(
                ProgressReport::with(['teacher', 'student'])
                    ->where('status', 'Submitted')->whereNull('reviewed_at')->latest()->take(8)->get()
                    ->map(fn ($i) => [
                        'who'  => $i->teacher?->name ?? '-',
                        'what' => 'Progress report ' . ($i->student?->name ?? '-'),
                        'time' => $i->submitted_at ?? $i->created_at,
                        'tab'  => 'report',
                    ])
            )
            ->concat(
                TeacherLeave::with('teacher')->where('status', 'Pending')->latest()->take(8)->get()
                    ->map(fn ($i) => [
                        'who'  => $i->teacher?->name ?? '-',
                        'what' => 'Pengajuan ' . ($i->request_type ?? 'Cuti'),
                        'time' => $i->created_at,
                        'tab'  => 'cuti',
                    ])
            )
            ->concat(
                Certificate::with(['teacher', 'student'])->pendingReview()->latest()->take(8)->get()
                    ->map(fn ($i) => [
                        'who'  => $i->teacher?->name ?? '-',
                        'what' => 'Sertifikat ' . ($i->student?->name ?? '-'),
                        'time' => $i->created_at,
                        'tab'  => 'sertifikat',
                    ])
            )
            ->sortByDesc('time')
            ->take(8)
            ->values();

        $latestSops = Document::where('document_type', 'SOP')->latest('uploaded_at')->take(5)->get();

        $latestMaterials = Material::with(['level.program', 'uploader'])->latest()->take(5)->get();

        return view('kurikulum.dashboard', compact(
            //'curriculum',
            'stats',
            'konsultasiBelumTerjadwal',
            'pending',
            'latestSops',
            'latestMaterials'
        ));
    }
}