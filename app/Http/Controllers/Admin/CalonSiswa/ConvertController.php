<?php

namespace App\Http\Controllers\Admin\CalonSiswa;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\ClassCapacityService;
use App\Services\ClassOfferService;
use App\Services\ClassScheduleService;
use App\Services\ClassSessionService;
use App\Services\PaymentInstallmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConvertController extends Controller
{
    /** Kunci session untuk menyimpan pilihan step 1 (ditambah id calon siswa). */
    private const SESSION_KEY = 'convert_draft.';

    public function __construct(
        private PaymentInstallmentService $payments,
        private ClassScheduleService $classes,
        private ClassSessionService $sessions,
        private ClassCapacityService $capacity,
        private ClassOfferService $offers,
    ) {
    }

    /* =====================================================================
     |  STEP 1: Data diri & program (tampilan)
     * ===================================================================== */
    public function create(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $candidateStudent->load(['program', 'privatePackage', 'availableSchedules']);
        $packages = collect();
        $levels = collect();
        $classes = collect();

        if ($candidateStudent->program_id) {
            $packages = ProgramPackage::where('program_packages.program_id', $candidateStudent->program_id)
                ->leftJoin('program_categories', 'program_categories.id', '=', 'program_packages.category_id')
                ->leftJoin('program_levels', 'program_levels.id', '=', 'program_packages.level_id')
                ->select(
                    'program_packages.*',
                    'program_categories.category_name',
                    'program_levels.level_name'
                )
                ->get();

            $packages = $this->sortPackagesByCategoryAndLevel($packages);

            $levels = ProgramLevel::where('program_levels.program_id', $candidateStudent->program_id)
                ->leftJoin('program_categories', 'program_categories.id', '=', 'program_levels.category_id')
                ->select('program_levels.*', 'program_categories.category_name')
                ->orderBy('program_levels.category_id')
                ->orderBy('program_levels.sort_order')
                ->get();

            $classes = ClassModel::whereIn('program_package_id', $packages->pluck('id'))
                ->whereIn('status', ['Open', 'Running'])
                ->orderBy('class_name')
                ->get(['id', 'class_name', 'program_package_id', 'level_id', 'delivery_mode', 'status', 'capacity'])
                // Kelas reguler yang sudah penuh tidak ditawarkan (batas max siswa)
                ->map(function ($c) {
                    $c->slots_left = $this->capacity->remainingSlots($c);

                    return $c;
                })
                ->filter(fn ($c) => $c->slots_left > 0)
                ->values();
        }

        $privatePackages = PrivatePackage::where('is_active', true)
            ->orderBy('package_name')
            ->get();

        // --- Data untuk form kelas private (ruangan) ---
        // Guru tidak dipilih lagi di sini: kelas diajukan ke guru setelah convert.
        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name']);

        $roomSuggestions = ClassSchedule::whereNotNull('room')
            ->where('room', '!=', '')
            ->where('room', '!=', 'Online')
            ->distinct()
            ->orderBy('room')
            ->pluck('room');

        $teacherSlots = $this->classes->teacherSlotsMap();

        // --- Kelas private yang masih punya slot kosong (difilter per paket di blade) ---
        $joinableClasses = ClassModel::with(['teacher:id,name', 'schedules'])
            ->whereNotNull('private_package_id')
            ->whereIn('status', ['Open', 'Running'])
            ->get()
            ->map(function ($c) {
                $c->slots_left = $this->capacity->remainingSlots($c);

                return $c;
            })
            ->filter(fn ($c) => $c->slots_left > 0)
            ->values();

        // --- Progres pertemuan kelas yang bisa dipilih (reguler & private) ---
        $classProgress = $this->meetingProgressMap($classes->concat($joinableClasses));

        // --- Rekomendasi kategori berdasarkan umur (khusus Daily Activity) ---
        $candidateAge = null;
        $recommendedCategory = null;

        if ($candidateStudent->birth_date) {
            $candidateAge = Carbon::parse($candidateStudent->birth_date)->age;

            if ($candidateStudent->program?->program_name === 'Daily Activity') {
                $recommendedCategory = $this->recommendCategoryByAge($candidateAge);
            }
        }

        $defaultPackageType = $candidateStudent->private_package_id ? 'private' : 'program';

        // Pilihan step 1 sebelumnya (kalau admin kembali dari step 2)
        $draft = session(self::SESSION_KEY . $candidateStudent->id, []);

        return view('admin.calon-siswa.convert', compact(
            'candidateStudent',
            'packages',
            'levels',
            'classes',
            'privatePackages',
            'teachers',
            'roomSuggestions',
            'teacherSlots',
            'joinableClasses',
            'classProgress',
            'candidateAge',
            'recommendedCategory',
            'defaultPackageType',
            'draft'
        ))->with('step', 1);
    }

    /* =====================================================================
     |  STEP 1: submit, simpan pilihan ke session, lanjut ke pembayaran
     * ===================================================================== */
    public function storeStep1(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $isPrivate     = $request->input('package_type') === 'private';
        $joining       = $isPrivate && $request->filled('join_class_id');
        $needsNewClass = $isPrivate && ! $joining;

        $validated = $request->validate([
            'package_type'       => 'required|in:program,private',
            'program_package_id' => 'required_if:package_type,program|nullable|exists:program_packages,id',
            'private_package_id' => 'required_if:package_type,private|nullable|exists:private_packages,id',
            'level_id'           => 'required_if:package_type,program|nullable|exists:program_levels,id',
            'class_id'           => 'nullable|exists:classes,id',
            'join_class_id'      => 'nullable|exists:classes,id',

            // Materi yang diminta siswa private (wajib diisi admin, ikut tercetak di formulir)
            'requested_material' => [Rule::requiredIf($isPrivate), 'nullable', 'string', 'max:2000'],

            // kelas private BARU: tanpa guru, guru diajukan setelah convert
            'delivery_mode'          => [Rule::requiredIf($needsNewClass), 'nullable', Rule::in(['Offline', 'Online'])],
            'start_date'             => [Rule::requiredIf($needsNewClass), 'nullable', 'date'],
            'end_date'               => ['nullable', 'date', 'after_or_equal:start_date'],
            'schedules'              => [Rule::requiredIf($needsNewClass), 'nullable', 'array', 'min:1'],
            'schedules.*.day'        => ['required', Rule::in(ClassScheduleService::DAYS)],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time'   => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room'       => ['nullable', 'string', 'max:255'],
        ], [
            'level_id.required_if'       => 'Level wajib dipilih.',
            'requested_material.required' => 'Materi yang diminta siswa private wajib diisi.',
            'delivery_mode.required'     => 'Mode kelas wajib dipilih.',
            'start_date.required'        => 'Tanggal mulai wajib diisi.',
            'schedules.required'         => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.min'              => 'Tambahkan minimal satu jadwal mingguan.',
            'schedules.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'end_date.after_or_equal'    => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        // Validasi kecocokan paket / level / kelas
        [, $package] = $this->resolveSelection($validated, $candidateStudent);

        if ($joining) {
            // Gabung ke kelas private yang sudah ada: jadwal, guru, dan mode mengikuti kelas itu
            $this->assertJoinable(
                ClassModel::findOrFail($validated['join_class_id']),
                (int) $validated['private_package_id']
            );

            unset(
                $validated['delivery_mode'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['schedules']
            );
        } elseif ($needsNewClass) {
            // Ruangan mengikuti mode kelas + cek jadwal bertabrakan di form yang sama
            $validated['schedules'] = $this->classes->normalizeSchedules(
                $validated['schedules'],
                $validated['delivery_mode']
            );

            // Tanggal selesai otomatis kalau dikosongkan
            if (empty($validated['end_date'])) {
                $validated['end_date'] = $this->classes->resolveEndDate(
                    $package,
                    $validated['start_date'],
                    $validated['schedules']
                );
            }

            // Cek bentrok jadwal/ruangan SEKARANG (step 1), bukan baru saat submit pembayaran di step 2.
            // Kelas dibuat percobaan lalu dibatalkan, memakai logika yang sama persis dengan saat disimpan.
            $this->assertPrivateClassBuildable($validated, $package, $candidateStudent);
        } else {
            // Siswa reguler tidak membuat kelas baru; buang field khusus private
            unset(
                $validated['join_class_id'],
                $validated['requested_material'],
                $validated['delivery_mode'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['schedules']
            );
        }

        session([self::SESSION_KEY . $candidateStudent->id => $validated]);

        return redirect()->route('admin.calon-siswa.convert.step2', $candidateStudent);
    }

    /* =====================================================================
     |  STEP 2: Pembayaran & upload bukti (tampilan)
     * ===================================================================== */
    public function step2(CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $draft = session(self::SESSION_KEY . $candidateStudent->id);

        if (! $draft) {
            return redirect()
                ->route('admin.calon-siswa.convert', $candidateStudent)
                ->with('error', 'Lengkapi data diri dan program dulu.');
        }

        [$isPrivate, $package, $level] = $this->resolveSelection($draft, $candidateStudent);

        // Aturan cicilan mengikuti PaymentInstallmentService (private = wajib lunas)
        $canInstallment = $this->payments->allowsInstallment(
            $isPrivate,
            $candidateStudent->program?->program_name,
            $level?->level_name,
        );

        // tagihan sebelum diskon = harga paket + biaya pendaftaran + biaya aktivitas
        $registrationFee = (float) Setting::get('registration_fee', 0);
        $activityFee     = (float) Setting::get('activity_fee', 0);
        $months          = $this->payments->monthsForPackage($package, $isPrivate);

        // Harga lunas (skema "Lunas")
        $lesTotal        = (float) $this->payments->packageTotalFor($package, $isPrivate);
        $totalBill       = $lesTotal + $registrationFee + $activityFee;

        // Harga termin (installment_price x lama paket). null = paket ini tidak punya harga termin khusus
        $lesTotalTermin  = (! $isPrivate && $package->installment_price)
            ? $package->totalFor('Termin')
            : null;
        $totalBillTermin = $lesTotalTermin !== null
            ? $lesTotalTermin + $registrationFee + $activityFee
            : null;

        // Kelas yang dipilih di step 1 (reguler atau private yang digabung): tampilkan progres
        // dan isi catatan otomatis kalau kelas sudah berjalan. Catatan tetap bisa diedit admin.
        $chosenClassId  = $draft['join_class_id'] ?? $draft['class_id'] ?? null;
        $chosenClass    = $chosenClassId ? ClassModel::find($chosenClassId) : null;
        $chosenProgress = $chosenClass ? $this->meetingProgress($chosenClass) : null;
        $midClassNote   = $this->midClassNote($chosenClass, $chosenProgress);

        return view('admin.calon-siswa.convert', compact(
            'candidateStudent',
            'package',
            'isPrivate',
            'canInstallment',
            'registrationFee',
            'activityFee',
            'months',
            'lesTotal',
            'lesTotalTermin',
            'totalBill',
            'totalBillTermin',
            'midClassNote',
            'chosenClass',
            'chosenProgress'
        ))->with('step', 2);
    }

    /* =====================================================================
     |  STEP 2: submit, buat user, siswa, kelas private (jika private baru),
     |          enrollment, pembayaran + bukti, lalu ajukan kelas ke guru
     * ===================================================================== */
    public function store(Request $request, CandidateStudent $candidateStudent)
    {
        abort_if($candidateStudent->student()->exists(), 404, 'Calon siswa ini sudah menjadi siswa.');

        $draft = session(self::SESSION_KEY . $candidateStudent->id);

        if (! $draft) {
            return redirect()
                ->route('admin.calon-siswa.convert', $candidateStudent)
                ->with('error', 'Sesi habis, lengkapi data diri dan program lagi.');
        }

        $validated = $request->validate([
            'payment_scheme' => 'nullable|in:Lunas,Termin',
            'discount'       => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
            'amount_paid'    => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'payment_date'   => 'required|date|before_or_equal:today',
            'proof'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ], [
            'proof.required'               => 'Bukti pembayaran wajib diupload.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh di masa depan.',
        ]);

        [$isPrivate, $package, $level, $levelId] = $this->resolveSelection($draft, $candidateStudent);

        $canInstallment = $this->payments->allowsInstallment(
            $isPrivate,
            $candidateStudent->program?->program_name,
            $level?->level_name,
        );

        // Termin hanya berlaku kalau paket boleh dicicil DAN punya harga termin.
        // Selain itu otomatis Lunas (private, HSK, dll).
        $scheme = ($canInstallment
            && ! $isPrivate
            && $package->installment_price
            && ($validated['payment_scheme'] ?? 'Lunas') === 'Termin')
            ? 'Termin'
            : 'Lunas';

        $registrationFee = (float) Setting::get('registration_fee', 0);
        $activityFee     = (float) Setting::get('activity_fee', 0);
        $discount        = round((float) ($validated['discount'] ?? 0), 2);
        $lesTotal        = $scheme === 'Termin'
            ? $package->totalFor('Termin')
            : (float) $this->payments->packageTotalFor($package, $isPrivate);
        $baseTotal       = $lesTotal + $registrationFee + $activityFee;

        if ($discount >= $baseTotal) {
            throw ValidationException::withMessages([
                'discount' => 'Diskon harus lebih kecil dari total tagihan (Rp' . number_format($baseTotal, 0, ',', '.') . ').',
            ]);
        }

        $totalBill = $baseTotal - $discount;

        // Aturan DP: pembayaran pertama minimal sebesar biaya pendaftaran dan tidak boleh melebihi total tagihan.
        // Berlaku untuk skema Lunas maupun Termin (PaymentInstallmentService mengecek ulang saat dicatat).
        $minDp = $this->payments->minimumDownPayment($registrationFee, $activityFee, $totalBill);
        $paidNow = round((float) $validated['amount_paid'], 2);

        if ($paidNow + 0.005 < $minDp) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Minimal DP Rp' . number_format($minDp, 0, ',', '.') . ' (biaya pendaftaran).',
            ]);
        }

        if ($paidNow > round($totalBill, 2) + 0.005) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Nominal melebihi total tagihan (Rp' . number_format($totalBill, 0, ',', '.') . ').',
            ]);
        }

        $proofPath = $request->file('proof')->store('bukti-pembayaran', 'public');

        try {
            $enrollment = DB::transaction(function () use ($validated, $draft, $candidateStudent, $isPrivate, $levelId, $proofPath, $registrationFee, $activityFee, $discount, $totalBill, $scheme) {

                $studentLevel = Level::where('nama_level', 'Student')->firstOrFail();

                $user = User::create([
                    'level_id' => $studentLevel->id_level,
                    'username' => $this->generateUsername($candidateStudent->phone),
                    'password' => Hash::make('haoyou123'),
                    'status'   => 'Active',
                ]);

                $student = Student::create([
                    'candidate_student_id' => $candidateStudent->id,
                    'user_id'              => $user->id,
                    'current_level_id'     => $levelId,
                    'name'                 => $candidateStudent->name,
                    'points'               => 0,
                    'join_date'            => now(),
                    'status'               => 'Active',
                ]);

                $classId = $draft['class_id'] ?? null;

                if ($isPrivate && ! empty($draft['join_class_id'])) {
                    // ---- Gabung kelas private yang sudah ada ----
                    $kelas = ClassModel::lockForUpdate()->findOrFail($draft['join_class_id']);
                    $this->assertJoinable($kelas, (int) $draft['private_package_id']);

                    $classId = $kelas->id;
                } elseif ($isPrivate) {
                    // ---- Kelas private baru: tanpa guru, sesi langsung dibuat
                    //      (sama seperti kelas reguler), guru diajukan setelah transaksi ----
                    $kelas = $this->createPrivateClass(
                        $draft,
                        PrivatePackage::findOrFail($draft['private_package_id']),
                        $candidateStudent
                    );

                    $classId = $kelas->id;
                } elseif ($classId) {
                    // ---- Reguler: kelas dipilih langsung, cek batas max siswa ----
                    $kelas = ClassModel::lockForUpdate()->findOrFail($classId);
                    $this->capacity->assertHasAvailableSlot($kelas);
                }

                $enrollment = ClassEnrollment::create([
                    'student_id'         => $student->id,
                    'class_id'           => $classId,
                    'program_package_id' => $isPrivate ? null : $draft['program_package_id'],
                    'private_package_id' => $isPrivate ? $draft['private_package_id'] : null,
                    'enrollment_date'    => Carbon::parse($validated['payment_date'])->toDateString(),
                    // Private selalu punya kelas -> Active. Reguler tanpa kelas -> Waiting Class.
                    'status'             => (! $isPrivate && ! $classId) ? 'Waiting Class' : 'Active',
                    // registration_fee dipakai service untuk total tagihan, activity_fee untuk formulir
                    'registration_fee' => $registrationFee,
                    'activity_fee'     => $activityFee,
                    'discount'         => $discount,
                    'notes'            => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
                    'requested_material' => $isPrivate && filled($draft['requested_material'] ?? null)
                        ? trim($draft['requested_material'])
                        : null,
                    // Skema bayar menentukan total tagihan (Lunas = price, Termin = installment_price x lama paket)
                    'payment_scheme'   => $scheme,
                ]);

                $amountPaid = (float) $validated['amount_paid'];

                // Service menghitung remaining_bill, status Paid/Partial, dan nomor invoice
                $this->payments->recordPayment(
                    $enrollment->load(['programPackage.program', 'privatePackage']),
                    round($amountPaid, 2) >= round($totalBill, 2) ? 'Lunas' : 'DP',
                    $amountPaid,
                    $validated['payment_method'],
                    $validated['payment_date'],
                    $proofPath, // disimpan ke kolom payment_proof_path
                );

                return $enrollment;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath); // jangan tinggalkan file yatim
            throw $e;
        }

        $message = 'Siswa berhasil didaftarkan.';

        // Kelas private baru yang belum punya guru: ajukan ke guru. Dilakukan di luar transaksi
        // supaya kegagalan pengajuan tidak membatalkan siswa dan pembayaran.
        $kelas = $enrollment->class;

        if ($isPrivate && $kelas && ! $kelas->teacher_id && empty($draft['join_class_id'])) {
            $message .= ' ' . $this->offerPrivateClass($kelas);
        }

        session()->forget(self::SESSION_KEY . $candidateStudent->id);

        return redirect()
            ->route('admin.calon-siswa.convert.step3', $enrollment)
            ->with('success', $message);
    }

    /* =====================================================================
     |  STEP 3: Download invoice & formulir perjanjian
     * ===================================================================== */
    public function step3(ClassEnrollment $enrollment)
    {
        $enrollment->load(['student.candidateStudent', 'class', 'privatePackage']);
        $student = $enrollment->student;

        return view('admin.calon-siswa.convert', compact('enrollment', 'student'))->with('step', 3);
    }

    /* =====================================================================
     |  Masukkan siswa "Waiting Class" ke kelas (tidak berubah)
     * ===================================================================== */
    public function assignClass(Request $request, ClassEnrollment $enrollment)
    {
        abort_if($enrollment->status !== 'Waiting Class', 404, 'Enrollment ini tidak sedang menunggu kelas.');

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // pastikan kelas yang dipilih sesuai program_package & level enrollment ini
        $classBelongsToPackage = ClassModel::where('id', $validated['class_id'])
            ->where('program_package_id', $enrollment->program_package_id)
            ->when($enrollment->student?->current_level_id, fn ($q, $levelId) => $q->where('level_id', $levelId))
            ->exists();

        abort_unless($classBelongsToPackage, 422, 'Kelas tidak sesuai dengan paket program siswa ini.');

        DB::transaction(function () use ($validated, $enrollment) {
            $kelas = ClassModel::lockForUpdate()->findOrFail($validated['class_id']);
            $this->capacity->assertHasAvailableSlot($kelas);

            $enrollment->update([
                'class_id' => $validated['class_id'],
                'status'   => 'Active',
            ]);
        });

        return redirect()
            ->back()
            ->with('success', 'Siswa berhasil dimasukkan ke kelas.');
    }

    /* =====================================================================
     |  Helper
     * ===================================================================== */

    /**
     * Validasi pilihan paket/level/kelas. Dipakai di step 1 dan step 2.
     *
     * @return array [$isPrivate, $package, $level, $levelId]
     */
    private function resolveSelection(array $data, CandidateStudent $candidateStudent): array
    {
        $isPrivate = $data['package_type'] === 'private';

        $package = $isPrivate
            ? PrivatePackage::findOrFail($data['private_package_id'])
            : ProgramPackage::findOrFail($data['program_package_id']);

        $level = null;
        $levelId = null;

        if (! $isPrivate) {
            $level = ProgramLevel::findOrFail($data['level_id']);

            if ((int) $level->program_id !== (int) $candidateStudent->program_id) {
                throw ValidationException::withMessages([
                    'level_id' => 'Level tidak sesuai dengan program calon siswa ini.',
                ]);
            }

            $levelId = $package->level_id ?: $level->id;

            if (! empty($data['class_id'])) {
                $classMatches = ClassModel::where('id', $data['class_id'])
                    ->where('program_package_id', $data['program_package_id'])
                    ->where('level_id', $levelId)
                    ->exists();

                if (! $classMatches) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Kelas yang dipilih tidak sesuai dengan paket/level yang dipilih.',
                    ]);
                }
            }
        }

        return [$isPrivate, $package, $level, $levelId];
    }

    /**
     * Buat kelas private baru (tanpa guru) lengkap dengan jadwal mingguan dan pertemuan bertanggal.
     * Melempar ValidationException kalau jadwal/ruangan bentrok. Panggil di dalam transaksi.
     */
    private function createPrivateClass(array $data, PrivatePackage $privatePackage, CandidateStudent $candidateStudent): ClassModel
    {
        $shared = ($privatePackage->max_students ?? 1) > 1;

        $kelas = new ClassModel();
        $kelas->forceFill([
            'program_package_id' => null,
            'private_package_id' => $privatePackage->id,
            'level_id'           => null,
            'teacher_id'         => null,
            'class_name'         => $shared
                ? $privatePackage->package_name . ' - ' . Carbon::parse($data['start_date'])->format('d M Y')
                : 'Private - ' . $candidateStudent->name,
            'delivery_mode'      => $data['delivery_mode'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'] ?? null,
            'capacity'           => $privatePackage->max_students ?? 1,
            'status'             => 'Open',
        ])->save();

        $this->classes->createSchedules($kelas, $data['schedules'], $data['start_date']);
        $this->sessions->generateForNewClass($kelas->refresh(), (int) $privatePackage->total_meetings);

        return $kelas;
    }

    /** Uji coba pembuatan kelas private: semua perubahan database dibatalkan, hanya error bentrok yang tersisa. */
    private function assertPrivateClassBuildable(array $data, PrivatePackage $privatePackage, CandidateStudent $candidateStudent): void
    {
        DB::beginTransaction();

        try {
            $this->createPrivateClass($data, $privatePackage, $candidateStudent);
        } finally {
            DB::rollBack();
        }
    }

    /** Ajukan kelas private ke guru yang cocok; kalau tidak ada yang cocok, ke semua guru aktif. */
    private function offerPrivateClass(ClassModel $kelas): string
    {
        try {
            $names = $this->offers->offer($kelas, 'matching', []);
        } catch (ValidationException $e) {
            // offer() melempar exception kalau tidak ada guru yang cocok / sudah diajukan semua
            try {
                $names = $this->offers->offer($kelas, 'all', []);
            } catch (\Throwable $e2) {
                report($e2);

                return 'Kelas dibuat, tapi belum bisa diajukan ke guru. Ajukan lewat halaman Edit kelas.';
            }
        } catch (\Throwable $e) {
            report($e);

            return 'Kelas dibuat, tapi belum bisa diajukan ke guru. Ajukan lewat halaman Edit kelas.';
        }

        $list = $names->take(5)->implode(', ') . ($names->count() > 5 ? ', ...' : '');

        return "Kelas diajukan ke {$names->count()} guru: {$list}.";
    }

    /** Kelas private yang dipilih harus sesuai paket, masih menerima murid, dan masih ada slot. */
    private function assertJoinable(ClassModel $kelas, int $privatePackageId): void
    {
        if ((int) $kelas->private_package_id !== $privatePackageId) {
            throw ValidationException::withMessages([
                'join_class_id' => 'Kelas yang dipilih bukan untuk paket private ini.',
            ]);
        }

        if (! in_array($kelas->status, ['Open', 'Running'], true)) {
            throw ValidationException::withMessages([
                'join_class_id' => 'Kelas ini sudah tidak menerima murid baru.',
            ]);
        }

        $this->capacity->assertHasAvailableSlot($kelas);
    }

    /**
     * Progres pertemuan kelas: jumlah pertemuan yang benar-benar terlaksana (jurnal berstatus Conducted,
     * dihitung sama dengan halaman Kelas), total pertemuan paket, dan tanggal pertemuan terakhir.
     *
     * @param  iterable<ClassModel>  $classes
     * @return array<int, array{done:int,total:int,next:int,last_date:?string}>
     */
    private function meetingProgressMap(iterable $classes): array
    {
        $ids = collect($classes)->pluck('id')->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $journals = TeachingJournal::query()
            ->whereIn('class_id', $ids)
            ->where('class_status', 'Conducted')
            ->selectRaw('class_id, COUNT(DISTINCT COALESCE(class_session_id, id)) AS done, MAX(session_date) AS last_date')
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        $models = ClassModel::with(['programPackage:id,total_meetings', 'privatePackage:id,total_meetings'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $result = [];

        foreach ($ids as $id) {
            $done = (int) ($journals[$id]->done ?? 0);
            $kelas = $models[$id] ?? null;

            $result[$id] = [
                'done'      => $done,
                'total'     => (int) ($kelas?->privatePackage?->total_meetings ?? $kelas?->programPackage?->total_meetings ?? 0),
                'next'      => $done + 1,
                'last_date' => isset($journals[$id]) && $journals[$id]->last_date
                    ? Carbon::parse($journals[$id]->last_date)->format('d/m/Y')
                    : null,
            ];
        }

        return $result;
    }

    private function meetingProgress(ClassModel $kelas): array
    {
        return $this->meetingProgressMap([$kelas])[$kelas->id];
    }

    /** Catatan otomatis untuk siswa yang masuk ke kelas yang sudah berjalan (null kalau belum ada pertemuan). */
    private function midClassNote(?ClassModel $kelas, ?array $progress): ?string
    {
        if (! $kelas || ! $progress || $progress['done'] < 1) {
            return null;
        }

        $dari = $progress['total'] > 0 ? " dari {$progress['total']}" : '';

        return "Daftar di tengah kelas: kelas sudah berjalan {$progress['done']}{$dari} pertemuan, "
            . "siswa mulai dari pertemuan ke-{$progress['next']}.";
    }

    private function sortPackagesByCategoryAndLevel($packages)
    {
        $categoryOrder = [
            // Daily Activity
            'Maochong' => 1,
            'Jianer'   => 2,
            'Hudie'    => 3,
            'Feixiang' => 4,
            // HSK
            'HSK Class'       => 1,
            'HSK Preparation' => 2,
        ];

        return $packages->sort(function ($a, $b) use ($categoryOrder) {
            $rankA = $categoryOrder[$a->category_name] ?? 99;
            $rankB = $categoryOrder[$b->category_name] ?? 99;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strnatcmp($a->level_name ?? '', $b->level_name ?? '')
                ?: strcmp($a->package_name, $b->package_name);
        })->values();
    }

    private function programAllowsInstallment(ProgramLevel $level): bool
    {
        if (! str_starts_with($level->level_name, 'HSK ')) {
            return true;
        }

        $levelNumber = (int) trim(substr($level->level_name, 4));

        return $levelNumber >= 3;
    }

    private function recommendCategoryByAge(int $age): ?string
    {
        return match (true) {
            $age >= 3 && $age <= 6   => 'Maochong',
            $age >= 7 && $age <= 9   => 'Jianer',
            $age >= 10 && $age <= 14 => 'Hudie',
            $age >= 15               => 'Feixiang',
            default                  => null,
        };
    }

    private function generateUsername(string $phone): string
    {
        $base = preg_replace('/\D/', '', $phone);
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }
}