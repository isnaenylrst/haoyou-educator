<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Consultation;
use App\Models\Teacher;
use App\Models\TeacherMaterial;
use App\Models\TeachingJournal;
use App\Services\ConsultationDeadline;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JadwalKonsultasiController extends Controller
{
    private const TYPES = ['Materi', 'Materi_Baru', 'LP_PPT', 'Direktur', 'Trial_Teaching'];
    private const TABS  = ['aktif', 'terlambat', 'selesai', 'dibatalkan', 'semua'];

    /** Kelas yang mulai dalam rentang ini ikut dicek untuk saran Konsultasi Materi Baru. */
    private const SUGGEST_WINDOW_DAYS = 60;

    private const MESSAGES = [
        'type.required'               => 'Pilih jenis konsultasi.',
        'teacher_id.exists'           => 'Guru tidak ditemukan atau tidak aktif.',
        'class_id.exists'             => 'Kelas tidak ditemukan atau sudah tidak berjalan.',
        'scheduled_at.after_or_equal' => 'Tanggal konsultasi tidak boleh sebelum hari ini.',
        'scheduled_at.date'           => 'Format tanggal dan jam tidak valid.',
        'scheduled_at.required'       => 'Tanggal dan jam wajib diisi.',
        'deadline_at.after_or_equal'  => 'Deadline tidak boleh sebelum hari ini.',
        'deadline_at.date'            => 'Format deadline tidak valid.',
        'notes.max'                   => 'Catatan maksimal 1000 karakter.',
    ];

    private const ATTRIBUTES = [
        'type'          => 'jenis konsultasi',
        'teacher_name'  => 'nama guru',
        'teacher_phone' => 'nomor WhatsApp',
        'class_id'      => 'kelas',
        'scheduled_at'  => 'tanggal dan jam',
        'deadline_at'   => 'deadline',
        'notes'         => 'catatan',
    ];

    // ------------------------------------------------------------------ LIST

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'aktif';

        $consultations = Consultation::with(['teacher', 'teacherMaterial', 'class'])
            ->when($tab === 'aktif', fn ($q) => $q->active())
            ->when($tab === 'terlambat', fn ($q) => $q->overdue())
            ->when($tab === 'selesai', fn ($q) => $q->where('status', 'Selesai'))
            ->when($tab === 'dibatalkan', fn ($q) => $q->where('status', 'Dibatalkan'))
            ->orderByRaw("FIELD(status,'Belum Terjadwal','Terjadwal','Selesai','Dibatalkan')")
            ->orderByRaw('deadline_at IS NULL')   // yang punya deadline lebih dulu
            ->orderBy('deadline_at')
            ->orderBy('scheduled_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($request->query());

        $counts = Consultation::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $overdueCount = Consultation::overdue()->count();

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'phone']);

        // Kelas yang bisa dipilih + info mengajar pertama & deadline (dipakai pratinjau di form)
        $classes = ClassModel::with(['teacher', 'schedules'])
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('start_date')
            ->get();

        $classInfo = [];
        foreach ($classes as $class) {
            $first    = ConsultationDeadline::firstSession($class);
            $deadline = ConsultationDeadline::deadlineFor($class);

            $classInfo[$class->id] = [
                'teacher'       => $class->teacher?->name,
                'start'         => $class->start_date?->translatedFormat('d M Y'),
                'first'         => $first?->translatedFormat('l, d F Y'),
                'deadline'      => $deadline?->translatedFormat('l, d F Y'),
                'deadline_iso'  => $deadline?->toDateString(),
                'deadline_past' => $deadline ? $deadline->lt(today()) : false,
            ];
        }

        $suggestions = $this->suggestions();

        return view('kurikulum.jadwal-konsultasi', compact(
            'consultations', 'teachers', 'counts', 'tab', 'overdueCount',
            'classes', 'classInfo', 'suggestions'
        ));
    }

    /**
     * Saran Konsultasi Materi Baru: kelas yang akan mulai, gurunya sudah menerima,
     * gurunya BELUM PERNAH mengajar level tersebut, dan konsultasinya belum dibuat.
     */
    private function suggestions(): Collection
    {
        $classes = ClassModel::with(['teacher', 'schedules', 'level'])
            ->whereIn('status', ['Open', 'Running'])
            ->where('teacher_response', 'Accepted')
            ->whereNotNull('teacher_id')
            ->whereNotNull('level_id')
            ->whereDate('start_date', '>=', today())
            ->whereDate('start_date', '<=', today()->addDays(self::SUGGEST_WINDOW_DAYS))
            ->orderBy('start_date')
            ->get();

        if ($classes->isEmpty()) {
            return collect();
        }

        // pasangan guru-level yang pernah diajar (dari jurnal kelas berjalan)
        $taught = TeachingJournal::query()
            ->join('classes', 'classes.id', '=', 'teaching_journals.class_id')
            ->where('teaching_journals.class_status', 'Conducted')
            ->whereIn('teaching_journals.teacher_id', $classes->pluck('teacher_id')->unique())
            ->whereIn('classes.level_id', $classes->pluck('level_id')->unique())
            ->selectRaw('teaching_journals.teacher_id as t_id, classes.level_id as l_id')
            ->distinct()
            ->get()
            ->map(fn ($r) => $r->t_id . '-' . $r->l_id)
            ->flip();

        // konsultasi materi baru yang sudah ada (selain yang dibatalkan)
        $already = Consultation::where('type', 'Materi_Baru')
            ->where('status', '!=', 'Dibatalkan')
            ->whereIn('class_id', $classes->pluck('id'))
            ->get()
            ->map(fn ($c) => $c->class_id . '-' . $c->teacher_id)
            ->flip();

        return $classes->map(function ($class) use ($taught, $already) {
            if (isset($taught[$class->teacher_id . '-' . $class->level_id])) {
                return null;
            }
            if (isset($already[$class->id . '-' . $class->teacher_id])) {
                return null;
            }

            $deadline = ConsultationDeadline::deadlineFor($class);

            return [
                'class'     => $class,
                'first'     => ConsultationDeadline::firstSession($class),
                'deadline'  => $deadline,
                'days_left' => $deadline ? (int) today()->diffInDays($deadline, false) : null,
            ];
        })
            ->filter()
            ->sortBy(fn ($row) => $row['deadline']?->timestamp ?? PHP_INT_MAX)
            ->values();
    }

    // ------------------------------------------------------------------ BUAT

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'            => ['required', Rule::in(self::TYPES)],
            'teacher_name'    => ['nullable', 'string', 'max:150'],
            'teacher_phone'   => ['nullable', 'string', 'max:30'],
            'class_id'        => ['nullable', 'integer', Rule::exists('classes', 'id')->whereIn('status', ['Open', 'Running'])],
            'scheduled_at'    => ['nullable', 'date', 'after_or_equal:today'],
            'deadline_at'     => ['nullable', 'date', 'after_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'notify_whatsapp' => ['nullable', 'boolean'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $type  = $data['type'];
        $class = !empty($data['class_id'])
            ? ClassModel::with(['teacher', 'schedules'])->find($data['class_id'])
            : null;

        if ($type === 'Materi_Baru' && !$class) {
            return back()->withInput()->withErrors([
                'class_id' => 'Pilih kelas untuk Konsultasi Materi Baru (dipakai menghitung deadline H-2 minggu sebelum mengajar).',
            ]);
        }

        // ---- Guru: diketik manual. Jika cocok dengan guru terdaftar, otomatis tertaut ke akunnya.
        $name    = trim((string) ($data['teacher_name'] ?? ''));
        $teacher = null;

        if ($name !== '') {
            $teacher = Teacher::where('status', 'Active')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
        } elseif ($class?->teacher) {
            $teacher = $class->teacher;   // nama dikosongkan: pakai guru pengampu kelas
            $name    = $teacher->name;
        }

        if ($name === '') {
            return back()->withInput()->withErrors(['teacher_name' => 'Nama guru wajib diisi.']);
        }

        $teacherName = $teacher->name ?? $name;
        $phone       = trim((string) ($data['teacher_phone'] ?? '')) ?: ($teacher->phone ?? null);

        // ---- Jadwal & deadline
        $scheduledAt = !empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        $deadline    = null;
        $warnings    = [];

        if ($type === 'Materi_Baru') {
            $deadline = ConsultationDeadline::deadlineFor($class);

            if ($deadline && $scheduledAt && $deadline->gte(today()) && $scheduledAt->gt($deadline->copy()->endOfDay())) {
                return back()->withInput()->withErrors([
                    'scheduled_at' => 'Konsultasi Materi Baru harus paling lambat ' . $deadline->translatedFormat('d F Y')
                        . ' (H-' . ConsultationDeadline::LEAD_DAYS . ' sebelum mengajar pertama).',
                ]);
            }

            if ($deadline && $deadline->lt(today())) {
                $warnings[] = 'Deadline konsultasi (' . $deadline->translatedFormat('d F Y') . ') sudah lewat. Jadwalkan secepatnya.';
            }

            if ($teacher && ConsultationDeadline::teacherHasTaught($teacher->id, $class->level_id)) {
                $warnings[] = 'Guru ini pernah mengajar level kelas tersebut. Pastikan konsultasi materi baru memang diperlukan.';
            }
        } elseif (!empty($data['deadline_at'])) {
            $deadline = Carbon::parse($data['deadline_at']);
        }

        // ---- Cegah jadwal ganda yang masih berjalan
        $duplicate = Consultation::active()
            ->where('type', $type)
            ->when(
                $teacher,
                fn ($q) => $q->where('teacher_id', $teacher->id),
                fn ($q) => $q->whereNull('teacher_id')->where('teacher_name', $teacherName)
            )
            ->when($class, fn ($q) => $q->where('class_id', $class->id))
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Guru ini sudah punya jadwal konsultasi jenis yang sama' . ($class ? ' untuk kelas tersebut' : '') . ' yang masih berjalan.');
        }

        // ---- LP & PPT / Direktur: tertaut ke pengajuan guru yang sedang berjalan (alur lama tetap)
        $submissionId = null;
        if ($teacher && in_array($type, ['LP_PPT', 'Direktur'], true)) {
            $submissionId = TeacherMaterial::where('teacher_id', $teacher->id)
                ->where('status', '!=', 'Approved')
                ->latest()
                ->value('id');

            if ($type === 'Direktur' && $submissionId) {
                $kurikulumSelesai = Consultation::where('teacher_material_id', $submissionId)
                    ->where('type', 'LP_PPT')
                    ->where('status', 'Selesai')
                    ->exists();

                if (!$kurikulumSelesai) {
                    return back()->withInput()->with('error', 'Konsultasi Kurikulum untuk pengajuan guru ini belum selesai, jadi Konsultasi Direktur belum bisa dibuat.');
                }
            }
        }

        try {
            Consultation::create([
                'teacher_id'          => $teacher?->id,
                'teacher_name'        => $teacherName,
                'teacher_phone'       => $phone,
                'class_id'            => $class?->id,
                'teacher_material_id' => $submissionId,
                'type'                => $type,
                'scheduled_at'        => $scheduledAt,
                'deadline_at'         => $deadline?->toDateString(),
                'status'              => $scheduledAt ? 'Terjadwal' : 'Belum Terjadwal',
                'notes'               => $data['notes'] ?? null,
                'notify_whatsapp'     => $request->boolean('notify_whatsapp'),
                'created_by'          => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan jadwal konsultasi. Silakan coba lagi.');
        }

        $redirect = redirect()->route('kurikulum.jadwal-konsultasi')->with('success', 'Jadwal konsultasi berhasil dibuat.');

        if (!$teacher) {
            $warnings[] = "Guru \"{$teacherName}\" belum terdaftar di website, jadi tidak menerima notifikasi di akunnya. Kabari lewat WhatsApp.";
        }
        if ($warnings) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    // ------------------------------------------------------------------ UBAH STATUS

    /** Jadwalkan / ganti jadwal. */
    public function update(Request $request, Consultation $consultation)
    {
        if (!in_array($consultation->status, ['Belum Terjadwal', 'Terjadwal'], true)) {
            return back()->with('error', 'Konsultasi yang sudah selesai atau dibatalkan tidak bisa dijadwalkan ulang.');
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'deadline_at'  => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $scheduledAt = Carbon::parse($data['scheduled_at']);

        $attrs = [
            'scheduled_at' => $scheduledAt,
            'notes'        => $data['notes'] ?? null,
            'status'       => 'Terjadwal',
        ];

        if ($consultation->type === 'Materi_Baru' && $consultation->class) {
            // deadline dihitung ulang dari data kelas (bisa saja tanggal mulai kelas berubah)
            $deadline = ConsultationDeadline::deadlineFor($consultation->class->loadMissing('schedules'));

            if ($deadline && $deadline->gte(today()) && $scheduledAt->gt($deadline->copy()->endOfDay())) {
                return back()->withInput()->withErrors([
                    'scheduled_at' => 'Konsultasi Materi Baru harus paling lambat ' . $deadline->translatedFormat('d F Y')
                        . ' (H-' . ConsultationDeadline::LEAD_DAYS . ' sebelum mengajar pertama).',
                ]);
            }

            $attrs['deadline_at'] = $deadline?->toDateString();
        } elseif ($request->has('deadline_at')) {
            $attrs['deadline_at'] = $data['deadline_at'] ?: null;
        }

        $consultation->update($attrs);

        return back()->with('success', 'Jadwal konsultasi berhasil disimpan.');
    }

    public function complete(Consultation $consultation)
    {
        if ($consultation->status !== 'Terjadwal') {
            return back()->with('error', 'Hanya konsultasi berstatus Terjadwal yang bisa ditandai selesai.');
        }

        $consultation->update(['status' => 'Selesai']);

        return back()->with('success', 'Konsultasi ditandai selesai.');
    }

    public function cancel(Consultation $consultation)
    {
        if (!in_array($consultation->status, ['Belum Terjadwal', 'Terjadwal'], true)) {
            return back()->with('error', 'Konsultasi ini sudah selesai atau sudah dibatalkan.');
        }

        $consultation->update(['status' => 'Dibatalkan']);

        return back()->with('success', 'Konsultasi dibatalkan.');
    }

    public function destroy(Consultation $consultation)
    {
        $consultation->delete();

        return back()->with('success', 'Jadwal konsultasi dihapus.');
    }
}