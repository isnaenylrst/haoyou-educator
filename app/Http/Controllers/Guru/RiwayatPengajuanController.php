<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\SubmissionHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Riwayat pengajuan milik guru yang login (jurnal, LP & PPT, report, cuti, sertifikat, reimburse). */
class RiwayatPengajuanController extends Controller
{
    public function index(Request $request, SubmissionHistoryService $history)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403);

        $filters = SubmissionHistoryService::parseFilters($request);
        $filters['teacher'] = null; // guru hanya melihat miliknya sendiri

        $result = $history->paginate(
            $filters,
            ['teacher_id' => $teacher->id, 'user_id' => Auth::id()],
            15,
            url()->current(),
            $request->query()
        );

        return view('guru.riwayat-pengajuan', [
            'filters'           => $filters,
            'rows'              => $result['rows'],
            'counts'            => $result['counts'],
            'typeCounts'        => $result['typeCounts'],
            'total'             => $result['total'],
            'teachers'          => collect(),
            'types'             => SubmissionHistoryService::TYPES,
            'groups'            => SubmissionHistoryService::GROUPS,
            'showTeacherFilter' => false,
            'actionUrl'         => url()->current(),
        ]);
    }
}
