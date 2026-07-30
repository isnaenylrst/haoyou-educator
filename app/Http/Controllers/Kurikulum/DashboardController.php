<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\Teacher;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use App\Models\TeacherLeave;
use App\Models\Material;
use App\Models\Document;

class DashboardController extends Controller
{
    public function index()
    {

        /*
        =====================================
        CARD
        =====================================
        */

        $guruAktif = Teacher::where('status', 'Active')->count();

        $materiPending = TeacherMaterial::where('status', 'Pending')->count();

        $jurnalPending = TeachingJournal::where('status', 'Pending')->count();

        $cutiPending = TeacherLeave::where('status', 'Pending')->count();


        /*
        =====================================
        PERLU DITINDAKLANJUTI
        =====================================
        */

        $pendingMaterials = TeacherMaterial::with([
            'teacher',
            'material'
        ])
        ->where('status', 'Pending')
        ->latest()
        ->take(5)
        ->get();


        /*
        =====================================
        SOP TERBARU
        =====================================
        */

        $latestSops = Document::where('document_type', 'SOP')
            ->latest()
            ->take(5)
            ->get();


        /*
        =====================================
        MATERI TERBARU
        =====================================
        */

        $latestMaterials = Material::with([
            'programPackage',
            'uploader'
        ])
        ->latest()
        ->take(5)
        ->get();


        /*
        =====================================
        PROFILE
        =====================================
        */

        $curriculum = Curriculum::first();


        return view(
            'kurikulum.dashboard',
            compact(
                'guruAktif',
                'materiPending',
                'jurnalPending',
                'cutiPending',
                'curriculum',
                'pendingMaterials',
                'latestMaterials',
                'latestSops'
            )
        );
    }
}