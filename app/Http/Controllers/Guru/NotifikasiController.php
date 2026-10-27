<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassTask;
use App\Models\Document;
use App\Models\ProgressReport;
use App\Models\TeacherLeave;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotifikasiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Notifikasi guru = gabungan dari:
    | 1. Surat/Dokumen dari Kurikulum (visibility Teacher/Private miliknya)
    | 2. Hasil review LP & PPT miliknya
    | 3. Hasil review Jurnal & Absensi miliknya
    | 4. Hasil review Progress Report miliknya
    | 5. Hasil review Cuti/Ganti Kelas miliknya
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            abort(403, 'Akun Anda belum terhubung dengan data guru.');
        }

        $notifications = collect();

        // ----- 1. Surat dari Kurikulum (hanya yang ditujukan ke guru ini) -----
        $letters = Document::letters()
            ->join('document_recipients as r', 'r.document_id', '=', 'documents.id')
            ->where('r.user_id', Auth::id())
            ->select('documents.*', 'r.read_at as my_read_at')
            ->get();

        foreach ($letters as $doc) {
            $notifications->push([
                'title'    => $doc->title,
                'category' => 'Surat Pemberitahuan — ' . $doc->type_label,
                'time'     => $doc->uploaded_at,
                'note'     => $doc->description,
                'model'    => $doc,
                'type'     => 'document',
                'unread'   => $doc->my_read_at === null,
                'action'   => [
                    'kind'  => 'link',
                    'url'   => route('teacher.surat.show', $doc),
                    'label' => 'Buka Surat',
                ],
            ]);
        }

        // ----- 2. LP & PPT -----
        $materials = TeacherMaterial::with('material')
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->get();

        foreach ($materials as $item) {
            $notifications->push([
                'title' => $item->status === 'Approved'
                    ? "LP & PPT \"{$item->ppt_title}\" disetujui"
                    : "LP & PPT \"{$item->ppt_title}\" perlu direvisi",
                'category' => 'Hasil Review LP & PPT',
                'time' => $item->approved_at ?? $item->updated_at,
                'note' => $item->status === 'Rejected' ? $item->review_note : null,
                'model' => $item,
                'type' => 'material',
            ]);
        }

        // ----- 3. Jurnal & Absensi -----
        $journals = TeachingJournal::with('class')
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['Reviewed', 'Revision'])
            ->get();

        foreach ($journals as $item) {
            $className = $item->class->class_name ?? '-';
            $revision  = $item->status === 'Revision';

            $notifications->push([
                'title'    => $revision
                    ? "Jurnal sesi {$className} perlu direvisi"
                    : "Jurnal sesi {$className} sudah direview",
                'category' => 'Hasil Review Jurnal & Absensi',
                'time'     => $item->reviewed_at ?? $item->updated_at,
                'note'     => $revision ? $item->review_note : null,
                'model'    => $item,
                'type'     => 'journal',
                'action'   => $revision
                    ? [
                        'kind'  => 'link',
                        'url'   => route('teacher.attendance', ['date' => $item->session_date->toDateString()]),
                        'label' => 'Perbaiki Jurnal',
                    ]
                    : null,
            ]);
        }

        // ----- 4. Progress Report -----
        // Disetujui = status Submitted + reviewed_at terisi.
        // Dikembalikan = status Draft + returned_at terisi (lengkap dengan alasan).
        $approved = ProgressReport::with('student')
            ->where('teacher_id', $teacher->id)
            ->approved()
            ->get();

        foreach ($approved as $item) {
            $studentName = $item->student->name ?? '-';

            $notifications->push([
                'title' => "Progress Report {$item->report_period} {$studentName} disetujui",
                'category' => 'Hasil Review Progress Report',
                'time' => $item->reviewed_at,
                'model' => $item,
                'type' => 'report',
            ]);
        }

        $returned = ProgressReport::with('student')
            ->where('teacher_id', $teacher->id)
            ->where('status', 'Draft')
            ->whereNotNull('returned_at')
            ->get();

        foreach ($returned as $item) {
            $studentName = $item->student->name ?? '-';

            $notifications->push([
                'title' => "Progress Report {$studentName} dikembalikan, perlu diperbaiki",
                'category' => 'Hasil Review Progress Report',
                'time' => $item->returned_at,
                'note' => $item->review_note,
                'model' => $item,
                'type' => 'report_returned',
                'action' => [
                    'kind'  => 'link',
                    'label' => 'Perbaiki',
                    'url'   => $item->enrollment_id
                        ? route('teacher.progress-report.form', $item->enrollment_id)
                        : route('teacher.progress-report'),
                ],
            ]);
        }

        // ----- 5. Cuti / Ganti Kelas -----
        $leaves = TeacherLeave::with('replacementTeacher')
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->get();

        foreach ($leaves as $item) {
            $penggantiText = $item->replacementTeacher
                ? " (pengganti: {$item->replacementTeacher->name})"
                : '';

            $notifications->push([
                'title' => $item->status === 'Approved'
                    ? "Cuti/Ganti kelas disetujui{$penggantiText}"
                    : "Cuti/Ganti kelas ditolak",
                'category' => 'Hasil Review Cuti/Ganti Kelas',
                'time' => $item->approved_at ?? $item->updated_at,
                'note' => $item->status === 'Rejected' ? $item->review_note : null,
                'model' => $item,
                'type' => 'leave',
            ]);
        }

        // ----- 6. Tugas perbaikan kelas dari Kurikulum -----
        $tasks = ClassTask::with('class')
            ->where('teacher_id', $teacher->id)
            ->where(function ($q) {
                $q->where('status', 'Open')
                    ->orWhere('done_at', '>=', now()->subDays(14));
            })
            ->get();

        foreach ($tasks as $task) {
            $className = $task->class->class_name ?? '-';
            $due = $task->due_date ? ' · batas ' . $task->due_date->translatedFormat('d M Y') : '';
            if ($task->is_overdue) {
                $due .= ' · TERLAMBAT';
            }

            $notifications->push([
                'title' => "Tugas perbaikan kelas {$className}: {$task->title}",
                'category' => 'Tugas dari Kurikulum' . $due,
                'time' => $task->created_at,
                'note' => $task->description,
                'model' => $task,
                'type' => 'task',
                'action' => $task->status === 'Open'
                    ? ['kind' => 'task', 'url' => route('teacher.tugas.complete', $task)]
                    : ['kind' => 'done'],
            ]);
        }

        // ----- 7. Kelas baru menunggu konfirmasi (terima / tolak) -----
        $assigned = ClassModel::where('teacher_id', $teacher->id)
            ->where('teacher_response', 'Pending')
            ->whereIn('status', ['Open', 'Running'])
            ->get();

        foreach ($assigned as $class) {
            $notifications->push([
                'title' => "Kelas {$class->class_name} diberikan kepada Anda, mohon konfirmasi",
                'category' => 'Penugasan Kelas',
                'time' => $class->updated_at,
                'model' => $class,
                'type' => 'class_assign',
                'action' => [
                    'kind'  => 'link',
                    'label' => 'Terima / Tolak',
                    'url'   => route('teacher.schedule'),
                ],
            ]);
        }

        // ----- 8. Permintaan dari guru lain agar saya menggantikan kelasnya -----
        $replaceRequests = TeacherLeave::with(['teacher', 'classSchedule.class'])
            ->where('replacement_teacher_id', $teacher->id)
            ->where('status', 'Pending')
            ->where('replacement_response', 'Pending')
            ->get();

        foreach ($replaceRequests as $item) {
            $className = optional(optional($item->classSchedule)->class)->class_name ?? '-';
            $when = $item->leave_date ? ' pada ' . $item->leave_date->translatedFormat('d M Y') : '';

            $notifications->push([
                'title' => ($item->teacher->name ?? 'Guru lain') . " meminta Anda menggantikan kelas {$className}{$when}",
                'category' => 'Permintaan Menggantikan',
                'time' => $item->created_at,
                'note' => $item->reason,
                'model' => $item,
                'type' => 'replacement_request',
                'action' => [
                    'kind'  => 'link',
                    'label' => 'Terima / Tolak',
                    'url'   => route('teacher.schedule'),
                ],
            ]);
        }

                // ----- Jadwal konsultasi dari Kurikulum -----
        $consultations = \App\Models\Consultation::with('teacherMaterial')
            ->where('teacher_id', $teacher->id)
            ->where(function ($q) {
                $q->where('status', 'Terjadwal')
                  ->orWhere(function ($q2) {
                      $q2->whereIn('status', ['Selesai', 'Dibatalkan'])
                         ->where('updated_at', '>=', now()->subDays(14));
                  });
            })
            ->get();

        foreach ($consultations as $c) {
            $when = $c->scheduled_at ? $c->scheduled_at->translatedFormat('l, d F Y \p\u\k\u\l H:i') : '-';

            $title = match ($c->status) {
                'Terjadwal'  => "Jadwal {$c->type_label}: {$when}",
                'Selesai'    => "{$c->type_label} sudah selesai",
                default      => "{$c->type_label} dibatalkan — silakan ajukan ulang jika diperlukan",
            };

            $note = collect([
                $c->notes,
                $c->teacherMaterial ? 'Terkait pengajuan: ' . $c->teacherMaterial->ppt_title : null,
            ])->filter()->implode(' · ');

            $notifications->push([
                'title'    => $title,
                'category' => 'Jadwal Konsultasi',
                'time'     => $c->updated_at,
                'note'     => $note ?: null,
                'model'    => $c,
                'type'     => 'consultation',
                'action'   => $c->status === 'Terjadwal' ? null : ['kind' => 'done'],
            ]);
        }

        // ----- Urutkan terbaru duluan -----
        $notifications = $notifications
            ->sortByDesc('time')
            ->values();

        return view('guru.notifikasi', compact('notifications'));
    }

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download(
            Storage::disk('public')->path($document->file_path),
            $document->title
        );
    }
}