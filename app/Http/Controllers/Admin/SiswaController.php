<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\PrivatePackage;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\TeachingJournal;
use App\Services\ClassScheduleService;
use App\Services\ClassSessionService;
use App\Services\ExtraFeeService;
use App\Services\PaymentInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiswaController extends Controller
{
    private const CONTINUE_DRAFT = 'continue_draft.';
    private const ATTENDED_STATUSES = ['Present', 'Late'];
    private const PAYMENT_METHODS = ['Transfer Bank', 'Cash', 'QRIS'];

    public function index(Request $request)
    {
        $query = Student::query()
            ->with([
                'user',
                'candidateStudent',
                'currentLevel.category',
                'enrollments.class',
                'activeEnrollment.class.programPackage.program',
                'activeEnrollment.class.schedules',
                'activeEnrollment.programPackage.program',
                'activeEnrollment.privatePackage',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('candidateStudent', function ($c) use ($search) {
                      $c->where('school', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('enrollments', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $students = $query->latest('id')
            ->paginate(15)
            ->withQueryString();
        
        $attendedMap = $this->attendedByStudentClass($students->pluck('id'));
        $heldMap     = $this->heldByClass(
            $students->map(fn ($s) => $s->activeEnrollment?->class_id)->filter()->unique()->values()
        );

        $classes = ClassModel::orderBy('class_name')->get(['id', 'class_name']);

        $stats = $this->getStats();

        return view('admin.siswa', compact('students', 'classes', 'stats', 'attendedMap', 'heldMap'));
    }

    private function getStats(): array
    {
        $totalActive = Student::where('status', 'Active')->count();

        $totalHsk = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->whereIn('status', ['Active', 'Waiting Class'])
                  ->where(function ($q2) {
                      $q2->whereHas('programPackage.program', fn ($p) => $p->where('program_name', 'HSK'))
                         ->orWhereHas('class.programPackage.program', fn ($p) => $p->where('program_name', 'HSK'));
                  });
            })->count();

        $totalDailyActivity = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->whereIn('status', ['Active', 'Waiting Class'])
                  ->where(function ($q2) {
                      $q2->whereHas('programPackage.program', fn ($p) => $p->where('program_name', 'Daily Activity'))
                         ->orWhereHas('class.programPackage.program', fn ($p) => $p->where('program_name', 'Daily Activity'));
                  });
            })->count();

        $totalPrivate = Student::where('status', 'Active')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')
                  ->whereNotNull('private_package_id');
            })->count();

        return [
            'total_active' => $totalActive,
            'total_hsk' => $totalHsk,
            'total_daily_activity' => $totalDailyActivity,
            'total_private' => $totalPrivate,
        ];
    }

    /**
     * Jumlah pertemuan yang diikuti tiap siswa per kelas.
     * Hasil: [student_id => [class_id => jumlah_hadir]]
     */
    private function attendedByStudentClass($studentIds)
    {
        return Attendance::query()
            ->join('teaching_journals', 'teaching_journals.id', '=', 'attendances.teaching_journal_id')
            ->whereIn('attendances.student_id', $studentIds)
            ->whereIn('attendances.status', self::ATTENDED_STATUSES)
            ->where('teaching_journals.class_status', 'Conducted')
            ->selectRaw('attendances.student_id, teaching_journals.class_id, COUNT(DISTINCT attendances.teaching_journal_id) AS attended')
            ->groupBy('attendances.student_id', 'teaching_journals.class_id')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('attended', 'class_id'));
    }

    /** Jumlah pertemuan yang sudah berlangsung per kelas: [class_id => jumlah] */
    private function heldByClass($classIds)
    {
        return TeachingJournal::query()
            ->whereIn('class_id', $classIds)
            ->where('class_status', 'Conducted')
            ->selectRaw('class_id, COUNT(DISTINCT COALESCE(class_session_id, id)) AS held')
            ->groupBy('class_id')
            ->pluck('held', 'class_id');
    }    

    public function backfillProgramPackages()
    {
        $enrollments = ClassEnrollment::whereNotNull('class_id')
            ->whereNull('program_package_id')
            ->with('class')
            ->get();

        $updated = 0;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->class && $enrollment->class->program_package_id) {
                $enrollment->update([
                    'program_package_id' => $enrollment->class->program_package_id,
                ]);
                $updated++;
            }
        }

        return response()->json([
            'message' => "Backfill selesai. {$updated} enrollment diperbarui.",
            'total_checked' => $enrollments->count(),
        ]);
    }

    public function edit(Student $siswa)
    {
        $siswa->load('activeEnrollment.class', 'activeEnrollment.privatePackage');

        $activeEnrollment = $siswa->activeEnrollment;
        $isPrivate = (bool) $activeEnrollment?->private_package_id;

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'status' => $siswa->status,
            'points' => $siswa->points,
            'join_date' => optional($siswa->join_date)->format('Y-m-d'),
            'is_private' => $isPrivate,
            'private_package_name' => $isPrivate
                ? ($activeEnrollment->privatePackage->package_name ?? 'Private')
                : null,
        ]);
    }

    public function update(Request $request, Student $siswa)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Graduated'],
            'points' => ['required', 'integer', 'min:0'],
        ]);

        $siswa->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'points' => $validated['points'],
        ]);

        $currentEnrollment = $siswa->activeEnrollment;

        if ($currentEnrollment && $currentEnrollment->private_package_id) {
            return redirect()
                ->route('admin.siswa')
                ->with('success', "Data siswa {$siswa->name} berhasil diperbarui. Kelas tidak diubah karena siswa sedang mengikuti paket Private — gunakan \"Lanjut Program Berikutnya\" untuk menggantinya.");
        }

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$siswa->name} berhasil diperbarui.");
    }

    public function show(Student $siswa)
    {
        $siswa->load([
            'candidateStudent',
            'user',
            'enrollments.class.programPackage.program',
            'enrollments.programPackage.program',
            'enrollments.privatePackage',
            'activeEnrollment.class.schedules',
            'progressReports',

            // Riwayat pembayaran siswa: semua jenis pembayaran
            'payments.session',
            'payments.enrollment.class.programPackage.program',
            'payments.enrollment.programPackage.program',
            'payments.enrollment.privatePackage',
        ]);

        // Jumlah pertemuan yang diikuti siswa per kelas: [class_id => jumlah_hadir]
        $attendedByClass = $this->attendedByStudentClass([$siswa->id])->get($siswa->id, collect());

        return response()->json([
            'id' => $siswa->id,
            'name' => $siswa->name,
            'phone' => $siswa->user->phone ?? $siswa->candidateStudent->phone ?? '-',
            'age' => $siswa->age,
            'status' => $siswa->status,
            'points' => $siswa->points,
            'join_date' => optional($siswa->join_date)->format('Y-m-d'),

            // Form kelas pengganti: pendaftaran Active yang bisa diberi pertemuan tambahan + tarif awal form
            'replacement_enrollments' => $siswa->enrollments->where('status', 'Active')->map(fn ($e) => [
                'id'         => $e->id,
                'class_name' => $e->class->class_name ?? '-',
            ])->values(),
            'fees' => [
                'replacement' => ExtraFeeService::FEE_REPLACEMENT,
                'home_visit'  => ExtraFeeService::FEE_HOME_VISIT,
            ],
            'replacements' => $this->replacementHistory($siswa),

            'schedules' => $siswa->activeEnrollment?->class?->schedules->map(fn ($s) => [
                'day' => $s->day,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ]) ?? [],

            'programs' => $siswa->enrollments->map(function ($e) use ($attendedByClass) {
                $attended = $e->class_id
                    ? (int) ($attendedByClass[$e->class_id] ?? 0)
                    : null;

                if ($e->private_package_id) {
                    return [
                        'class_name' => $e->class->class_name ?? '-',
                        'program_name' => $e->privatePackage->package_name ?? '-',
                        'course_type' => 'Private',
                        'enrollment_status' => $e->status,
                        'enrollment_date' => optional($e->enrollment_date)->format('d M Y'),
                        'attended' => $attended,
                        'total_meetings' => $e->privatePackage->total_meetings ?? null,
                    ];
                }

                $programPackage = $e->programPackage ?? $e->class->programPackage ?? null;

                return [
                    'class_name' => $e->class->class_name ?? '-',
                    'program_name' => $programPackage->program->program_name ?? '-',
                    'course_type' => 'Reguler',
                    'enrollment_status' => $e->status,
                    'enrollment_date' => optional($e->enrollment_date)->format('d M Y'),
                    'attended' => $attended,
                    'total_meetings' => $programPackage->total_meetings ?? null,
                ];
            }),

            'progress_reports' => $siswa->progressReports->map(fn ($r) => [
                'report_type' => $r->report_type,
                'report_period' => $r->report_period,
                'status' => $r->status,
                'uploaded_at' => optional($r->uploaded_at)->format('d M Y'),
                'file_url' => $r->file_path ? Storage::url($r->file_path) : null,
            ]),

            'payments' => $siswa->payments->map(function ($p) {
                $enrollment = $p->enrollment;

                if ($enrollment?->private_package_id) {
                    $programName = $enrollment->privatePackage->package_name ?? '-';
                } elseif ($enrollment) {
                    $programPackage = $enrollment->programPackage
                        ?? $enrollment->class?->programPackage;

                    $programName = $programPackage?->program?->program_name ?? '-';
                } else {
                    $programName = '-';
                }

                return [
                    'invoice_number' => $p->invoice_number,
                    'payment_type' => $p->payment_type,
                    'payment_stage' => $p->payment_stage,
                    'session_date' => $p->session?->session_date?->format('d M Y'),
                    'program_name' => $programName,

                    'total_bill' => $p->total_bill,
                    'amount_paid' => $p->amount_paid,
                    'remaining_bill' => $p->remaining_bill,
                    'status' => $p->status,

                    'payment_date' => optional($p->payment_date)->format('d M Y'),

                    'proof_url' => $p->payment_proof_path
                        ? Storage::url($p->payment_proof_path)
                        : null,

                    'invoice_url' => $p->invoice_file_path
                        ? Storage::url($p->invoice_file_path)
                        : route('admin.pembayaran.invoice', $p->id),
                ];
            }),
        ]);
    }

    /* =====================================================================
     |  KELAS PENGGANTI (Rp175.000, dibayar dulu baru dijadwalkan)
     |  Home Visit pada kelas pengganti ditambah Rp50.000 (dua pembayaran, dua invoice).
     * ===================================================================== */

    /** Riwayat kelas pengganti siswa beserta pembayaran dan invoicenya. */
    private function replacementHistory(Student $siswa)
    {
        // withTrashed: pendaftaran yang sudah di-soft-delete tetap menampilkan riwayatnya
        $enrollmentIds = ClassEnrollment::withTrashed()->where('student_id', $siswa->id)->pluck('id');

        return ClassSession::with(['teacher:id,name', 'payments'])
            ->where('kind', 'Replacement')
            ->whereIn('class_enrollment_id', $enrollmentIds)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (ClassSession $s) => [
                'id'            => $s->id,
                'date'          => $s->session_date->format('d M Y'),
                'time'          => substr($s->start_time, 0, 5) . ' - ' . substr($s->end_time, 0, 5),
                'teacher'       => $s->teacher?->name,
                'location_type' => $s->location_type,
                'room'          => $s->room,
                'status'        => $s->status,
                'payments'      => $s->payments->map(fn ($p) => [
                    'type'           => $p->payment_type,
                    'amount'         => (float) $p->amount_paid,
                    'invoice_number' => $p->invoice_number,
                    'invoice_url'    => route('admin.pembayaran.invoice', $p->id),
                ])->values(),
            ])
            ->values();
    }

    /** Guru yang tersedia (jam tersedia + tidak bentrok) pada tanggal dan jam tertentu. */
    public function replacementTeachers(Request $request, ClassScheduleService $schedules)
    {
        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $day = ClassScheduleService::DAYS[Carbon::parse($data['session_date'])->dayOfWeekIso - 1];

        $list = Teacher::orderBy('name')->get(['id', 'name'])
            ->filter(function ($t) use ($schedules, $data, $day) {
                if (! $schedules->isTeacherAvailable($t->id, $day, $data['start_time'], $data['end_time'])) {
                    return false;
                }

                try {
                    $schedules->assertTeacherFreeOn($t->id, $data['session_date'], $data['start_time'], $data['end_time']);

                    return true;
                } catch (ValidationException) {
                    return false;
                }
            })
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        return response()->json($list);
    }

    /**
     * Tambah kelas pengganti. Pertemuan dan pembayarannya dibuat dalam satu transaksi
     * (ClassSessionService::createReplacement), jadi jadwal yang bentrok tidak meninggalkan pembayaran.
     */
    public function storeReplacement(Request $request, Student $siswa, ClassSessionService $service)
    {
        $data = $request->validate([
            // Hanya pendaftaran Active milik siswa ini
            'class_enrollment_id' => ['required', 'integer', Rule::exists('class_enrollments', 'id')->where(
                fn ($q) => $q->where('student_id', $siswa->id)->where('status', 'Active')->whereNull('deleted_at')
            )],
            'teacher_id'          => ['required', 'exists:teachers,id'],
            'session_date'        => ['required', 'date', 'after_or_equal:today'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i', 'after:start_time'],
            'location_type'       => ['required', Rule::in(['Studio', 'Online', 'Home Visit'])],
            'room'                => ['nullable', 'string', 'max:255'],

            'payment_date'        => ['required', 'date', 'before_or_equal:today'],
            'payment_method'      => ['required', Rule::in(self::PAYMENT_METHODS)],
            'amount'              => ['nullable', 'numeric', 'min:1'],
            'home_visit_amount'   => ['nullable', 'numeric', 'min:1'],
            'proof'               => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'class_enrollment_id.exists' => 'Pendaftaran tidak ditemukan atau tidak aktif.',
            'end_time.after'             => 'Jam selesai harus setelah jam mulai.',
        ]);

        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        try {
            $session = $service->createReplacement([
                'class_enrollment_id' => $data['class_enrollment_id'],
                'teacher_id'          => $data['teacher_id'],
                'session_date'        => $data['session_date'],
                'start_time'          => $data['start_time'],
                'end_time'            => $data['end_time'],
                'location_type'       => $data['location_type'],
                'room'                => $data['room'] ?? null,

                'payment_date'        => $data['payment_date'],
                'payment_method'      => $data['payment_method'],
                'payment_proof_path'  => $proofPath,
                'amount'              => $data['amount'] ?? null,
                'home_visit_amount'   => $data['home_visit_amount'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        $total = (float) $session->payments->sum('amount_paid');
        $count = $session->payments->count();

        return back()->with('success', 'Kelas pengganti dijadwalkan. Pembayaran Rp'
            . number_format($total, 0, ',', '.') . ' tercatat lunas (' . $count . ' invoice).');
    }

    public function destroy(Student $siswa)
    {
        $name = $siswa->name;
        $hasAttendance = $siswa->attendances()->exists();
        $hasProgressReports = $siswa->progressReports()->exists();
        $hasPayments = $siswa->enrollments()
            ->whereHas('packagePayments')
            ->exists();

        if ($hasAttendance || $hasProgressReports || $hasPayments) {
            return redirect()
                ->route('admin.siswa')
                ->with('error', "Data siswa {$name} tidak bisa dihapus karena sudah punya riwayat absensi/laporan/pembayaran. Ubah status siswa menjadi Cuti atau Lulus sebagai gantinya.");
        }

        $siswa->enrollments()->delete();
        $siswa->delete();

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Data siswa {$name} berhasil dihapus.");
    }

    public function waitingClassOptions(ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status !== 'Waiting Class', 404, 'Enrollment ini tidak sedang menunggu kelas.');

        $enrollment->load('student.candidateStudent.availableSchedules');

        $classOptions = ClassModel::with('schedules')
            ->withCount(['enrollments as terisi' => fn ($q) => $q->where('status', 'Active')])
            ->where('program_package_id', $enrollment->program_package_id)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'class_name' => $class->class_name,
                'delivery_mode' => $class->delivery_mode,
                'status' => $class->status,
                'kuota_text' => $class->capacity !== null
                    ? "{$class->terisi}/{$class->capacity}"
                    : null,
                'is_full' => $class->capacity !== null && $class->terisi >= $class->capacity,
                'schedule_text' => $class->schedules
                    ->map(fn ($s) => substr($s->day, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
                    ->implode(', '),
            ]);

        $studentSchedules = $enrollment->student?->candidateStudent?->availableSchedules
            ?->map(fn ($s) => [
                'day' => $s->day,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
            ]) ?? collect();

        return response()->json([
            'class_options' => $classOptions,
            'student_schedules' => $studentSchedules,
        ]);
    }

    public function assignClass(Request $request, ClassEnrollment $enrollment)
    {
        if ($enrollment->status !== 'Waiting Class') {
            return redirect()
                ->route('admin.siswa')
                ->with('error', 'Enrollment ini tidak sedang menunggu kelas.');
        }

        $validated = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('classes', 'id')->whereIn('status', ['Open', 'Running']),
            ],
        ]);

        try {
            $class = DB::transaction(function () use ($validated, $enrollment) {
                // kunci baris kelas supaya dua admin tidak mengisi kursi terakhir bersamaan
                $class = ClassModel::lockForUpdate()->findOrFail($validated['class_id']);

                if ($enrollment->program_package_id
                    && (int) $class->program_package_id !== (int) $enrollment->program_package_id) {
                    throw new \DomainException('Kelas yang dipilih tidak sesuai dengan paket siswa.');
                }

                if ($class->capacity !== null) {
                    $terisi = $class->enrollments()->where('status', 'Active')->count();

                    if ($terisi >= $class->capacity) {
                        throw new \DomainException("Kelas {$class->class_name} sudah penuh ({$terisi}/{$class->capacity}).");
                    }
                }

                $enrollment->update([
                    'class_id'           => $class->id,
                    'program_package_id' => $enrollment->program_package_id ?? $class->program_package_id,
                    'status'             => 'Active',
                ]);

                return $class;
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.siswa')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.siswa')
            ->with('success', "Siswa {$enrollment->student->name} berhasil di-assign ke {$class->class_name}.");
    }

    /* =====================================================================
     |  LANJUT PROGRAM: helper bersama
     * ===================================================================== */

    private function buildRecommendation(?ClassEnrollment $currentEnrollment): array
    {
        if (! $currentEnrollment) {
            return ['type' => null];
        }

        if ($currentEnrollment->private_package_id) {
            return [
                'type' => 'private',
                'private_package_id' => $currentEnrollment->private_package_id,
                'label' => 'Rekomendasi: lanjutkan paket Private yang sama — '
                    . ($currentEnrollment->privatePackage->package_name ?? '-'),
            ];
        }

        $currentPackage = $currentEnrollment->class->programPackage ?? $currentEnrollment->programPackage ?? null;

        if (! $currentPackage || ! $currentPackage->level_id) {
            return ['type' => null];
        }

        $currentLevel = ProgramLevel::find($currentPackage->level_id);

        if (! $currentLevel) {
            return ['type' => null];
        }

        $nextLevel = ProgramLevel::where('category_id', $currentLevel->category_id)
            ->where('sort_order', '>', $currentLevel->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (! $nextLevel) {
            return ['type' => null]; // sudah di level tertinggi kategori ini
        }

        $nextPackage = ProgramPackage::where('program_id', $currentPackage->program_id)
            ->where('category_id', $currentLevel->category_id)
            ->where('level_id', $nextLevel->id)
            ->first();

        if (! $nextPackage) {
            return ['type' => null]; // paket level berikutnya belum dibuat
        }

        $recommendedClass = ClassModel::where('program_package_id', $nextPackage->id)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->first();

        return [
            'type' => 'program',
            'program_package_id' => $nextPackage->id,
            'class_id' => $recommendedClass->id ?? null,
            'label' => 'Rekomendasi: lanjut ke — ' . $nextPackage->package_name,
        ];
    }

    private function hasOutstandingBalance(?ClassEnrollment $enrollment): bool
    {
        if (! $enrollment) {
            return false;
        }

        $payments = app(PaymentInstallmentService::class);

        if ($payments->totalBillFor($enrollment) === null) {
            return false; // tagihan tidak bisa dihitung, jangan memblokir
        }

        return ! $payments->isFullyPaid($enrollment);
    }

    /**
     * Kelas private lama yang akan ditutup saat siswa lanjut program:
     * hanya kelas private yang TIDAK dipakai siswa lain (private 1-on-1, atau Exclusive yang sudah kosong).
     * Kelas reguler dan kelas bersama yang masih punya anggota lain tidak disentuh.
     */
    private function closableClass(?ClassEnrollment $enrollment): ?ClassModel
    {
        if (! $enrollment || ! $enrollment->class_id || ! $enrollment->private_package_id) {
            return null;
        }

        $class = ClassModel::find($enrollment->class_id);

        if (! $class) {
            return null;
        }

        $othersExist = $class->enrollments()
            ->where('id', '!=', $enrollment->id)
            ->whereIn('status', ClassModel::SEAT_STATUSES)
            ->exists();

        return $othersExist ? null : $class;
    }

    /**
     * Lepaskan kelas private lama: pertemuan yang belum terjadi (hari ini ke depan) dibatalkan
     * dan kelas ditandai selesai, supaya slot guru kembali kosong dan tidak ada pertemuan "hantu".
     * Pertemuan yang sudah lewat tidak diubah (riwayat tetap).
     */
    private function releaseOldClass(?ClassEnrollment $enrollment): void
    {
        $class = $this->closableClass($enrollment);

        if (! $class) {
            return;
        }

        ClassSession::where('class_id', $class->id)
            ->where('status', 'Scheduled')
            ->whereDate('session_date', '>=', today())
            ->update([
                'status'        => 'Cancelled',
                'change_reason' => 'Siswa lanjut ke program berikutnya',
            ]);

        $class->update(['status' => 'Completed']);
    }

    // Biaya tambahan untuk siswa lama yang lanjut program: [biaya pendaftaran, biaya aktivitas, info]
    private function continueFees(Student $siswa): array
    {
        [$applies, $lastEnd] = $this->registrationFeeInfo($siswa);

        $registrationFee = $applies ? (float) Setting::get('registration_fee', 0) : 0.0;
        $activityFee     = (float) Setting::get('activity_fee', 0);

        $note = ($applies && $lastEnd)
            ? 'Biaya pendaftaran dikenakan lagi karena lebih dari 30 hari sejak kelas terakhir berakhir (' . $lastEnd->translatedFormat('d M Y') . ').'
            : null;

        return [$registrationFee, $activityFee, $note];
    }

    // Biaya pendaftaran dikenakan lagi kalau sudah lewat 30 hari sejak kelas terakhir berakhir.
    // Kalau tidak ada kelas dengan end_date, dianggap tidak dikenakan.
    private function registrationFeeInfo(Student $siswa): array
    {
        $lastEnd = $siswa->enrollments()
            ->with('class:id,end_date')
            ->get()
            ->map(fn ($e) => $e->class?->end_date)
            ->filter()
            ->max();

        if (! $lastEnd) {
            return [false, null];
        }

        $lastEnd = Carbon::parse($lastEnd);

        return [$lastEnd->copy()->addDays(30)->lt(today()), $lastEnd];
    }

    private function resolveContinuePackage(array $draft): array
    {
        $isPrivate = $draft['package_type'] === 'private';

        $package = $isPrivate
            ? PrivatePackage::findOrFail($draft['private_package_id'])
            : ProgramPackage::with('program')->findOrFail($draft['program_package_id']);

        return [$isPrivate, $package];
    }

    private function continueAllowsInstallment(PaymentInstallmentService $payments, bool $isPrivate, $package): bool
    {
        if ($isPrivate) {
            return false;
        }

        return $payments->allowsInstallment(
            false,
            $package->program?->program_name,
            ProgramLevel::find($package->level_id)?->level_name,
        );
    }

    /* =====================================================================
     |  KONDISI 1: BELUM LUNAS (2 step)
     |  step 1: pembayaran & bukti, step 2: invoice & formulir
     * ===================================================================== */
    public function settlementForm(Student $siswa, PaymentInstallmentService $payments)
    {
        $enrollment = $siswa->activeEnrollment;
        abort_if(! $enrollment, 404);

        $enrollment->loadMissing(['programPackage.program', 'privatePackage', 'class.programPackage', 'student.currentLevel']);

        if (! $this->hasOutstandingBalance($enrollment)) {
            return redirect()->route('admin.siswa')
                ->with('success', "Pembayaran {$siswa->name} sudah lunas. Silakan lanjut ke program berikutnya.");
        }

        $isPrivate = (bool) $enrollment->private_package_id;
        $package   = $enrollment->programPackage
            ?? $enrollment->class?->programPackage
            ?? $enrollment->privatePackage;

        $total     = (float) $payments->totalBillFor($enrollment);
        $paid      = (float) $payments->totalPaid($enrollment);
        $remaining = (float) $payments->remainingBill($enrollment);

        return view('admin.calon-siswa.convert', [
            'step'           => 2,
            'personName'     => $siswa->name,
            'personLabel'    => 'Siswa',
            'eyebrow'        => 'Pelunasan:',
            'subtitle'       => 'Catat pembayaran untuk melunasi tagihan program yang sedang diikuti.',
            'formAction'     => route('admin.siswa.settle.store', $siswa),
            'backUrl'        => route('admin.siswa'),
            'cancelUrl'      => route('admin.siswa'),
            'package'        => $package,
            'isPrivate'      => $isPrivate,
            'totalBill'      => $remaining,
            'canInstallment' => $payments->enrollmentAllowsInstallment($enrollment),
            'settlement'     => ['total' => $total, 'paid' => $paid, 'remaining' => $remaining],
            'stepLabels'     => [1 => 'Pembayaran & Bukti', 2 => 'Invoice & Formulir'],
            'stepDisplay'    => 1,
        ]);
    }

    public function settlementStore(Request $request, Student $siswa, PaymentInstallmentService $payments)
    {
        $enrollment = $siswa->activeEnrollment;
        abort_if(! $enrollment, 404);

        $enrollment->loadMissing(['programPackage', 'privatePackage']);

        if (! $this->hasOutstandingBalance($enrollment)) {
            return redirect()->route('admin.siswa')->with('success', 'Pembayaran sudah lunas.');
        }

        $remaining = (float) $payments->remainingBill($enrollment);

        $data = $request->validate([
            'amount_paid'    => ['required', 'numeric', 'min:1', 'max:' . $remaining],
            'payment_method' => ['required', Rule::in(['Transfer Bank', 'Cash', 'QRIS'])],
            'payment_date'   => ['required', 'date', 'before_or_equal:today'],
            'proof'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'amount_paid.max' => 'Nominal melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        $amount = (float) $data['amount_paid'];
        $stage  = $this->nextStage($enrollment, $amount, $remaining);

        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        try {
            DB::transaction(fn () => $payments->recordPayment(
                $enrollment,
                $stage,
                $amount,
                $data['payment_method'],
                $data['payment_date'],
                $proofPath,
            ));
        } catch (ValidationException $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        $after = max($remaining - $amount, 0);

        return redirect()
            ->route('admin.siswa.continue.done', [$siswa, $enrollment, 'jenis' => 'pelunasan'])
            ->with('success', $after <= 0
                ? "Pembayaran {$siswa->name} sudah lunas."
                : 'Pembayaran dicatat. Sisa tagihan Rp ' . number_format($after, 0, ',', '.') . '.');
    }

    // Tahap otomatis: bayar penuh = Pelunasan, bayar sebagian = termin berikutnya yang belum terpakai
    private function nextStage(ClassEnrollment $enrollment, float $amount, float $remaining): string
    {
        $used = $enrollment->packagePayments()->pluck('payment_stage')->all();

        if (in_array('Pelunasan', $used, true)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Tahap Pelunasan sudah tercatat tetapi tagihan masih tersisa. Periksa riwayat di halaman Pembayaran.',
            ]);
        }

        if ($amount >= $remaining) {
            return 'Pelunasan';
        }

        foreach (['Termin 1', 'Termin 2', 'Termin 3'] as $stage) {
            if (! in_array($stage, $used, true)) {
                return $stage;
            }
        }

        return 'Pelunasan';
    }

    /* =====================================================================
     |  KONDISI 2: SUDAH LUNAS (3 step)
     |  step 1: pilih program, step 2: pembayaran & bukti, step 3: invoice & formulir
     * ===================================================================== */

    // STEP 1 (tampilan)
    public function continueProgramPage(Student $siswa)
    {
        $siswa->load(
            'candidateStudent',
            'activeEnrollment.class.programPackage.program',
            'activeEnrollment.programPackage.program',
            'activeEnrollment.privatePackage'
        );

        $enrollment = $siswa->activeEnrollment;

        // Belum lunas → kondisi 1 (pelunasan)
        if ($this->hasOutstandingBalance($enrollment)) {
            return redirect()->route('admin.siswa.settle', $siswa);
        }

        // Siswa tanpa calon siswa (seeder / data impor) tetap bisa lanjut program:
        // form hanya butuh nama, telepon, dan relasi kosong sebagai pengganti.
        $candidateStudent = $siswa->candidateStudent;

        if ($candidateStudent) {
            $candidateStudent->load(['program', 'privatePackage', 'availableSchedules']);
        } else {
            $candidateStudent = new CandidateStudent([
                'name'  => $siswa->name,
                'phone' => $siswa->user->phone ?? '-',
            ]);
            $candidateStudent->setRelation('program', null);
            $candidateStudent->setRelation('privatePackage', null);
            $candidateStudent->setRelation('availableSchedules', collect());
        }

        $packages = ProgramPackage::leftJoin('program_categories', 'program_categories.id', '=', 'program_packages.category_id')
            ->leftJoin('program_levels', 'program_levels.id', '=', 'program_packages.level_id')
            ->select('program_packages.*', 'program_categories.category_name', 'program_levels.level_name')
            ->orderBy('program_packages.package_name')
            ->get();

        $levels = ProgramLevel::leftJoin('program_categories', 'program_categories.id', '=', 'program_levels.category_id')
            ->select('program_levels.*', 'program_categories.category_name')
            ->orderBy('program_levels.category_id')
            ->orderBy('program_levels.sort_order')
            ->get();

        $classes = ClassModel::with('schedules')
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'program_package_id', 'level_id', 'delivery_mode', 'status']);

        $privatePackages = PrivatePackage::where('is_active', true)->orderBy('package_name')->get();

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        $roomSuggestions = ClassSchedule::whereNotNull('room')
            ->where('room', '!=', '')
            ->where('room', '!=', 'Online')
            ->distinct()->orderBy('room')->pluck('room');

        // Jadwal tersedia & jam terpakai tiap guru. Kelas private lama siswa ini diabaikan
        // (akan ditutup saat lanjut program), supaya slotnya tidak tampil sebagai bentrok.
        $teacherSlots = app(ClassScheduleService::class)
            ->teacherSlotsMap($this->closableClass($enrollment)?->id);

        // Draft: pilihan sebelumnya (kalau kembali dari step 2), kalau kosong pakai rekomendasi
        $draft = session(self::CONTINUE_DRAFT . $siswa->id);

        if (! $draft) {
            $rec = $this->buildRecommendation($enrollment);

            if (($rec['type'] ?? null) === 'program') {
                $draft = [
                    'package_type'       => 'program',
                    'program_package_id' => $rec['program_package_id'],
                    'level_id'           => $packages->firstWhere('id', $rec['program_package_id'])?->level_id,
                    'class_id'           => $rec['class_id'],
                ];
            } elseif (($rec['type'] ?? null) === 'private') {
                $draft = [
                    'package_type'       => 'private',
                    'private_package_id' => $rec['private_package_id'],
                ];
            }
        }

        $draft = $draft ?? [];

        return view('admin.calon-siswa.convert', [
            'step'                => 1,
            'stepDisplay'         => 1,
            'candidateStudent'    => $candidateStudent,
            'personName'          => $siswa->name,
            'eyebrow'             => 'Lanjut Program:',
            'subtitle'            => 'Pilih program berikutnya untuk siswa ini, lalu lanjut ke pembayaran.',
            'step1Action'         => route('admin.siswa.continue', $siswa),
            'backUrl'             => route('admin.siswa'),
            'cancelUrl'           => route('admin.siswa'),
            'packages'            => $packages,
            'levels'              => $levels,
            'classes'             => $classes,
            'privatePackages'     => $privatePackages,
            'teachers'            => $teachers,
            'roomSuggestions'     => $roomSuggestions,
            'teacherSlots'        => $teacherSlots,
            'recommendedCategory' => null,
            'defaultPackageType'  => $draft['package_type'] ?? 'program',
            'draft'               => $draft,
        ]);
    }

    // STEP 1 (submit): validasi, simpan pilihan ke session
    public function continueProgram(Request $request, Student $siswa)
    {
        $currentEnrollment = $siswa->activeEnrollment;

        if ($this->hasOutstandingBalance($currentEnrollment)) {
            return redirect()->route('admin.siswa.settle', $siswa);
        }

        $isPrivate = $request->input('package_type') === 'private';

        $validated = $request->validate([
            'package_type'       => ['required', 'in:program,private'],
            'program_package_id' => ['required_if:package_type,program', 'nullable', 'exists:program_packages,id'],
            'private_package_id' => ['required_if:package_type,private', 'nullable', 'exists:private_packages,id'],
            'level_id'           => ['required_if:package_type,program', 'nullable', 'exists:program_levels,id'],
            'class_id'           => ['nullable', 'exists:classes,id'],

            // khusus private: kelas dibuat saat pembayaran disimpan
            'teacher_id'             => [Rule::requiredIf($isPrivate), 'nullable', 'exists:teachers,id'],
            'delivery_mode'          => [Rule::requiredIf($isPrivate), 'nullable', Rule::in(['Offline', 'Online'])],
            'start_date'             => [Rule::requiredIf($isPrivate), 'nullable', 'date'],
            'end_date'               => ['nullable', 'date', 'after_or_equal:start_date'],
            'schedules'              => [Rule::requiredIf($isPrivate), 'nullable', 'array', 'min:1'],
            'schedules.*.day'        => ['required', Rule::in(ClassScheduleService::DAYS)],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time'   => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room'       => ['nullable', 'string', 'max:255'],
        ], [
            'level_id.required_if'       => 'Level wajib dipilih.',
            'teacher_id.required'        => 'Guru wajib dipilih untuk kelas private.',
            'delivery_mode.required'     => 'Mode kelas wajib dipilih.',
            'start_date.required'        => 'Tanggal mulai wajib diisi.',
            'schedules.required'         => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.min'              => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'end_date.after_or_equal'    => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        if ($isPrivate) {
            $classes = app(ClassScheduleService::class);

            $validated['schedules'] = $classes->normalizeSchedules(
                $validated['schedules'],
                $validated['delivery_mode']
            );

            // Kelas private lama siswa ini akan ditutup saat lanjut program, jadi tidak dihitung sebagai bentrok
            $oldClassId = $this->closableClass($currentEnrollment)?->id;

            $classes->assertTeacherFree((int) $validated['teacher_id'], $validated['schedules'], $oldClassId);
            $classes->assertTeacherAvailable((int) $validated['teacher_id'], $validated['schedules']);

            if (empty($validated['end_date'])) {
                $privatePackage = PrivatePackage::findOrFail($validated['private_package_id']);
                $validated['end_date'] = $classes->resolveEndDate(
                    $privatePackage,
                    $validated['start_date'],
                    $validated['schedules']
                );
            }
        } else {
            // reguler tidak membuat kelas baru; buang field khusus private
            unset(
                $validated['teacher_id'],
                $validated['delivery_mode'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['schedules']
            );

            if (! empty($validated['class_id'])) {
                $cocok = ClassModel::where('id', $validated['class_id'])
                    ->where('program_package_id', $validated['program_package_id'])
                    ->exists();

                if (! $cocok) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Kelas yang dipilih tidak sesuai dengan paket program.',
                    ]);
                }
            }
        }

        session([self::CONTINUE_DRAFT . $siswa->id => $validated]);

        return redirect()->route('admin.siswa.continue.payment', $siswa);
    }

    // STEP 2 (tampilan)
    public function continuePayment(Student $siswa, PaymentInstallmentService $payments)
    {
        if ($this->hasOutstandingBalance($siswa->activeEnrollment)) {
            return redirect()->route('admin.siswa.settle', $siswa);
        }

        $draft = session(self::CONTINUE_DRAFT . $siswa->id);

        if (! $draft) {
            return redirect()->route('admin.siswa')->with('error', 'Pilih program terlebih dahulu.');
        }

        [$isPrivate, $package] = $this->resolveContinuePackage($draft);
        [$registrationFee, $activityFee, $registrationNote] = $this->continueFees($siswa);

        $canInstallment = $this->continueAllowsInstallment($payments, $isPrivate, $package);
        $months         = $payments->monthsForPackage($package, $isPrivate);

        // Harga lunas (skema "Lunas")
        $lesTotal       = (float) $payments->packageTotalFor($package, $isPrivate);
        $totalBill      = $lesTotal + $registrationFee + $activityFee;

        // Harga termin (installment_price x lama paket). null = paket ini tidak punya harga termin khusus
        $lesTotalTermin = (! $isPrivate && $package->installment_price)
            ? $package->totalFor('Termin')
            : null;
        $totalBillTermin = $lesTotalTermin !== null
            ? $lesTotalTermin + $registrationFee + $activityFee
            : null;

        return view('admin.calon-siswa.convert', [
            'step'             => 2,
            'stepDisplay'      => 2,
            'personName'       => $siswa->name,
            'personLabel'      => 'Siswa',
            'eyebrow'          => 'Lanjut Program:',
            'subtitle'         => 'Catat pembayaran untuk program baru dan upload bukti pembayaran.',
            'formAction'       => route('admin.siswa.continue.store', $siswa),
            'backUrl'          => route('admin.siswa.continue.program', $siswa),
            'cancelUrl'        => route('admin.siswa'),
            'package'          => $package,
            'isPrivate'        => $isPrivate,
            'canInstallment'   => $canInstallment,
            'registrationFee'  => $registrationFee,
            'registrationNote' => $registrationNote,
            'activityFee'      => $activityFee,
            'months'           => $months,
            'lesTotal'         => $lesTotal,
            'lesTotalTermin'   => $lesTotalTermin,
            'totalBill'        => $totalBill,
            'totalBillTermin'  => $totalBillTermin,
        ]);
    }

    // STEP 2 (submit): enrollment baru + pembayaran (+ kelas private)
    public function continueStore(Request $request, Student $siswa, PaymentInstallmentService $payments)
    {
        $currentEnrollment = $siswa->activeEnrollment;

        if ($this->hasOutstandingBalance($currentEnrollment)) {
            return redirect()->route('admin.siswa.settle', $siswa);
        }

        $draft = session(self::CONTINUE_DRAFT . $siswa->id);

        if (! $draft) {
            return redirect()->route('admin.siswa')->with('error', 'Sesi habis, pilih program lagi.');
        }

        $validated = $request->validate([
            'payment_scheme' => ['nullable', 'in:Lunas,Termin'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'amount_paid'    => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['Transfer Bank', 'Cash', 'QRIS'])],
            'payment_date'   => ['required', 'date', 'before_or_equal:today'],
            'proof'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'proof.required'               => 'Bukti pembayaran wajib diupload.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh di masa depan.',
        ]);

        [$isPrivate, $package] = $this->resolveContinuePackage($draft);
        [$registrationFee, $activityFee] = $this->continueFees($siswa);

        $canInstallment = $this->continueAllowsInstallment($payments, $isPrivate, $package);

        // Termin hanya berlaku kalau paket boleh dicicil DAN punya harga termin.
        // Selain itu otomatis Lunas (private, HSK, dll).
        $scheme = ($canInstallment
            && ! $isPrivate
            && $package->installment_price
            && ($validated['payment_scheme'] ?? 'Lunas') === 'Termin')
            ? 'Termin'
            : 'Lunas';

        $lesTotal = $scheme === 'Termin'
            ? $package->totalFor('Termin')
            : (float) $payments->packageTotalFor($package, $isPrivate);

        $baseTotal  = $lesTotal + $registrationFee + $activityFee;
        $discount   = round((float) ($validated['discount'] ?? 0), 2);
        $amountPaid = round((float) $validated['amount_paid'], 2);

        if ($discount >= $baseTotal) {
            throw ValidationException::withMessages([
                'discount' => 'Diskon harus lebih kecil dari total tagihan (Rp' . number_format($baseTotal, 0, ',', '.') . ').',
            ]);
        }

        $totalBill = round($baseTotal - $discount, 2);

        if (! $canInstallment && $amountPaid < $totalBill) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Paket ini wajib dibayar lunas (Rp' . number_format($totalBill, 0, ',', '.') . '), tidak bisa dicicil/DP.',
            ]);
        }

        $classId   = $isPrivate ? null : ($draft['class_id'] ?? null);
        $levelId   = $isPrivate ? null : ($package->level_id ?: ($draft['level_id'] ?? null));
        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        try {
            $enrollment = DB::transaction(function () use (
                $siswa, $currentEnrollment, $draft, $isPrivate, $validated, $payments,
                $registrationFee, $activityFee, $discount, $amountPaid, $totalBill, $classId, $levelId, $proofPath, $scheme
            ) {
                $currentEnrollment?->update(['status' => 'Completed']);

                // Kelas private lama (kalau tidak dipakai siswa lain) ditutup dan pertemuan yang belum terjadi dibatalkan
                $this->releaseOldClass($currentEnrollment);

                // kuota kelas reguler (dikunci supaya dua admin tidak mengisi kursi terakhir bersamaan)
                if ($classId) {
                    $class = ClassModel::lockForUpdate()->findOrFail($classId);

                    if ($class->capacity !== null) {
                        $terisi = $class->enrollments()->where('status', 'Active')->count();

                        if ($terisi >= $class->capacity) {
                            throw ValidationException::withMessages([
                                'class_id' => "Kelas {$class->class_name} sudah penuh ({$terisi}/{$class->capacity}). Pilih kelas lain.",
                            ]);
                        }
                    }
                }

                // ---- Private: kelas langsung dibuat (guru, mode, tanggal, jadwal) ----
                if ($isPrivate) {
                    $classesSvc = app(ClassScheduleService::class);

                    // cek ulang bentrok dan ketersediaan guru, karena bisa berubah antara step 1 dan step 2
                    $classesSvc->assertTeacherFree((int) $draft['teacher_id'], $draft['schedules']);
                    $classesSvc->assertTeacherAvailable((int) $draft['teacher_id'], $draft['schedules']);

                    $privatePackage = PrivatePackage::findOrFail($draft['private_package_id']);

                    $kelas = new ClassModel();
                    $kelas->forceFill([
                        'program_package_id' => null,
                        'private_package_id' => $privatePackage->id,
                        'level_id'           => null,
                        'teacher_id'         => $draft['teacher_id'],
                        'class_name'         => 'Private - ' . $siswa->name,
                        'delivery_mode'      => $draft['delivery_mode'],
                        'start_date'         => $draft['start_date'],
                        'end_date'           => $draft['end_date'] ?? null,
                        'capacity'           => $privatePackage->max_students ?? 1,
                        'status'             => 'Open',
                    ])->save();

                    $classesSvc->createSchedules($kelas, $draft['schedules'], $draft['start_date']);

                    // Bangkitkan pertemuan bertanggal sebanyak total_meetings paket
                    app(ClassSessionService::class)
                        ->generateForNewClass($kelas->refresh(), (int) $privatePackage->total_meetings);

                    $classId = $kelas->id;
                }

                // level siswa dipakai service untuk aturan cicilan, jadi perbarui sebelum mencatat pembayaran
                $siswa->update(array_filter(['current_level_id' => $levelId]));

                $new = $siswa->enrollments()->create([
                    'class_id'           => $classId,
                    'program_package_id' => $isPrivate ? null : $draft['program_package_id'],
                    'private_package_id' => $isPrivate ? $draft['private_package_id'] : null,
                    // sama dengan tanggal bayar, supaya lolos aturan service
                    'enrollment_date'    => Carbon::parse($validated['payment_date'])->toDateString(),
                    // Private selalu punya kelas → Active. Reguler tanpa kelas → Waiting Class.
                    'status'             => (! $isPrivate && ! $classId) ? 'Waiting Class' : 'Active',
                    'registration_fee'   => $registrationFee,
                    'activity_fee'       => $activityFee,
                    'discount'           => $discount,
                    // Skema bayar menentukan total tagihan (Lunas = price, Termin = installment_price x lama paket)
                    'payment_scheme'     => $scheme,
                ]);

                $payments->recordPayment(
                    $new->load(['programPackage.program', 'privatePackage', 'student.currentLevel']),
                    $amountPaid >= $totalBill ? 'Lunas' : 'DP',
                    $amountPaid,
                    $validated['payment_method'],
                    $validated['payment_date'],
                    $proofPath,
                );

                $siswa->update(['status' => 'Active', 'join_date' => $validated['payment_date']]);

                return $new;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        session()->forget(self::CONTINUE_DRAFT . $siswa->id);

        return redirect()
            ->route('admin.siswa.continue.done', [$siswa, $enrollment])
            ->with('success', "Siswa {$siswa->name} berhasil dilanjutkan ke program berikutnya.");
    }

    /* =====================================================================
     |  STEP AKHIR (dipakai kondisi 1 dan kondisi 2): invoice & formulir
     * ===================================================================== */
    public function continueDone(Request $request, Student $siswa, ClassEnrollment $enrollment)
    {
        abort_unless((int) $enrollment->student_id === (int) $siswa->id, 404);

        $settle = $request->query('jenis') === 'pelunasan';

        return view('admin.calon-siswa.convert', [
            'step'        => 3,
            'student'     => $siswa,
            'enrollment'  => $enrollment->load('class', 'privatePackage'),
            'eyebrow'     => 'Akademik » Siswa',
            'doneTitle'   => $settle ? 'Pembayaran Berhasil' : 'Lanjut Program Berhasil',
            'subtitle'    => 'Pembayaran sudah tercatat. Unduh dokumen yang diperlukan di bawah ini.',
            'doneUrl'     => route('admin.siswa'),
            'doneLabel'   => 'Daftar Siswa',
            // null → blade memakai 3 label bawaan (Data Diri & Program, Pembayaran & Bukti, Invoice & Formulir)
            'stepLabels'  => $settle
                ? [1 => 'Pembayaran & Bukti', 2 => 'Invoice & Formulir']
                : null,
            'stepDisplay' => $settle ? 2 : 3,
        ]);
    }
}