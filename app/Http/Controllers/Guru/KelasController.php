<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Material;
use App\Models\ProgramCategory;
use App\Models\TeacherMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            abort(403, 'Akun Anda belum terhubung dengan data guru.');
        }

        $categories = ProgramCategory::orderBy('category_name')->get();

        $materials = Material::with('level.category')
            ->orderBy('title')
            ->get();

        $submissions = TeacherMaterial::with(['material.level.category', 'consultations'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        $activeSubmission = $submissions->firstWhere('status', '!=', 'Approved');

        $progressSteps = $activeSubmission
            ? $this->buildProgressSteps($activeSubmission)
            : null;

        return view('guru.kelas', compact(
            'categories',
            'materials',
            'submissions',
            'activeSubmission',
            'progressSteps'
        ));
    }

        private function buildProgressSteps(TeacherMaterial $submission): array
    {
        // ambil konsultasi TERBARU per jenis (yang lama/dibatalkan tidak menutupi pengajuan ulang)
        $latest = fn (string $type) => $submission->consultations
            ->where('type', $type)
            ->sortByDesc('id')
            ->first();

        $konsultasiKurikulum = $latest('LP_PPT');
        $konsultasiDirektur  = $latest('Direktur');

        return [
            [
                'label' => '1. Konsultasi Kurikulum',
                'consultation' => $konsultasiKurikulum,
                'type' => 'LP_PPT',
            ],
            [
                'label' => '2. Konsultasi Direktur',
                'consultation' => $konsultasiDirektur,
                'type' => 'Direktur',
                'locked' => !$konsultasiKurikulum || $konsultasiKurikulum->status !== 'Selesai',
            ],
            [
                'label' => '3. Revisi LP',
                'status_text' => $submission->status === 'Rejected' ? 'Perlu Revisi' : 'Menunggu',
            ],
            [
                'label' => '4. Upload PPT Final',
                'status_text' => $submission->status === 'Approved' ? 'Selesai' : 'Belum Mulai',
            ],
        ];
    }

    public function requestConsultation(Request $request, TeacherMaterial $teacherMaterial)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || (int) $teacherMaterial->teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        $request->validate([
            'type' => ['required', Rule::in(['LP_PPT', 'Direktur'])],
        ]);

        if ($teacherMaterial->status === 'Approved') {
            return back()->with('error', 'Pengajuan ini sudah disetujui, tidak perlu konsultasi lagi.');
        }

        // Konsultasi Direktur hanya boleh setelah konsultasi Kurikulum selesai
        if ($request->type === 'Direktur') {
            $kurikulumSelesai = Consultation::where('teacher_material_id', $teacherMaterial->id)
                ->where('type', 'LP_PPT')
                ->where('status', 'Selesai')
                ->exists();

            if (!$kurikulumSelesai) {
                return back()->with('error', 'Selesaikan konsultasi Kurikulum terlebih dahulu.');
            }
        }

        // Yang sudah dibatalkan Kurikulum boleh diajukan ulang
        $exists = Consultation::where('teacher_material_id', $teacherMaterial->id)
            ->where('type', $request->type)
            ->where('status', '!=', 'Dibatalkan')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Jadwal konsultasi untuk tahap ini sudah pernah diajukan.');
        }

        Consultation::create([
            'teacher_id'          => $teacher->id,
            'teacher_material_id' => $teacherMaterial->id,
            'type'                => $request->type,
            'status'              => 'Belum Terjadwal',
            'notify_whatsapp'     => true,
            'created_by'          => Auth::id(),
        ]);

        return back()->with('success', 'Pengajuan jadwal konsultasi berhasil dikirim ke Kurikulum.');
    }

    public function store(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            abort(403, 'Akun Anda belum terhubung dengan data guru.');
        }

        $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'lp_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file' => ['required', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
        ]);

        try {
            DB::transaction(function () use ($request, $teacher) {

                $lpPath = $request->file('lp_file')->store('teacher_materials/lp', 'public');
                $pptPath = $request->file('ppt_file')->store('teacher_materials/ppt', 'public');

                $material = Material::findOrFail($request->material_id);

                TeacherMaterial::create([
                    'material_id' => $request->material_id,
                    'teacher_id' => $teacher->id,
                    'ppt_title' => $material->title,
                    'lp_file_path' => $lpPath,
                    'ppt_file_path' => $pptPath,
                    'status' => 'Pending',
                ]);
            });

            return redirect()
                ->route('teacher.kelas')
                ->with('success', 'LP & PPT berhasil dikirim untuk direview Kurikulum.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal mengirim LP & PPT: ' . $e->getMessage());
        }
    }

    public function reupload(Request $request, TeacherMaterial $teacherMaterial)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || $teacherMaterial->teacher_id !== $teacher->id) {
            abort(403);
        }

        $request->validate([
            'lp_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
        ]);

        $data = ['status' => 'Pending', 'review_note' => null];

        if ($request->hasFile('lp_file')) {
            if ($teacherMaterial->lp_file_path && Storage::disk('public')->exists($teacherMaterial->lp_file_path)) {
                Storage::disk('public')->delete($teacherMaterial->lp_file_path);
            }
            $data['lp_file_path'] = $request->file('lp_file')->store('teacher_materials/lp', 'public');
        }

        if ($request->hasFile('ppt_file')) {
            if ($teacherMaterial->ppt_file_path && Storage::disk('public')->exists($teacherMaterial->ppt_file_path)) {
                Storage::disk('public')->delete($teacherMaterial->ppt_file_path);
            }
            $data['ppt_file_path'] = $request->file('ppt_file')->store('teacher_materials/ppt', 'public');
        }

        $teacherMaterial->update($data);

        return redirect()
            ->route('teacher.kelas')
            ->with('success', 'LP & PPT berhasil diupload ulang dan dikirim kembali untuk direview.');
    }
}