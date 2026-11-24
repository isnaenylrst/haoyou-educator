<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\SubmissionHistoryService;
use Illuminate\Http\Request;

/** Riwayat semua pengajuan guru (tidak hilang setelah di-ACC / TTD lengkap). */
class RiwayatPengajuanController extends Controller
{
    public function index(Request $request, SubmissionHistoryService $history)
    {
        $filters = SubmissionHistoryService::parseFilters($request);
        $result  = $history->paginate($filters, null, 15, url()->current(), $request->query());

        return view('kurikulum.riwayat-pengajuan', [
            'filters'          => $filters,
            'rows'             => $result['rows'],
            'counts'           => $result['counts'],
            'typeCounts'       => $result['typeCounts'],
            'total'            => $result['total'],
            'teachers'         => Teacher::orderBy('name')->get(['id', 'name']),
            'types'            => SubmissionHistoryService::TYPES,
            'groups'           => SubmissionHistoryService::GROUPS,
            'showTeacherFilter' => true,
            'actionUrl'        => url()->current(),
        ]);
    }
}
