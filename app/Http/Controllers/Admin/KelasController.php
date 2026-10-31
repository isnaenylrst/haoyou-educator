<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassTeacherOffer;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherLeave;
use App\Models\TeachingJournal;
use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Student;
use App\Services\ClassOfferService;
use App\Services\ClassScheduleService;
use App\Services\ClassSessionRescheduleService;
use App\Services\ClassSessionService;
use App\Services\ExtraFeeService;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KelasController extends Controller
{
    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private const ACTIVE_STATUSES = ['Open', 'Running'];


    private const COUNTED_ENROLLMENT_STATUSES = ['Active', 'Completed'];

    private const PAYMENT_METHODS = ['Transfer Bank', 'Cash', 'QRIS'];

    public function __construct(
        private ClassScheduleService $scheduleSvc,
        private ClassSessionService $sessionSvc,
        private ClassSessionRescheduleService $rescheduleSvc,
        private ClassOfferService $offerSvc,
    ) {
    }

    /* ============================================================
       CREATE
    ============================================================ */
    public function create()
    {
        return view('admin.kelas.tambah', $this->formLookups());
    }

    /* ============================================================
       EDIT (halaman penuh: data kelas + kelola siswa)
    ============================================================ */
    public function edit($id)
    {
        $kelas = ClassModel::query()
            ->with(['schedules', 'programPackage.program', 'privatePackage', 'teacher:id,name'])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', self::COUNTED_ENROLLMENT_STATUSES),
            ])
            ->findOrFail($id);

        // Sudah punya pertemuan bertanggal -> jadwal mingguan, tanggal & mode terkunci (diubah lewat "Ubah Jadwal")
        $hasSessions = ClassSession::where('class_id', $kelas->id)->exists();

        // Panel "Ajukan ke Guru" (kelas belum punya guru) / "Ganti Guru" (kelas sudah punya guru)
        [$offerFit, $offerOthers] = $this->offerCandidates($kelas);

        return view('admin.kelas.edit', array_merge(
            $this->formLookups(),
            compact('kelas', 'hasSessions', 'offerFit', 'offerOthers')
        ));
    }

    /* ============================================================
       UBAH JADWAL (halaman penuh: daftar pertemuan + form ubah jadwal)
    ============================================================ */
    public function ubahJadwal($id)
    {
        $kelas = ClassModel::with(['teacher:id,name', 'level', 'schedules'])->findOrFail($id);

        return view('admin.kelas.ubahjadwal', [
            'kelas'           => $kelas,
            'sessionsData'    => $this->sessionList($kelas),
            'roomSuggestions' => $this->roomSuggestions(),
        ]);
    }

    /* ============================================================
       AJUKAN KE GURU (panel di halaman Edit; di halaman Tambah ikut tersimpan bersama kelas)
       Kelas dibuat tanpa guru. classes.teacher_id baru terisi setelah ada guru yang menerima.
    ============================================================ */

    /** Guru aktif beserta kecocokannya dengan jadwal kelas. [yang cocok (di atas), yang lain] */
    private function offerCandidates(ClassModel $kelas): array
    {
        $kelas->loadMissing('schedules');

        $existing = ClassTeacherOffer::where('class_id', $kelas->id)->get()->keyBy('teacher_id');

        $teachers = Teacher::with('availabilities')
            ->where('status', 'Active')
            ->when($kelas->teacher_id, fn ($q) => $q->where('id', '!=', $kelas->teacher_id))
            ->orderBy('name')
            ->get()
            ->map(function (Teacher $t) use ($kelas, $existing) {
                $slots = TeacherAvailabilityService::sortSlots($t->availabilities);
                $clash = TeacherAvailabilityService::conflictWith($t->id, $kelas);
                $fits  = TeacherAvailabilityService::coversClass($slots, $kelas) && ! $clash;
                $offer = $existing->get($t->id);

                return (object) [
                    'id'     => $t->id,
                    'name'   => $t->name,
                    'fits'   => $fits,
                    'reason' => $clash
                        ? "Bentrok dengan kelas {$clash}"
                        : ($fits ? null : 'Di luar jadwal tersedia'),
                    'status' => $offer?->status,
                    'note'   => $offer?->response_note,
                ];
            });

        return [
            $teachers->where('fits', true)->values(),
            $teachers->where('fits', false)->values(),
        ];
    }

    /** Tombol Ajukan di halaman Edit. */
    public function storeOffer(Request $request, $id)
    {
        $kelas = ClassModel::with(['schedules', 'teacher:id,name'])->findOrFail($id);

        $data = $request->validate([
            'mode'          => ['required', Rule::in(['selected', 'matching', 'all'])],
            'teacher_ids'   => ['required_if:mode,selected', 'nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        $names = $this->offerSvc->offer($kelas, $data['mode'], $data['teacher_ids'] ?? []);

        return back()->with('success', $this->offerMessage($names, $kelas->teacher?->name));
    }

    private function offerMessage($names, ?string $currentTeacher = null): string
    {
        $list = $names->take(5)->implode(', ') . ($names->count() > 5 ? ', ...' : '');

        return $currentTeacher
            ? "Kelas diajukan ke {$names->count()} guru: {$list}. {$currentTeacher} tetap menjadi guru sampai ada yang menerima."
            : "Kelas diajukan ke {$names->count()} guru: {$list}. Guru kelas terisi setelah ada yang menerima.";
    }

    /** Data pendukung form kelas (dipakai halaman Tambah & Edit). */
    private function formLookups(): array
    {
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'status']);

        // Ketersediaan guru: { teacher_id: [ {day, start, end}, ... ] }
        $teacherAvailabilities = TeacherAvailability::orderBy('start_time')
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($group) => $group->map(fn ($a) => [
                'day'   => $a->day,
                'start' => substr($a->start_time, 0, 5),
                'end'   => substr($a->end_time, 0, 5),
            ])->values());

        // Slot guru yang sudah terpakai kelas aktif: { teacher_id: [ {class_id, class_name, day, start, end}, ... ] }
        $teacherBusy = ClassModel::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('teacher_id')
            ->with('schedules')
            ->get(['id', 'class_name', 'teacher_id'])
            ->groupBy('teacher_id')
            ->map(fn ($classes) => $classes->flatMap(fn ($k) => $k->schedules->map(fn ($sc) => [
                'class_id'   => $k->id,
                'class_name' => $k->class_name,
                'day'        => $sc->day,
                'start'      => substr($sc->start_time, 0, 5),
                'end'        => substr($sc->end_time, 0, 5),
            ]))->values());

        $programPackages = ProgramPackage::with('program')
            ->orderBy('program_id')
            ->orderBy('id')
            ->get();

        $privatePackages = PrivatePackage::orderBy('id')->get();

        $levels = ProgramLevel::query()
            ->leftJoin('program_categories', 'program_levels.category_id', '=', 'program_categories.id')
            ->select('program_levels.*', 'program_categories.category_name')
            ->orderBy('program_levels.program_id')
            ->orderBy('program_levels.category_id')
            ->orderBy('program_levels.sort_order')
            ->get();

        return [
            'teachers'              => $teachers,
            'teacherAvailabilities' => $teacherAvailabilities,
            'teacherBusy'           => $teacherBusy,
            'programPackages'       => $programPackages,
            'privatePackages'       => $privatePackages,
            'roomSuggestions'       => $this->roomSuggestions(),
            'levels'                => $levels,
        ];
    }

    /** Nama ruangan tidak baku: ambil dari yang pernah dipakai sebagai saran (boleh diketik bebas). */
    private function roomSuggestions()
    {
        return collect(['Kelas Kaca', 'Kelas Beijing', 'Kelas Feixiang', 'Kelas Atas'])
            ->merge(
                ClassSchedule::query()
                    ->whereNotNull('room')
                    ->where('room', '!=', '')
                    ->where('room', '!=', 'Online')
                    ->distinct()
                    ->pluck('room')
            )
            ->unique()
            ->sort()
            ->values();
    }

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

        // ---- Progres pertemuan dari jurnal mengajar (hanya sesi yang benar-benar berlangsung) ----
        $meetingProgress = TeachingJournal::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('class_status', 'Conducted')
            ->selectRaw('class_id, COUNT(DISTINCT COALESCE(class_session_id, id)) AS done, MAX(session_date) AS last_date')
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

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

        // ---- Data untuk filter ----
        $programs = Program::orderBy('id')->get(['id', 'program_name']);
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'status']);

        return view('admin.kelas.index', compact(
            'classes',
            'meetingProgress',
            'programs',
            'teachers',
            'kelasAktif',
            'kelasDailyActivity',
            'kelasHsk',
            'kelasPrivate'
        ));
    }

    /* ============================================================
       STORE
       Kelas selalu dibuat tanpa guru (teacher_id kosong), lalu admin diarahkan ke halaman
       "Ajukan ke Guru". Guru baru tersimpan di kelas setelah ada yang menerima.
    ============================================================ */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $offered = null;

        $kelas = DB::transaction(function () use ($data, $request, &$offered) {
            $kelas = new ClassModel();
            $kelas->forceFill([
                'program_package_id' => $data['program_package_id'],
                'private_package_id' => $data['private_package_id'],
                'level_id'           => $data['level_id'],
                'teacher_id'         => null,
                'class_name'         => $data['class_name'],
                'delivery_mode'      => $data['delivery_mode'],
                'start_date'         => $data['start_date'],
                'end_date'           => $data['end_date'],
                'capacity'           => $data['capacity'],
                'status'             => 'Open',
                'zoom_link'          => $data['zoom_link'],
                'zoom_passcode'      => $data['zoom_passcode'],
            ])->save();

            $this->scheduleSvc->createSchedules($kelas, $data['schedules']);

            // Bangkitkan pertemuan bertanggal (class_sessions) supaya kelas muncul di Jadwal.
            // Kalau jadwal bentrok (guru/ruang), seluruh transaksi dibatalkan.
            $this->generateSessions($kelas, ! $request->filled('end_date'));

            // Ajukan ke guru dalam transaksi yang sama: kalau pengajuannya gagal (mis. tidak ada guru
            // yang dicentang), kelas ikut dibatalkan dan admin diminta memperbaiki pilihannya.
            $mode = $data['offer_mode'] ?? 'none';

            if ($mode !== 'none') {
                $offered = $this->offerSvc->offer($kelas, $mode, $data['offer_teacher_ids'] ?? []);
            }

            return $kelas;
        });

        $message = 'Kelas berhasil ditambahkan. ' . ($offered
            ? $this->offerMessage($offered)
            : 'Belum diajukan ke guru, ajukan lewat halaman Edit kelas.');

        return redirect()->route('admin.kelas')->with('success', $message);
    }

    /* ============================================================
       UPDATE
    ============================================================ */
    public function update(Request $request, $id)
    {
        $kelas = ClassModel::findOrFail($id);

        // Kelas yang sudah punya pertemuan bertanggal: jadwal mingguan, tanggal mulai, dan mode kelas
        // dikunci. Perubahan jadwal dilakukan lewat "Ubah Jadwal" di daftar pertemuan kelas.
        $hasSessions    = ClassSession::where('class_id', $kelas->id)->exists();
        $data        = $this->validated($request, $kelas, $hasSessions);

        DB::transaction(function () use ($kelas, $data, $hasSessions) {
            // Tipe & paket sengaja TIDAK diubah setelah kelas dibuat.
            $kelas->forceFill([
                'level_id'      => $data['level_id'],
                // teacher_id sengaja TIDAK diubah di sini: guru hanya berubah setelah menerima pengajuan
                'class_name'    => $data['class_name'],
                'delivery_mode' => $data['delivery_mode'],
                'start_date'    => $data['start_date'],
                'end_date'      => $data['end_date'],
                'capacity'      => $data['capacity'],
                'status'        => $data['status'],
                'zoom_link'     => $data['zoom_link'],
                'zoom_passcode' => $data['zoom_passcode'],
            ])->save();

            if (! $hasSessions) {
                $this->syncSchedules($kelas, $data['schedules']);
            }
        });

        return redirect()->route('admin.kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    /* ============================================================
       VALIDASI (dipakai store & update)
    ============================================================ */
    private function validated(Request $request, ?ClassModel $kelas = null, bool $lockSchedule = false): array
    {
        // Admin tidak boleh mengisi guru langsung: input teacher_id diabaikan. Kelas baru tanpa guru;
        // kelas yang sudah punya guru dicek terhadap guru yang sekarang (bukan guru dari form).
        $request->merge(['teacher_id' => $kelas?->teacher_id]);

        // Kelas yang sudah punya pertemuan: pakai jadwal, tanggal mulai, dan mode yang tersimpan
        // (input form diabaikan). Cek guru di bawah tetap jalan terhadap jadwal yang berlaku.
        if ($kelas && $lockSchedule) {
            $request->merge([
                'delivery_mode' => $kelas->delivery_mode,
                'start_date'    => Carbon::parse($kelas->start_date)->toDateString(),
                'end_date'      => $kelas->end_date ? Carbon::parse($kelas->end_date)->toDateString() : null,
                'schedules'     => $kelas->schedules()->get()->map(fn ($sc) => [
                    'id'         => $sc->id,
                    'day'        => $sc->day,
                    'start_time' => substr($sc->start_time, 0, 5),
                    'end_time'   => substr($sc->end_time, 0, 5),
                    'room'       => $sc->room,
                ])->all(),
            ]);
        }

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
            'offer_mode'          => ['nullable', Rule::in(['selected', 'matching', 'all', 'none'])],
            'offer_teacher_ids'   => ['nullable', 'array'],
            'offer_teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'zoom_link'     => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/([a-z0-9-]+\.)?zoom\.us\//i'],
            'zoom_passcode' => ['nullable', 'string', 'max:50'],

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
            'zoom_link.regex' => 'Link harus berupa link Zoom (https://...zoom.us/...).',
            'zoom_passcode.max' => 'Kode akses Zoom tidak boleh lebih dari 50 karakter.',
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

        // ---- Guru harus tersedia pada jadwal kelas ----
        if (! empty($data['teacher_id'])) {
            foreach ($schedules as $row) {
                $available = TeacherAvailability::teacherCovers(
                    (int) $data['teacher_id'],
                    $row['day'],
                    $row['start_time'],
                    $row['end_time']
                );

                if (! $available) {
                    $errors['teacher_id'] = "Guru belum menyatakan tersedia pada {$row['day']} "
                        . "{$row['start_time']}–{$row['end_time']}. Atur ketersediaan guru terlebih dahulu.";
                    break;
                }
            }
        }

        // ---- Guru bentrok dengan kelas lain ----
        if (! empty($data['teacher_id']) && ! isset($errors['teacher_id'])) {
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
        $data['end_date'] = $data['end_date'] ?? $this->scheduleSvc->resolveEndDate($package, $data['start_date'], $schedules);
        $data['status'] = $data['status'] ?? 'Open';

        if ($data['delivery_mode'] === 'Offline') {
            $data['zoom_link'] = null;
            $data['zoom_passcode'] = null;
        } else {
            $data['zoom_link'] = $data['zoom_link'] ?? null;
            $data['zoom_passcode'] = $data['zoom_passcode'] ?? null;
        }

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

    /* ============================================================
       PERTEMUAN KELAS (class_sessions)
    ============================================================ */

    /** Bangkitkan pertemuan bertanggal sebanyak total_meetings paket kelas. */
    private function generateSessions(ClassModel $kelas, bool $fillEndDate): void
    {
        $package = $kelas->private_package_id
            ? PrivatePackage::find($kelas->private_package_id)
            : ProgramPackage::find($kelas->program_package_id);

        $total = (int) ($package?->total_meetings ?? 0);

        if ($total < 1) {
            return;
        }

        $sessions = $this->sessionSvc->generateForNewClass($kelas, $total);
        $last     = $sessions->last();

        // Tanggal selesai mengikuti pertemuan terakhir yang benar-benar terbentuk
        if ($fillEndDate && $last) {
            $kelas->forceFill(['end_date' => $last->session_date->toDateString()])->save();
        }
    }

    /** Pertemuan kelas sebagai array: status, aksi yang boleh, dan pembayarannya. */
    private function sessionList(ClassModel $kelas)
    {
        return ClassSession::with(['schedule', 'teacher:id,name', 'journal:id,class_session_id', 'payments'])
            ->where('class_id', $kelas->id)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $s) => [
                'id'             => $s->id,
                'number'         => $s->session_number,
                'date'           => $s->session_date->toDateString(),
                'date_label'     => $s->session_date->locale('id')->translatedFormat('D, j M Y'),
                'original_date'  => $s->original_date?->toDateString(),
                'start'          => substr($s->start_time, 0, 5),
                'end'            => substr($s->end_time, 0, 5),
                'room'           => $s->room,
                'location_type'  => $s->location_type,
                'teacher'        => $s->teacher?->name,
                'status'         => $s->status,
                'deviates'       => $s->deviatesFromPattern(),
                'change_reason'  => $s->change_reason,
                'can_reschedule' => $s->canBeRescheduled(),
                'can_following'  => $s->kind === 'Regular' && (bool) $s->schedule?->isCurrent(),
                'can_complete'   => $s->canBeCompleted(),
                'can_home_visit' => $s->canBecomeHomeVisit(),
                'payments'       => $s->payments->map(fn ($p) => [
                    'type'           => $p->payment_type,
                    'amount'         => (float) $p->amount_paid,
                    'invoice_number' => $p->invoice_number,
                    'invoice_url'    => route('admin.pembayaran.invoice', $p->id),
                ])->values(),
            ])
            ->values();
    }

    /** Daftar pertemuan kelas (JSON). */
    public function sessions($id)
    {
        $kelas = ClassModel::findOrFail($id);

        // Calon pembayar home visit: siswa Active di kelas ini
        $payers = ClassEnrollment::with('student:id,name')
            ->where('class_id', $kelas->id)
            ->where('status', 'Active')
            ->get()
            ->filter(fn ($e) => $e->student)
            ->map(fn ($e) => ['enrollment_id' => $e->id, 'name' => $e->student->name])
            ->values();

        return response()->json([
            'kelas'    => ['id' => $kelas->id, 'class_name' => $kelas->class_name],
            'sessions' => $this->sessionList($kelas),
            'payers'   => $payers,
            'fees'     => ['home_visit' => ExtraFeeService::FEE_HOME_VISIT],
        ]);
    }

    /** Ubah jadwal: satu pertemuan saja ("one") atau pertemuan ini dan seterusnya ("following"). */
    public function rescheduleSession(Request $request, ClassSession $classSession)
    {
        $data = $request->validate([
            'scope'                        => ['required', Rule::in(['one', 'following'])],
            'session_date'                 => ['required_if:scope,one', 'nullable', 'date'],
            'day'                          => ['required_if:scope,following', 'nullable', Rule::in(self::DAYS)],
            'start_time'                   => ['required', 'date_format:H:i'],
            'room'                         => ['nullable', 'string', 'max:255'],
            'reason'                       => ['nullable', 'string', 'max:255'],
            'confirm_outside_availability' => ['nullable', 'boolean'],
        ]);

        // "Seterusnya": admin memilih hari. Tanggal pertemuan yang diklik = hari itu di minggu yang sama
        // (pertemuan sesudahnya mengikuti di service dengan aturan yang sama).
        if ($data['scope'] === 'following') {
            $dayIndex = array_search($data['day'], self::DAYS, true);

            $data['session_date'] = $classSession->session_date->copy()
                ->startOfWeek(Carbon::MONDAY)
                ->addDays($dayIndex)
                ->toDateString();
        }

        if ($data['scope'] === 'one') {
            $this->rescheduleSvc->rescheduleOne($classSession, $data);
            $message = 'Jadwal pertemuan berhasil diubah.';
        } else {
            $count   = $this->rescheduleSvc->rescheduleFollowing($classSession, $data);
            $message = "Jadwal {$count} pertemuan (ini dan seterusnya) berhasil diubah.";
        }

        // Dipanggil lewat fetch dari modal: balas JSON, pesan sukses tampil setelah halaman dimuat ulang
        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    /** Tandai pertemuan selesai. */
    public function completeSession(ClassSession $classSession)
    {
        $this->sessionSvc->complete($classSession);

        return back()->with('success', 'Pertemuan ditandai selesai.');
    }

    /**
     * Jadikan pertemuan kelas ini Home Visit. Dibayar dulu: Rp50.000 per pertemuan,
     * satu baris pembayaran dan satu invoice per pertemuan. Pertemuan lain tidak kena biaya.
     */
    public function makeHomeVisit(Request $request, $id)
    {
        $kelas = ClassModel::findOrFail($id);

        $data = $request->validate([
            'session_ids'         => ['required', 'array', 'min:1'],
            'session_ids.*'       => ['integer', 'distinct'],
            'payer_enrollment_id' => ['required', 'integer', 'exists:class_enrollments,id'],
            'payment_date'        => ['required', 'date', 'before_or_equal:today'],
            'payment_method'      => ['required', Rule::in(self::PAYMENT_METHODS)],
            'amount'              => ['nullable', 'numeric', 'min:1'],
            'proof'               => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'session_ids.required' => 'Pilih minimal satu pertemuan.',
            'session_ids.min'      => 'Pilih minimal satu pertemuan.',
        ]);

        // Semua pertemuan harus milik kelas ini
        $sessionIds = ClassSession::where('class_id', $kelas->id)
            ->whereIn('id', $data['session_ids'])
            ->pluck('id')
            ->all();

        if (count($sessionIds) !== count($data['session_ids'])) {
            throw ValidationException::withMessages([
                'session_ids' => 'Ada pertemuan yang bukan milik kelas ini.',
            ]);
        }

        $payer     = ClassEnrollment::findOrFail($data['payer_enrollment_id']);
        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        try {
            $sessions = $this->sessionSvc->makeHomeVisitMany($sessionIds, $payer, [
                'payment_date'       => $data['payment_date'],
                'payment_method'     => $data['payment_method'],
                'payment_proof_path' => $proofPath,
                'amount'             => $data['amount'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        $total = Payment::whereIn('class_session_id', $sessionIds)
            ->where('payment_type', Payment::TYPE_HOME_VISIT)
            ->sum('amount_paid');

        return back()->with('success', $sessions->count() . ' pertemuan dijadikan Home Visit. Pembayaran Rp'
            . number_format((float) $total, 0, ',', '.') . ' tercatat lunas (satu invoice per pertemuan).');
    }
}