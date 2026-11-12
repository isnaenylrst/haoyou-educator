<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Consultation;
use App\Models\Material;
use App\Models\ProgramCategory;
use App\Models\TeacherMaterial;
use App\Models\TeacherMaterialVersion;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            abort(403, 'Akun Anda belum terhubung dengan data guru.');
        }

        // Daftar kelas yang diampu guru ini
        $myClasses = ClassModel::where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Accepted')
            ->orderBy('class_name')
            ->get();

        $selectedClassId = $request->query('class_id');

        $categories = ProgramCategory::orderBy('category_name')->get();

        $materials = Material::forGuru()->with('level.category')
            ->orderBy('title')
            ->get();

        $query = TeacherMaterial::with(['material.level.category', 'class', 'versions.reviewer', 'consultations'])
            ->where('teacher_id', $teacher->id);

        if ($selectedClassId) {
            $query->where('class_id', $selectedClassId);
        }

        $submissions = $query->latest()->get();

        $activeSubmission = $submissions->firstWhere('status', '!=', 'Approved');

        $progressSteps = $activeSubmission
            ? $this->buildProgressSteps($activeSubmission)
            : null;

        return view('guru.kelas', compact(
            'myClasses',
            'selectedClassId',
            'categories',
            'materials',
            'submissions',
            'activeSubmission',
            'progressSteps'
        ));
    }

    private function buildProgressSteps(TeacherMaterial $submission): array
    {
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
                'label' => '3. Revisi LP & Dokumen',
                'status_text' => in_array($submission->status, ['Rejected', 'Perlu Revisi']) ? 'Perlu Revisi' : 'Menunggu',
            ],
            [
                'label' => '4. Upload PPT & Finalisasi',
                'status_text' => in_array($submission->status, ['Approved', 'Disetujui']) ? 'Disetujui' : 'Belum Selesai',
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

        if (in_array($teacherMaterial->status, ['Approved', 'Disetujui'])) {
            return back()->with('error', 'Pengajuan ini sudah disetujui, tidak perlu konsultasi lagi.');
        }

        if ($request->type === 'Direktur') {
            $kurikulumSelesai = Consultation::where('teacher_material_id', $teacherMaterial->id)
                ->where('type', 'LP_PPT')
                ->where('status', 'Selesai')
                ->exists();

            if (!$kurikulumSelesai) {
                return back()->with('error', 'Selesaikan konsultasi Kurikulum terlebih dahulu.');
            }
        }

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
            'class_id'            => $teacherMaterial->class_id,
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
            'class_id'    => ['required', 'exists:classes,id'],
            'material_id' => ['required', Rule::exists('materials', 'id')->where('audience', 'Guru')],
            'lp_file'     => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file'    => ['required', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $teacher) {
                $lpPath  = $request->file('lp_file')->store('teacher_materials/lp', 'public');
                $pptPath = $request->file('ppt_file')->store('teacher_materials/ppt', 'public');

                $material = Material::forGuru()->findOrFail($request->material_id);
                $class    = ClassModel::findOrFail($request->class_id);

                $submission = TeacherMaterial::create([
                    'material_id'     => $request->material_id,
                    'teacher_id'      => $teacher->id,
                    'class_id'        => $class->id,
                    'ppt_title'       => $material->title,
                    'lp_file_path'    => $lpPath,
                    'ppt_file_path'   => $pptPath,
                    'status'          => 'Diajukan',
                    'current_version' => 1,
                ]);

                // Simpan versi 1 ke riwayat versi
                TeacherMaterialVersion::create([
                    'teacher_material_id' => $submission->id,
                    'version_number'      => 1,
                    'lp_file_path'         => $lpPath,
                    'ppt_file_path'        => $pptPath,
                    'notes'                => $request->notes ?? 'Versi awal',
                    'status'               => 'Diajukan',
                ]);

                // Notifikasi ke seluruh tim Kurikulum
                $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
                foreach ($curriculums as $cUser) {
                    $cUser->notify(new AppNotification(
                        title: "Revisi Dokumen Masuk: {$class->class_name}",
                        message: "Guru {$teacher->name} mengunggah Lesson Plan & PPT (Versi 1) untuk kelas {$class->class_name} ({$material->title}).",
                        url: url('/kurikulum/review-pengajuan?tab=material'),
                        type: 'dokumen_kelas',
                        icon: 'fa-chalkboard'
                    ));
                }
            });

            return redirect()
                ->route('teacher.kelas')
                ->with('success', 'Lesson Plan & PPT berhasil dikirim untuk direview Kurikulum (Versi 1).');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal mengirim dokumen kelas: ' . $e->getMessage());
        }
    }

    public function reupload(Request $request, TeacherMaterial $teacherMaterial)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || (int) $teacherMaterial->teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        $request->validate([
            'lp_file'  => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'ppt_file' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
            'notes'    => ['required', 'string', 'max:1000'],
        ], [
            'notes.required' => 'Catatan revisi wajib diisi agar Kurikulum mengetahui poin perubahan dokumen.',
        ]);

        if (!$request->hasFile('lp_file') && !$request->hasFile('ppt_file')) {
            return back()->with('error', 'Pilih minimal satu file (LP atau PPT) baru untuk revisi versi baru.');
        }

        try {
            DB::transaction(function () use ($request, $teacherMaterial, $teacher) {
                $newVersion = ($teacherMaterial->current_version ?: 1) + 1;

                // Jangan hapus file lama; simpan path baru
                $lpPath  = $request->hasFile('lp_file')
                    ? $request->file('lp_file')->store('teacher_materials/lp', 'public')
                    : $teacherMaterial->lp_file_path;

                $pptPath = $request->hasFile('ppt_file')
                    ? $request->file('ppt_file')->store('teacher_materials/ppt', 'public')
                    : $teacherMaterial->ppt_file_path;

                // Catat versi baru di riwayat versi
                TeacherMaterialVersion::create([
                    'teacher_material_id' => $teacherMaterial->id,
                    'version_number'      => $newVersion,
                    'lp_file_path'         => $lpPath,
                    'ppt_file_path'        => $pptPath,
                    'notes'                => $request->notes,
                    'status'               => 'Diajukan',
                ]);

                // Update induk TeacherMaterial
                $teacherMaterial->update([
                    'lp_file_path'    => $lpPath,
                    'ppt_file_path'   => $pptPath,
                    'current_version' => $newVersion,
                    'status'          => 'Diajukan',
                    'review_note'     => null,
                ]);

                // Notifikasi ke Kurikulum
                $className = $teacherMaterial->class?->class_name ?? 'Kelas';
                $curriculums = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
                foreach ($curriculums as $cUser) {
                    $cUser->notify(new AppNotification(
                        title: "Revisi Dokumen Baru (Versi {$newVersion}): {$className}",
                        message: "Guru {$teacher->name} mengunggah revisi versi {$newVersion} untuk kelas {$className}. Catatan guru: {$request->notes}",
                        url: url('/kurikulum/review-pengajuan?tab=material'),
                        type: 'dokumen_kelas',
                        icon: 'fa-chalkboard'
                    ));
                }
            });

            return redirect()
                ->route('teacher.kelas')
                ->with('success', 'Revisi dokumen versi baru berhasil diupload dan dikirim kembali ke Kurikulum.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengupload revisi: ' . $e->getMessage());
        }
    }
}
