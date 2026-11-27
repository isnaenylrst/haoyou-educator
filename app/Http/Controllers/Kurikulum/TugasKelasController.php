<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TugasKelasController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Tugas perbaikan kelas
    |--------------------------------------------------------------------------
    | Kurikulum membuat tugas untuk kelas yang perlu diperbaiki. Tugas
    | otomatis masuk ke Notifikasi guru pengampu kelas tersebut.
    |--------------------------------------------------------------------------
    */

    private const MESSAGES = [
        'class_id.required'        => 'Pilih kelas terlebih dahulu.',
        'class_id.exists'          => 'Kelas tidak ditemukan.',
        'title.required'           => 'Judul tugas wajib diisi.',
        'due_date.date'            => 'Format batas waktu tidak valid.',
        'due_date.after_or_equal'  => 'Batas waktu tidak boleh sebelum hari ini.',
        'reason.required'          => 'Alasan membuka kembali tugas wajib diisi.',
        'attachment.file'          => 'Lampiran harus berupa file.',
        'attachment.max'           => 'Ukuran lampiran maksimal 10 MB.',
        'attachment.mimes'         => 'Format lampiran yang diizinkan: PDF, Word, JPG, PNG.',
    ];

    /** Simpan file lampiran ke storage dan kembalikan [$path, $name]. */
    private function storeAttachment(Request $request): array
    {
        if (!$request->hasFile('attachment') || !$request->file('attachment')->isValid()) {
            return [null, null];
        }

        $file = $request->file('attachment');
        $path = $file->store('class-task-attachments', 'public');

        return [$path, $file->getClientOriginalName()];
    }

    /** Hapus file lampiran lama dari storage jika ada. */
    private function deleteAttachment(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // ------------------------------------------------------------------ LIST

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['Open', 'Overdue', 'Done'])],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $tasks = ClassTask::with(['class.teacher', 'teacher', 'creator', 'material'])
            ->when($status === 'Open', fn ($x) => $x->open())
            ->when($status === 'Overdue', fn ($x) => $x->overdue())
            ->when($status === 'Done', fn ($x) => $x->where('status', 'Done'))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhereHas('class', fn ($c) => $c->where('class_name', 'like', "%{$q}%"))
                        ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('material', fn ($m) => $m->where('title', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw(DB::getDriverName() === 'mysql' ? "FIELD(status,'Open','Done')" : "CASE status WHEN 'Open' THEN 1 WHEN 'Done' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')   // tanpa batas waktu di bawah
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Hanya kelas yang sudah punya guru yang bisa diberi tugas
        $classes = ClassModel::with('teacher')
            ->whereNotNull('teacher_id')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get();

        $teachers = \App\Models\Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);
        $materials = \App\Models\Material::orderBy('title')->get(['id', 'title', 'meeting_number']);

        $counts = [
            'all'     => ClassTask::count(),
            'Open'    => ClassTask::open()->count(),
            'Overdue' => ClassTask::overdue()->count(),
            'Done'    => ClassTask::where('status', 'Done')->count(),
        ];

        return view('kurikulum.tugas-kelas', compact('tasks', 'classes', 'teachers', 'materials', 'counts', 'status', 'q'));
    }

    // ------------------------------------------------------------------ BUAT

    public function store(Request $request)
    {
        $taskType = $request->input('task_type', 'class_task');

        if ($taskType === 'material_revision') {
            $data = $request->validate([
                'task_type'    => ['required', 'in:material_revision'],
                'teacher_ids'  => ['required', 'array', 'min:1'],
                'teacher_ids.*'=> ['required', 'integer', 'exists:teachers,id'],
                'material_id'  => ['nullable', 'integer', 'exists:materials,id'],
                'title'        => ['required', 'string', 'max:255'],
                'description'  => ['required', 'string', 'max:2000'],
                'due_date'     => ['nullable', 'date', 'after_or_equal:today'],
                'priority'     => ['required', 'in:Low,Medium,High'],
                'attachment'   => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            ], [
                'teacher_ids.required' => 'Pilih minimal satu guru tujuan.',
                'teacher_ids.min'      => 'Pilih minimal satu guru tujuan.',
                'title.required'       => 'Judul tugas wajib diisi.',
                'description.required' => 'Instruksi perbaikan materi wajib diisi.',
            ]);

            [$attachPath, $attachName] = $this->storeAttachment($request);

            try {
                DB::transaction(function () use ($data, $attachPath, $attachName) {
                    foreach ($data['teacher_ids'] as $teacherId) {
                        $task = ClassTask::create([
                            'class_id'        => null,
                            'teacher_id'      => $teacherId,
                            'task_type'       => 'material_revision',
                            'material_id'     => $data['material_id'] ?? null,
                            'title'           => trim($data['title']),
                            'description'     => $data['description'],
                            'due_date'        => $data['due_date'] ?? null,
                            'priority'        => $data['priority'],
                            'status'          => 'Open',
                            'created_by'      => Auth::id(),
                            'attachment_path' => $attachPath,
                            'attachment_name' => $attachName,
                        ]);

                        // Kirim notifikasi ke guru
                        $teacher = \App\Models\Teacher::with('user')->find($teacherId);
                        if ($teacher?->user) {
                            $dueTxt = $task->due_date ? ' (Tenggat: ' . $task->due_date->translatedFormat('d M Y') . ')' : '';
                            $teacher->user->notify(new \App\Notifications\AppNotification(
                                title: 'Tugas Perbaikan Materi: ' . $task->title,
                                message: 'Kurikulum memberikan tugas perbaikan materi: ' . \Illuminate\Support\Str::limit($task->description, 100) . $dueTxt,
                                url: url('/guru/tugas-kelas'),
                                type: 'tugas_materi',
                                icon: 'fa-clipboard-check',
                                actionLabel: 'Lihat Tugas'
                            ));
                        }
                    }
                });
            } catch (\Throwable $e) {
                $this->deleteAttachment($attachPath);
                report($e);

                return back()->withInput()->with('error', 'Gagal mengirim tugas perbaikan materi: ' . $e->getMessage());
            }

            return redirect()
                ->route('kurikulum.tugas-kelas')
                ->with('success', 'Tugas perbaikan materi berhasil ditugaskan ke guru terkait.');
        }

        // --- Alur tugas kelas lama ---
        $data = $request->validate([
            'class_id'    => ['required', 'integer', 'exists:classes,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:today'],
            'attachment'  => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ], self::MESSAGES);

        $class = ClassModel::with('teacher')->findOrFail($data['class_id']);

        if (!$class->teacher_id) {
            return back()->withInput()->withErrors([
                'class_id' => 'Kelas ini belum punya guru pengampu, tugas tidak bisa dikirim.',
            ]);
        }

        // Cegah tugas ganda yang masih terbuka
        $duplicate = ClassTask::open()
            ->where('class_id', $class->id)
            ->where('title', trim($data['title']))
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'title' => 'Tugas dengan judul yang sama untuk kelas ini masih terbuka.',
            ]);
        }

        [$attachPath, $attachName] = $this->storeAttachment($request);

        try {
            $task = ClassTask::create([
                'class_id'        => $class->id,
                'teacher_id'      => $class->teacher_id,
                'task_type'       => 'class_task',
                'title'           => trim($data['title']),
                'description'     => $data['description'] ?? null,
                'due_date'        => $data['due_date'] ?? null,
                'status'          => 'Open',
                'priority'        => 'Medium',
                'created_by'      => Auth::id(),
                'attachment_path' => $attachPath,
                'attachment_name' => $attachName,
            ]);

            if ($class->teacher?->user) {
                $dueTxt = $task->due_date ? ' (Tenggat: ' . $task->due_date->translatedFormat('d M Y') . ')' : '';
                $class->teacher->user->notify(new \App\Notifications\AppNotification(
                    title: 'Tugas Kelas: ' . $task->title,
                    message: 'Kurikulum menugaskan perbaikan untuk kelas ' . $class->class_name . $dueTxt,
                    url: url('/guru/tugas-kelas'),
                    type: 'tugas_kelas',
                    icon: 'fa-clipboard-list',
                    actionLabel: 'Lihat Tugas'
                ));
            }
        } catch (\Throwable $e) {
            // Hapus file yang baru diupload jika create gagal
            $this->deleteAttachment($attachPath);
            report($e);

            return back()->withInput()->with('error', 'Gagal mengirim tugas. Silakan coba lagi.');
        }

        return redirect()
            ->route('kurikulum.tugas-kelas')
            ->with('success', 'Tugas dikirim ke notifikasi ' . ($class->teacher->name ?? 'guru pengampu') . '.');
    }

    // ------------------------------------------------------------------ UBAH

    /**
     * Ubah tugas.
     * - Belum selesai : kelas, judul, deskripsi, batas waktu (tujuan guru ikut kelas yang dipilih).
     * - Sudah selesai : judul, deskripsi, batas waktu saja (kelas dikunci, status tetap Selesai).
     */
    public function update(Request $request, ClassTask $classTask)
    {
        $isOpen = $classTask->status === 'Open';
        $isMaterialRevision = $classTask->task_type === 'material_revision';

        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date'    => ['nullable', 'date'],
            'attachment'  => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];

        if ($isOpen && ! $isMaterialRevision) {
            $rules['class_id'] = ['required', 'integer', 'exists:classes,id'];
        }

        $data = $request->validate($rules, self::MESSAGES);

        $attrs = [
            'title'       => trim($data['title']),
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?? null,
        ];

        $message = 'Tugas diperbarui.';

        if ($isOpen) {
            // Batas waktu yang diubah tidak boleh sebelum hari ini
            $oldDue = $classTask->due_date?->toDateString();
            if (!empty($data['due_date']) && $data['due_date'] !== $oldDue && $data['due_date'] < today()->toDateString()) {
                return back()->withInput()->withErrors(['due_date' => self::MESSAGES['due_date.after_or_equal']]);
            }

            if (! $isMaterialRevision) {
                $class = ClassModel::with('teacher')->find($data['class_id']);

                if (!$class || !$class->teacher_id) {
                    return back()->withInput()->withErrors([
                        'class_id' => 'Kelas ini belum punya guru pengampu, tugas tidak bisa dikirim.',
                    ]);
                }

                $classChanged = (int) $class->id !== (int) $classTask->class_id;

                if ($classChanged && !in_array($class->status, ['Open', 'Running'], true)) {
                    return back()->withInput()->withErrors([
                        'class_id' => 'Kelas tujuan harus berstatus Open atau Running.',
                    ]);
                }

                // Cegah tugas ganda yang masih terbuka
                $duplicate = ClassTask::open()
                    ->where('class_id', $class->id)
                    ->where('title', $attrs['title'])
                    ->where('id', '!=', $classTask->id)
                    ->exists();

                if ($duplicate) {
                    return back()->withInput()->withErrors([
                        'title' => 'Tugas dengan judul yang sama untuk kelas ini masih terbuka.',
                    ]);
                }

                $attrs['class_id']   = $class->id;
                $attrs['teacher_id'] = $class->teacher_id;   // ikut guru pengampu terbaru

                if ($classChanged || (int) $class->teacher_id !== (int) $classTask->teacher_id) {
                    $message = 'Tugas diperbarui dan dikirim ke ' . ($class->teacher->name ?? 'guru pengampu') . '.';
                }
            }
        }

        // Jika ada file baru diupload, ganti lampiran lama
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $this->deleteAttachment($classTask->attachment_path);
            [$attrs['attachment_path'], $attrs['attachment_name']] = $this->storeAttachment($request);
        }

        try {
            $classTask->update($attrs);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal memperbarui tugas. Silakan coba lagi.');
        }

        return back()->with('success', $message);
    }

    // ------------------------------------------------------------------ BUKA KEMBALI

    /** Buka kembali tugas yang sudah ditandai selesai (hasil perbaikan belum sesuai). */
    public function reopen(Request $request, ClassTask $classTask)
    {
        if ($classTask->status !== 'Done') {
            return back()->with('error', 'Hanya tugas yang sudah selesai yang bisa dibuka kembali.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], self::MESSAGES);

        $class = $classTask->class;
        $teacherId = $classTask->teacher_id;

        if ($classTask->task_type !== 'material_revision') {
            if (!$class || !$class->teacher_id) {
                return back()->with('error', 'Kelas ini belum punya guru pengampu.');
            }
            $teacherId = $class->teacher_id;
        }

        // Alasan ditambahkan ke deskripsi agar terbaca guru di Notifikasi
        $append = "\n\n[Dibuka kembali " . now()->translatedFormat('d M Y') . '] ' . trim($data['reason']);
        if ($classTask->teacher_note) {
            $append .= "\n(Catatan guru sebelumnya: " . $classTask->teacher_note . ')';
        }

        $classTask->update([
            'status'       => 'Open',
            'done_at'      => null,
            'teacher_note' => null,
            'teacher_id'   => $teacherId,
            'description'  => mb_substr(trim(($classTask->description ?? '') . $append), 0, 4000),
        ]);

        return back()->with('success', 'Tugas dibuka kembali dan dikirim ulang ke guru.');
    }

    // ------------------------------------------------------------------ HAPUS

    public function destroy(ClassTask $classTask)
    {
        // Hapus file lampiran dari storage sebelum record dihapus
        $this->deleteAttachment($classTask->attachment_path);

        $classTask->delete();

        return redirect()
            ->route('kurikulum.tugas-kelas')
            ->with('success', 'Tugas dihapus.');
    }
}