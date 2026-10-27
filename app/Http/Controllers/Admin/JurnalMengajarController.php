<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jurnal Mengajar — tampilan READ-ONLY untuk Admin.
 * Pengisian jurnal (create/update) dilakukan oleh Guru lewat halaman guru.
 */
class JurnalMengajarController extends Controller
{
    /* ============================================================
       INDEX — daftar jurnal + filter + statistik
    ============================================================ */
    public function index(Request $request)
    {
        $journals = TeachingJournal::with([
                'class.level',
                'class.privatePackage',
                'class.programPackage',
                'teacher',
                'substituteTeacher',
                'material',
                'attendances',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($w) use ($term) {
                    $w->whereHas('class', fn ($c) => $c->where('class_name', 'like', $term))
                      ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', $term))
                      ->orWhereHas('substituteTeacher', fn ($t) => $t->where('name', 'like', $term));
                });
            })
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->filled('teacher_id'), function ($q) use ($request) {
                $q->where(fn ($w) => $w->where('teacher_id', $request->teacher_id)
                                       ->orWhere('substitute_teacher_id', $request->teacher_id));
            })
            ->when($request->filled('class_status'), fn ($q) => $q->where('class_status', $request->class_status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('session_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('session_date', '<=', $request->to))
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Statistik atas seluruh jurnal (tidak ikut filter).
        $stats = TeachingJournal::selectRaw(
            "COUNT(*) AS total,
             SUM(class_status = 'Conducted') AS conducted,
             SUM(class_status <> 'Conducted') AS not_conducted,
             SUM(is_substitute = 1) AS substitute"
        )->first();

        return view('admin.jurnalmengajar', [
            'journals'          => $journals,
            'classes'           => ClassModel::orderBy('class_name')->get(['id', 'class_name']),
            'teachers'          => Teacher::orderBy('name')->get(['id', 'name']),
            'totalJournals'     => (int) ($stats->total ?? 0),
            'conductedCount'    => (int) ($stats->conducted ?? 0),
            'notConductedCount' => (int) ($stats->not_conducted ?? 0),
            'substituteCount'   => (int) ($stats->substitute ?? 0),
        ]);
    }

    /* ============================================================
       SHOW — JSON untuk modal detail
    ============================================================ */
    public function show(TeachingJournal $journal): JsonResponse
    {
        $journal->load(['class', 'teacher', 'substituteTeacher', 'material', 'attendances.student']);

        return response()->json([
            'id'                  => $journal->id,
            'class_name'          => $journal->class?->class_name,
            'is_private'          => (bool) $journal->class?->private_package_id,
            'session_date'        => $journal->session_date?->format('Y-m-d'),
            'class_status'        => $journal->class_status,
            'teacher_name'        => $journal->teacher?->name,
            'substitute_name'     => $journal->substituteTeacher?->name,
            'material_title'      => $journal->material?->title,
            'learning_activities' => $journal->learning_activities,
            'problems'            => $journal->problems,
            'solutions'           => $journal->solutions,
            'results'             => $journal->results,
            'notes'               => $journal->notes,
            'attendances'         => $journal->attendances
                ->sortBy(fn ($a) => $a->student?->name)
                ->map(fn ($a) => [
                    'student_id' => $a->student_id,
                    'name'       => $a->student?->name,
                    'status'     => $a->status,
                    'note'       => $a->note,
                ])->values(),
        ]);
    }
}