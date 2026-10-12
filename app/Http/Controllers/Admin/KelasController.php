<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use App\Models\ClassEnrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KelasController extends Controller
{
    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private const ACTIVE_STATUSES = ['Open', 'Running'];

    /**
     * Siswa yang dihitung mengisi kapasitas kelas
     * (sama dengan aturan ClassCapacityService: Active + Completed).
     */
    private const COUNTED_ENROLLMENT_STATUSES = ['Active', 'Completed'];

    /* ============================================================
       INDEX
    ============================================================ */
    public function index(Request $request)
    {
        $classes = ClassModel::query()
            ->with(['programPackage.program', 'privatePackage', 'teacher', 'level', 'schedules'])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
            ])
            ->when($request->filled('search'), fn ($q) => $q->where('class_name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('program_id'), fn ($q) => $q->whereHas(
                'programPackage',
                fn ($p) => $p->where('program_id', $request->program_id)
            ))
            ->when($request->type === 'Reguler', fn ($q) => $q->whereNotNull('program_package_id'))
            ->when($request->type === 'Private', fn ($q) => $q->whereNotNull('private_package_id'))
            ->when($request->filled('teacher_id'), fn ($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("FIELD(status, 'Running', 'Open', 'Completed', 'Closed')")
            ->orderBy('class_name')
            ->paginate(10)
            ->withQueryString();

        // ---- Statistik: hanya kelas aktif (Open & Running), tidak terpengaruh filter ----
        $activeClasses = ClassModel::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->with('programPackage.program')
            ->get(['id', 'program_package_id', 'private_package_id']);

        $kelasAktif = $activeClasses->count();

        $kelasPrivate = $activeClasses->whereNotNull('private_package_id')->count();

        $isHsk = fn ($k) => str_contains(strtolower($k->programPackage?->program?->program_name ?? ''), 'hsk');

        $kelasHsk = $activeClasses
            ->whereNotNull('program_package_id')
            ->filter($isHsk)
            ->count();

        $kelasDailyActivity = $activeClasses
            ->whereNotNull('program_package_id')
            ->reject($isHsk)
            ->count();

        // ---- Data untuk filter & form ----
        $programs = Program::orderBy('id')->get(['id', 'program_name']);
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'status']);

        $programPackages = ProgramPackage::with('program')
            ->orderBy('program_id')
            ->orderBy('id')
            ->get();

        $privatePackages = PrivatePackage::orderBy('id')->get();

        // Nama ruangan tidak baku: ambil dari yang pernah dipakai sebagai saran (boleh diketik bebas)
        $roomSuggestions = ClassSchedule::query()
            ->whereNotNull('room')
            ->where('room', '!=', '')
            ->where('room', '!=', 'Online')
            ->distinct()
            ->orderBy('room')
            ->pluck('room');

        $levels = ProgramLevel::query()
            ->leftJoin('program_categories', 'program_levels.category_id', '=', 'program_categories.id')
            ->select('program_levels.*', 'program_categories.category_name')
            ->orderBy('program_levels.program_id')
            ->orderBy('program_levels.category_id')
            ->orderBy('program_levels.sort_order')
            ->get();

        return view('admin.kelas', compact(
            'classes',
            'programs',
            'teachers',
            'programPackages',
            'privatePackages',
            'roomSuggestions',
            'levels',
            'kelasAktif',
            'kelasDailyActivity',
            'kelasHsk',
            'kelasPrivate'
        ));
    }

    /* ============================================================
       STORE
    ============================================================ */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $kelas = new ClassModel();
            $kelas->forceFill([
                'program_package_id' => $data['program_package_id'],
                'private_package_id' => $data['private_package_id'],
                'level_id'           => $data['level_id'],
                'teacher_id'         => $data['teacher_id'],
                'class_name'         => $data['class_name'],
                'delivery_mode'      => $data['delivery_mode'],
                'start_date'         => $data['start_date'],
                'end_date'           => $data['end_date'],
                'capacity'           => $data['capacity'],
                'status'             => 'Open',
            ])->save();

            foreach ($data['schedules'] as $row) {
                $this->createSchedule($kelas, $row);
            }
        });

        return redirect()->route('admin.kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    /* ============================================================
       UPDATE
    ============================================================ */
    public function update(Request $request, $id)
    {
        $kelas = ClassModel::findOrFail($id);
        $data  = $this->validated($request, $kelas);

        DB::transaction(function () use ($kelas, $data) {
            // Tipe & paket sengaja TIDAK diubah setelah kelas dibuat.
            $kelas->forceFill([
                'level_id'      => $data['level_id'],
                'teacher_id'    => $data['teacher_id'],
                'class_name'    => $data['class_name'],
                'delivery_mode' => $data['delivery_mode'],
                'start_date'    => $data['start_date'],
                'end_date'      => $data['end_date'],
                'capacity'      => $data['capacity'],
                'status'        => $data['status'],
            ])->save();

            $this->syncSchedules($kelas, $data['schedules']);
        });

        return redirect()->route('admin.kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    /* ============================================================
       VALIDASI (dipakai store & update)
    ============================================================ */
    private function validated(Request $request, ?ClassModel $kelas = null): array
    {
        // Saat edit, tipe & paket diambil dari data kelas yang sudah ada.
        if ($kelas) {
            $request->merge([
                'type'               => $kelas->private_package_id ? 'Private' : 'Reguler',
                'program_package_id' => $kelas->program_package_id,
                'private_package_id' => $kelas->private_package_id,
            ]);
        }

        $isPrivate = $request->input('type') === 'Private';

        $data = $request->validate([
            'type'          => ['required', Rule::in(['Reguler', 'Private'])],
            'class_name'    => ['required', 'string', 'max:255'],
            'delivery_mode' => ['required', Rule::in(['Offline', 'Online'])],
            'capacity'      => ['required', 'integer', 'min:1', 'max:255'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'        => [$kelas ? 'required' : 'nullable', Rule::in(['Open', 'Running', 'Completed', 'Closed'])],
            'teacher_id'    => ['nullable', 'exists:teachers,id'],

            'program_package_id' => [Rule::requiredIf(! $isPrivate), 'nullable', 'exists:program_packages,id'],
            'private_package_id' => [Rule::requiredIf($isPrivate), 'nullable', 'exists:private_packages,id'],
            'level_id'           => [Rule::requiredIf(! $isPrivate), 'nullable', 'exists:program_levels,id'],

            'schedules'              => ['required', 'array', 'min:1'],
            'schedules.*.id'         => ['nullable', 'integer'],
            'schedules.*.day'        => ['required', Rule::in(self::DAYS)],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time'   => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room'       => ['nullable', 'string', 'max:255'],
        ], [
            'schedules.required'                => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.min'                     => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.*.end_time.after'        => 'Jam selesai harus setelah jam mulai.',
            'end_date.after_or_equal'           => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'program_package_id.required'       => 'Paket program wajib dipilih.',
            'private_package_id.required'       => 'Paket private wajib dipilih.',
            'level_id.required'                 => 'Level wajib dipilih.',
        ], [
            'class_name'             => 'nama kelas',
            'delivery_mode'          => 'mode kelas',
            'capacity'               => 'kapasitas',
            'start_date'             => 'tanggal mulai',
            'end_date'               => 'tanggal selesai',
            'schedules.*.day'        => 'hari',
            'schedules.*.start_time' => 'jam mulai',
            'schedules.*.end_time'   => 'jam selesai',
        ]);

        $errors = [];

        // ---- Paket & level ----
        $package = $isPrivate
            ? PrivatePackage::find($data['private_package_id'] ?? null)
            : ProgramPackage::find($data['program_package_id'] ?? null);

        if (! $package) {
            throw ValidationException::withMessages(['type' => 'Paket kelas tidak ditemukan.']);
        }

        if ($data['capacity'] > $package->max_students) {
            $errors['capacity'] = "Kapasitas maksimal untuk paket ini adalah {$package->max_students} siswa.";
        }

        if ($isPrivate) {
            $data['program_package_id'] = null;
            $data['level_id'] = null; // materi kelas private lewat tabel documents, bukan materials
        } else {
            $data['private_package_id'] = null;

            // Paket HSK sudah menentukan level-nya sendiri.
            $data['level_id'] = $package->level_id ?? $data['level_id'];

            $levelMatchesProgram = ProgramLevel::where('id', $data['level_id'])
                ->where('program_id', $package->program_id)
                ->exists();

            if (! $levelMatchesProgram) {
                $errors['level_id'] = 'Level tidak sesuai dengan program pada paket yang dipilih.';
            }
        }

        // ---- Kapasitas tidak boleh di bawah jumlah siswa saat ini (edit) ----
        if ($kelas) {
            $enrolled = $kelas->enrollments()->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES)->count();
            if ($data['capacity'] < $enrolled) {
                $errors['capacity'] = "Kapasitas tidak boleh di bawah jumlah siswa saat ini ({$enrolled} siswa).";
            }
        }

        // ---- Ruangan mengikuti mode kelas ----
        $schedules = [];
        foreach ($data['schedules'] as $i => $row) {
            $row['room'] = isset($row['room']) ? trim($row['room']) : null;

            if ($data['delivery_mode'] === 'Online') {
                $row['room'] = 'Online';
            } elseif (empty($row['room']) || $row['room'] === 'Online') {
                $errors["schedules.$i.room"] = 'Pilih ruangan untuk kelas offline.';
            }
            $schedules[] = $row;
        }

        // ---- Jadwal saling bertabrakan di form yang sama ----
        foreach ($schedules as $i => $a) {
            foreach ($schedules as $j => $b) {
                if ($j <= $i || $a['day'] !== $b['day']) {
                    continue;
                }
                if ($a['start_time'] < $b['end_time'] && $b['start_time'] < $a['end_time']) {
                    $errors['schedules'] = "Jadwal {$a['day']} saling bertabrakan.";
                }
            }
        }

        // ---- Guru bentrok dengan kelas lain ----
        if (! empty($data['teacher_id'])) {
            foreach ($schedules as $row) {
                $clash = ClassModel::query()
                    ->where('teacher_id', $data['teacher_id'])
                    ->whereIn('status', self::ACTIVE_STATUSES)
                    ->when($kelas, fn ($q) => $q->where('id', '!=', $kelas->id))
                    ->whereHas('schedules', fn ($q) => $q
                        ->where('day', $row['day'])
                        ->where('start_time', '<', $row['end_time'] . ':00')
                        ->where('end_time', '>', $row['start_time'] . ':00'))
                    ->first(['id', 'class_name']);

                if ($clash) {
                    $errors['teacher_id'] = "Guru sudah mengajar di kelas \"{$clash->class_name}\" pada {$row['day']} {$row['start_time']}–{$row['end_time']}.";
                    break;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $data['schedules'] = $schedules;
        $data['program_package_id'] = $data['program_package_id'] ?? null;
        $data['private_package_id'] = $data['private_package_id'] ?? null;
        $data['teacher_id'] = $data['teacher_id'] ?? null;
        $data['end_date'] = $data['end_date'] ?? null;
        $data['status'] = $data['status'] ?? 'Open';

        return $data;
    }

    /* ============================================================
       JADWAL
    ============================================================ */
    private function createSchedule(ClassModel $kelas, array $row)
    {
        return $kelas->schedules()->forceCreate([
            'class_id'   => $kelas->id,
            'day'        => $row['day'],
            'start_time' => $row['start_time'],
            'end_time'   => $row['end_time'],
            'room'       => $row['room'] ?? null,
        ]);
    }

    /**
     * Sinkronkan jadwal berdasarkan id, BUKAN hapus-lalu-buat-ulang.
     * teaching_journals.class_schedule_id memakai ON DELETE RESTRICT dan
     * teacher_leaves.class_schedule_id memakai ON DELETE CASCADE, jadi jadwal
     * lama tidak boleh dibuang begitu saja.
     */
    private function syncSchedules(ClassModel $kelas, array $rows): void
    {
        $existing = $kelas->schedules()->get()->keyBy('id');
        $keptIds  = [];

        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : null;

            if ($id && $existing->has($id)) {
                $existing[$id]->forceFill([
                    'day'        => $row['day'],
                    'start_time' => $row['start_time'],
                    'end_time'   => $row['end_time'],
                    'room'       => $row['room'] ?? null,
                ])->save();
                $keptIds[] = $id;
            } else {
                $keptIds[] = $this->createSchedule($kelas, $row)->id;
            }
        }

        $removedIds = $existing->keys()->diff($keptIds)->values();

        if ($removedIds->isEmpty()) {
            return;
        }

        $inUse = TeachingJournal::whereIn('class_schedule_id', $removedIds)->exists()
            || TeacherLeave::whereIn('class_schedule_id', $removedIds)->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'schedules' => 'Jadwal yang sudah memiliki jurnal mengajar atau data izin guru tidak bisa dihapus. '
                    . 'Ubah hari/jam-nya saja, atau biarkan jadwal tersebut.',
            ]);
        }

        $kelas->schedules()->whereIn('id', $removedIds)->delete();
    }

    /* ============================================================
       KELOLA SISWA
    ============================================================ */

    /**
     * Data awal modal "Kelola Siswa": info kapasitas + daftar siswa
     * dari daftar tunggu yang program/level-nya cocok dengan kelas ini.
     */
    public function students($id)
    {
        $kelas = ClassModel::with('programPackage.program')->findOrFail($id);
        $isPrivate = ! is_null($kelas->private_package_id);

        $activeCount = ClassEnrollment::where('class_id', $kelas->id)
            ->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES)
            ->count();

        // Siswa yang SUDAH terdaftar di kelas ini (untuk ditampilkan sebagai detail)
        $enrolled = ClassEnrollment::where('class_id', $kelas->id)
            ->whereIn('status', ['Active', 'Completed'])
            ->with('student.candidateStudent.availableSchedules')
            ->get()
            ->filter(fn ($e) => $e->student)
            ->map(fn ($e) => [
                'enrollment_id' => $e->id,
                'student_id'    => $e->student_id,
                'name'          => $e->student->name,
                'status'        => $e->status,
                'schedules'     => $this->formatSchedules($e->student),
            ])
            ->values();

        $alreadyInClass = ClassEnrollment::where('class_id', $kelas->id)
            ->whereIn('status', ['Active', 'Waiting Class'])
            ->pluck('student_id');

        $waitingQuery = ClassEnrollment::query()
            ->whereNull('class_id')
            ->where('status', 'Waiting Class')
            ->with('student.candidateStudent.availableSchedules');

        if ($isPrivate) {
            $waitingQuery->where('private_package_id', $kelas->private_package_id);
        } else {
            $programId = $kelas->programPackage?->program_id;
            $waitingQuery
                ->whereNotNull('program_package_id')
                ->whereHas('programPackage', fn ($q) => $q->where('program_id', $programId))
                ->whereHas('student', fn ($q) => $q->where('current_level_id', $kelas->level_id));
        }

        $waiting = $waitingQuery->get()
            ->filter(fn ($e) => $e->student) // jaga-jaga kalau data siswa sudah terhapus
            ->map(fn ($e) => [
                'enrollment_id'    => $e->id,
                'student_id'       => $e->student_id,
                'name'             => $e->student->name,
                'join_date'        => optional($e->student->join_date)->format('d M Y'),
                'already_in_class' => $alreadyInClass->contains($e->student_id),
                'schedules'        => $this->formatSchedules($e->student),
            ])
            ->values();

        return response()->json([
            'kelas' => [
                'class_name'     => $kelas->class_name,
                'is_private'     => $isPrivate,
                'capacity'       => $kelas->capacity,
                'students_count' => $activeCount,
                'is_full'        => $activeCount >= $kelas->capacity,
            ],
            'enrolled' => $enrolled,
            'waiting'  => $waiting,
        ]);
    }

    /**
     * Jadwal preferensi siswa (dari data calon siswa sebelum konversi),
     * dipakai untuk ditampilkan di modal Kelola Siswa.
     */
    private function formatSchedules(Student $student): array
    {
        return $student->candidateStudent?->availableSchedules
            ->map(fn ($s) => [
                'day'        => $s->day,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time'   => substr($s->end_time, 0, 5),
            ])
            ->values()
            ->all() ?? [];
    }

    /**
     * Cari siswa aktif untuk tab "Siswa Baru" (belum melalui daftar tunggu paket ini).
     * Untuk kelas Reguler, hanya siswa dengan level yang sama yang ditampilkan.
     */
    public function searchStudents(Request $request, $id)
    {
        $kelas = ClassModel::findOrFail($id);
        $isPrivate = ! is_null($kelas->private_package_id);
        $keyword = trim((string) $request->query('q', ''));

        $alreadyInClass = ClassEnrollment::where('class_id', $kelas->id)
            ->whereIn('status', ['Active', 'Waiting Class'])
            ->pluck('student_id');

        $students = Student::query()
            ->where('status', 'Active')
            ->when(! $isPrivate, fn ($q) => $q->where('current_level_id', $kelas->level_id))
            ->when($keyword !== '', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
            ->with('candidateStudent.availableSchedules')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'student_id'       => $s->id,
                'name'             => $s->name,
                'already_in_class' => $alreadyInClass->contains($s->id),
                'schedules'        => $this->formatSchedules($s),
            ]);

        return response()->json(['students' => $students]);
    }

    /**
     * Tambahkan siswa ke kelas ini.
     * source=waiting → pindahkan enrollment daftar tunggu ke kelas (isi class_id).
     * source=new     → buat enrollment baru langsung berstatus Active.
     */
    public function enrollStudent(Request $request, $id)
    {
        $kelas = ClassModel::with('programPackage')->findOrFail($id);
        $isPrivate = ! is_null($kelas->private_package_id);

        $data = $request->validate([
            'source'        => ['required', Rule::in(['waiting', 'new'])],
            'enrollment_id' => ['required_if:source,waiting', 'nullable', 'integer', 'exists:class_enrollments,id'],
            'student_id'    => ['required_if:source,new', 'nullable', 'integer', 'exists:students,id'],
        ]);

        DB::transaction(function () use ($data, $kelas, $isPrivate) {
            // Lock baris kelas supaya dua request tidak lolos kapasitas bersamaan
            $locked = ClassModel::where('id', $kelas->id)->lockForUpdate()->first();

            $activeCount = ClassEnrollment::where('class_id', $locked->id)
                ->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES)
                ->count();

            if ($activeCount >= $locked->capacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Kelas sudah penuh, tidak bisa menambah siswa lagi.',
                ]);
            }

            if ($data['source'] === 'waiting') {
                $enrollment = ClassEnrollment::whereNull('class_id')
                    ->where('status', 'Waiting Class')
                    ->with(['programPackage', 'student'])
                    ->findOrFail($data['enrollment_id']);

                $matches = $isPrivate
                    ? $enrollment->private_package_id === $locked->private_package_id
                    : $enrollment->program_package_id
                        && optional($enrollment->programPackage)->program_id === $locked->programPackage?->program_id
                        && optional($enrollment->student)->current_level_id === $locked->level_id;

                if (! $matches) {
                    throw ValidationException::withMessages([
                        'enrollment_id' => 'Program/level siswa ini tidak cocok dengan kelas.',
                    ]);
                }

                $enrollment->forceFill([
                    'class_id' => $locked->id,
                    'status'   => 'Active',
                ])->save();
            } else {
                $student = Student::findOrFail($data['student_id']);

                if (! $isPrivate && $student->current_level_id !== $locked->level_id) {
                    throw ValidationException::withMessages([
                        'student_id' => 'Level siswa ini tidak sesuai dengan level kelas.',
                    ]);
                }

                $duplicate = ClassEnrollment::where('class_id', $locked->id)
                    ->where('student_id', $student->id)
                    ->whereIn('status', ['Active', 'Waiting Class'])
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'student_id' => 'Siswa ini sudah terdaftar di kelas ini.',
                    ]);
                }

                ClassEnrollment::create([
                    'student_id'         => $student->id,
                    'class_id'           => $locked->id,
                    'program_package_id' => $isPrivate ? null : $locked->program_package_id,
                    'private_package_id' => $isPrivate ? $locked->private_package_id : null,
                    'enrollment_date'    => now()->toDateString(),
                    'status'             => 'Active',
                ]);
            }
        });

        return response()->json(['success' => true]);
    }
}