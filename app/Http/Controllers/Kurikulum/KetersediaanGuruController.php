<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KetersediaanGuruController extends Controller
{
    private const MESSAGES = [
        'teacher_id.required'    => 'Pilih guru terlebih dahulu.',
        'teacher_id.exists'      => 'Guru tidak ditemukan atau tidak aktif.',
        'days.required'          => 'Pilih minimal satu hari.',
        'days.min'               => 'Pilih minimal satu hari.',
        'day.required'           => 'Pilih hari.',
        'start_time.required'    => 'Jam mulai wajib diisi.',
        'start_time.date_format' => 'Format jam mulai tidak valid.',
        'end_time.required'      => 'Jam selesai wajib diisi.',
        'end_time.date_format'   => 'Format jam selesai tidak valid.',
        'end_time.after'         => 'Jam selesai harus setelah jam mulai.',
    ];

    // ------------------------------------------------------------------ HALAMAN

    public function index()
    {
        $teachers = Teacher::with('availabilities')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        foreach ($teachers as $t) {
            $t->setRelation('availabilities', TeacherAvailabilityService::sortSlots($t->availabilities));
        }

        // Kelas yang perlu guru: belum punya guru, atau ditolak guru
        $needsAction = ClassModel::with(['teacher', 'schedules'])
            ->whereIn('status', ['Open', 'Running'])
            ->where(function ($q) {
                $q->where('teacher_response', 'Rejected')->orWhereNull('teacher_id');
            })
            ->orderBy('class_name')
            ->get();

        // Sudah dikirim ke guru, menunggu jawaban
        $waiting = ClassModel::with(['teacher', 'schedules'])
            ->whereIn('status', ['Open', 'Running'])
            ->whereNotNull('teacher_id')
            ->where('teacher_response', 'Pending')
            ->orderBy('class_name')
            ->get();

        // Satu kali query untuk semua jadwal yang sudah diterima guru
        $busy = TeacherAvailabilityService::busyIndex($teachers->pluck('id'));

        // Kecocokan tiap kelas x guru: sesuai ketersediaan? bentrok?
        $fit = [];
        foreach ($needsAction as $class) {
            foreach ($teachers as $t) {
                $fit[$class->id][$t->id] = [
                    'covers' => TeacherAvailabilityService::coversClass($t->availabilities, $class),
                    'clash'  => TeacherAvailabilityService::clashIn($busy->get($t->id, collect()), $class),
                ];
            }
        }

        // Beban mengajar saat ini per guru
        $load = [];
        foreach ($teachers as $t) {
            $rows  = $busy->get($t->id, collect());
            $hours = $rows->sum(fn ($r) => max(0, (strtotime($r->end_time) - strtotime($r->start_time)) / 3600));

            $load[$t->id] = [
                'classes' => $rows->pluck('class_id')->unique()->count(),
                'hours'   => round($hours, 1),
            ];
        }

        $days = TeacherAvailability::DAYS;

        $allSlots = collect();
        foreach ($teachers as $t) {
            foreach ($t->availabilities as $a) {
                $a->setRelation('teacher', $t);
                $allSlots->push($a);
            }
        }
        $availabilityGrid = TeacherAvailabilityService::weeklyGrid($allSlots);

        $notFilled   = $teachers->filter(fn ($t) => $t->availabilities->isEmpty())->values();
        $filledCount = $teachers->count() - $notFilled->count();

        return view('kurikulum.ketersediaan-guru', compact(
            'teachers', 'needsAction', 'waiting', 'fit', 'load', 'days',
            'availabilityGrid', 'notFilled', 'filledCount'
        ));
    }

    // ------------------------------------------------------------------ KETERSEDIAAN

    /** Tambah ketersediaan untuk satu atau beberapa hari sekaligus. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')->where('status', 'Active')],
            'days'       => ['required', 'array', 'min:1'],
            'days.*'     => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ], self::MESSAGES);

        [$created, $skipped] = TeacherAvailabilityService::addSlots(
            (int) $data['teacher_id'], $data['days'], $data['start_time'], $data['end_time'],
            $data['note'] ?? null, Auth::id()
        );

        if (!$created) {
            return back()->withInput()->with('error', 'Semua hari yang dipilih bertumpuk dengan ketersediaan guru tersebut yang sudah ada.');
        }

        $message = count($created) . ' slot ketersediaan ditambahkan (' . implode(', ', $created) . ').';
        if ($skipped) {
            $message .= ' Dilewati karena bertumpuk: ' . implode(', ', $skipped) . '.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, TeacherAvailability $availability)
    {
        $data = $request->validate([
            'day'        => ['required', Rule::in(TeacherAvailability::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'note'       => ['nullable', 'string', 'max:255'],
        ], self::MESSAGES);

        if (TeacherAvailabilityService::overlaps($availability->teacher_id, $data['day'], $data['start_time'], $data['end_time'], $availability->id)) {
            return back()->withInput()->with('error', 'Slot bertumpuk dengan ketersediaan guru tersebut di hari yang sama.');
        }

        $availability->update($data);

        return back()->with('success', 'Ketersediaan diperbarui.');
    }

    public function destroy(TeacherAvailability $availability)
    {
        $availability->delete();

        return back()->with('success', 'Ketersediaan dihapus.');
    }

    // ------------------------------------------------------------------ PENUGASAN KELAS

    /** Tugaskan (atau tugaskan ulang) guru ke kelas; guru harus menjawab lagi. */
    public function assign(Request $request, ClassModel $class)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')->where('status', 'Active')],
        ], self::MESSAGES);

        if (!in_array($class->status, ['Open', 'Running'], true)) {
            return back()->with('error', 'Kelas ini sudah tidak berjalan, tidak bisa ditugaskan.');
        }

        if ($class->teacher_id && $class->teacher_response === 'Accepted') {
            return back()->with('error', 'Kelas ini sudah punya guru yang menerima.');
        }

        $class->load('schedules');

        $clash = TeacherAvailabilityService::conflictWith((int) $data['teacher_id'], $class);
        if ($clash) {
            return back()->with('error', "Guru tersebut bentrok dengan kelas {$clash} yang sudah diterimanya, jadi tidak bisa ditugaskan.");
        }

        $class->update([
            'teacher_id'            => $data['teacher_id'],
            'teacher_response'      => 'Pending',
            'teacher_response_note' => null,
            'teacher_responded_at'  => null,
        ]);

        $teacher = Teacher::find($data['teacher_id']);

        return back()->with('success', "Kelas {$class->class_name} dikirim ke " . ($teacher->name ?? 'guru') . ' untuk dikonfirmasi.');
    }

    /** Batalkan penugasan yang belum dijawab guru, agar kelas bisa ditugaskan ke guru lain. */
    public function unassign(ClassModel $class)
    {
        if (!$class->teacher_id || $class->teacher_response !== 'Pending') {
            return back()->with('error', 'Hanya penugasan yang masih menunggu jawaban guru yang bisa dibatalkan.');
        }

        $class->update([
            'teacher_id'            => null,
            'teacher_response'      => 'Pending',
            'teacher_response_note' => null,
            'teacher_responded_at'  => null,
        ]);

        return back()->with('success', "Penugasan kelas {$class->class_name} dibatalkan. Kelas kembali ke daftar Kelas Perlu Guru.");
    }
}