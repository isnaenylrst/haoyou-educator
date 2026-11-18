<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\CurriculumConsultationSlot;
use App\Models\Level;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\ProgressReport;
use App\Models\ProgressReportTemplate;
use App\Models\ProgressReportTemplateMeeting;
use App\Models\Student;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\ProgressReportDocument;
use App\Services\TeacherAvailabilityUrgencyService;
use App\Services\TerbilangService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruFeaturesAndAttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruUser;
    protected Teacher $teacher;
    protected User $kurikulumUser;
    protected User $ownerUser;
    protected User $adminUser;
    protected ClassModel $class;
    protected ClassSchedule $schedule;
    protected Student $student;
    protected ClassEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $currLevel = Level::create(['nama_level' => 'Curriculum']);
        $ownerLevel = Level::create(['nama_level' => 'Owner']);
        $adminLevel = Level::create(['nama_level' => 'Admin']);

        $this->guruUser = User::create([
            'username' => 'guru_test',
            'name'     => 'Guru Test Pratama',
            'email'    => 'guru_test@example.com',
            'password' => bcrypt('password'),
            'level_id' => $guruLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Guru Test Pratama',
            'phone'   => '081234567890',
            'status'  => 'Active',
        ]);

        $this->kurikulumUser = User::create([
            'username' => 'kurikulum_test',
            'name'     => 'Kurikulum Officer',
            'email'    => 'kurikulum_test@example.com',
            'password' => bcrypt('password'),
            'level_id' => $currLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->ownerUser = User::create([
            'username' => 'owner_test',
            'name'     => 'Owner Joy',
            'email'    => 'owner_test@example.com',
            'password' => bcrypt('password'),
            'level_id' => $ownerLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->adminUser = User::create([
            'username' => 'admin_test',
            'name'     => 'Admin Staff',
            'email'    => 'admin_test@example.com',
            'password' => bcrypt('password'),
            'level_id' => $adminLevel->id_level,
            'status'   => 'Active',
        ]);

        $program = Program::create(['program_name' => 'Mandarin Kids']);
        $pl = ProgramLevel::create(['program_id' => $program->id, 'level_name' => 'Beginner']);
        $package = ProgramPackage::create([
            'program_id'       => $program->id,
            'level_id'         => $pl->id,
            'package_name'     => 'Paket Hemat 4 Pertemuan',
            'duration_minutes' => 60,
            'total_meetings'   => 4,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 400000,
        ]);

        $this->class = ClassModel::create([
            'program_package_id' => $package->id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Mandarin Kids A',
            'delivery_mode'      => 'Online',
            'capacity'           => 10,
            'start_date'         => '2026-11-20',
            'end_date'           => '2026-12-20',
            'status'             => 'Running',
            'teacher_response'   => 'Accepted',
        ]);

        $this->schedule = ClassSchedule::create([
            'class_id'       => $this->class->id,
            'day'            => 'Jumat',
            'start_time'     => '14:00:00',
            'end_time'       => '15:00:00',
            'room'           => 'Zoom Room 1',
            'effective_from' => '2026-11-20',
        ]);

        $studentLevel = Level::firstOrCreate(['nama_level' => 'Student']);
        $studentUser = User::create([
            'username' => 'budi_student',
            'name'     => 'Budi Santoso',
            'email'    => 'budi@example.com',
            'password' => bcrypt('password'),
            'level_id' => $studentLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->student = Student::create([
            'user_id'   => $studentUser->id,
            'name'      => 'Budi Santoso',
            'gender'    => 'L',
            'join_date' => '2026-11-20',
            'status'    => 'Active',
        ]);

        $this->enrollment = ClassEnrollment::create([
            'student_id'         => $this->student->id,
            'class_id'           => $this->class->id,
            'program_package_id' => $package->id,
            'enrollment_date'    => '2026-11-20',
            'status'             => 'Active',
        ]);
    }

    /**
     * GURU 1: Template Progress Report - Tanggal Pertemuan Otomatis (termasuk hasil reschedule & tanpa error bila kosong)
     */
    public function test_progress_report_meeting_dates_automatic_and_fallback(): void
    {
        // 1. Buat class_session reschedule: Pertemuan 1 dipindah ke Jumat 20 Nov 2026
        ClassSession::create([
            'class_id'       => $this->class->id,
            'kind'           => 'Regular',
            'session_number' => 1,
            'session_date'   => '2026-11-20',
            'start_time'     => '14:00:00',
            'end_time'       => '15:00:00',
            'status'         => 'Scheduled',
        ]);

        // Buat Template Kurikulum agar form tidak me-redirect
        $tpl = ProgressReportTemplate::create([
            'program_id'       => $this->class->programPackage->program_id,
            'program_level_id' => $this->class->programPackage->level_id,
            'name'             => 'Template Mandarin Kids',
        ]);
        ProgressReportTemplateMeeting::create([
            'template_id'    => $tpl->id,
            'meeting_number' => 1,
            'materials'      => 'Salam Mandarin',
        ]);
        ProgressReportTemplateMeeting::create([
            'template_id'    => $tpl->id,
            'meeting_number' => 2,
            'materials'      => 'Angka Mandarin',
        ]);

        // Pertemuan 2 belum ada session/jurnal
        $dates = ProgressReportDocument::meetingDatesForClass($this->class->id);
        $this->assertTrue($dates->has('1'));
        $this->assertStringContainsString('20 Nov 2026', $dates->get('1'));

        $this->actingAs($this->guruUser);

        // Buka form editor progress report
        $response = $this->get(route('teacher.progress-report.form', ['enrollment' => $this->enrollment->id]));
        $response->assertStatus(200);
        $response->assertSee('20 Nov 2026');
        $response->assertSee('Tanggal');

        // Buka view dokumen progress report (komponen bersama)
        $report = ProgressReport::create([
            'class_enrollment_id' => $this->enrollment->id,
            'teacher_id'          => $this->teacher->id,
            'status'              => 'Draft',
            'content'             => [
                ['meeting' => 1, 'material' => 'Salam Mandarin', 'level' => 'Proficient', 'description' => 'Bagus'],
                ['meeting' => 2, 'material' => 'Angka Mandarin', 'level' => '', 'description' => ''],
            ],
        ]);

        $docData = ProgressReportDocument::data($report);
        $this->assertEquals(1, $docData['rows'][0]['no']);
        $this->assertStringContainsString('20 Nov 2026', $docData['rows'][0]['meeting_date']);
        $this->assertEquals('-', $docData['rows'][1]['meeting_date']); // belum ada sesi -> '-' tanpa error
    }

    /**
     * GURU 2: Schedule - Urgensi kelas yang menunggu konfirmasi (Badge & Urutan)
     */
    public function test_schedule_urgency_badge_and_sorting(): void
    {
        // Kelas A: Sesi terdekat besok (Urgent)
        $classUrgent = ClassModel::create([
            'program_package_id' => $this->class->program_package_id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Kelas A Mendesak',
            'delivery_mode'      => 'Online',
            'capacity'           => 5,
            'start_date'         => Carbon::tomorrow()->toDateString(),
            'status'             => 'Running',
            'teacher_response'   => 'Pending',
        ]);

        // Kelas B: Sesi terdekat 14 hari lagi (Tidak Urgent)
        $classNotUrgent = ClassModel::create([
            'program_package_id' => $this->class->program_package_id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Kelas B Santai',
            'delivery_mode'      => 'Online',
            'capacity'           => 5,
            'start_date'         => Carbon::today()->addDays(14)->toDateString(),
            'status'             => 'Running',
            'teacher_response'   => 'Pending',
        ]);

        $evalUrgent = TeacherAvailabilityUrgencyService::evaluateClass($classUrgent);
        $evalNotUrgent = TeacherAvailabilityUrgencyService::evaluateClass($classNotUrgent);

        $this->assertEquals('urgent', $evalUrgent['level']);
        $this->assertStringContainsString('bg-danger', $evalUrgent['badge_class']);
        $this->assertNotEmpty($evalUrgent['datetime_str']);

        $this->assertEquals('neutral', $evalNotUrgent['level']);

        $this->actingAs($this->guruUser);
        $response = $this->get(route('teacher.schedule'));
        $response->assertStatus(200);
        $response->assertSee('Kelas A Mendesak');
        $response->assertSee('Urgent');
        $response->assertSee('Lapisan Slot Konsultasi Kurikulum & Owner');
    }

    /**
     * GURU 3: Pengajuan Pembelian Bahan Ajar (Total, Terbilang, Nama Otomatis, Rekening, TTD sesuai persetujuan)
     */
    public function test_pengajuan_pembelian_bahan_ajar_flow_and_recap(): void
    {
        // Test TerbilangService secara komprehensif
        $this->assertEquals('Nol rupiah', TerbilangService::make(0));
        $this->assertEquals('Sebelas rupiah', TerbilangService::make(11));
        $this->assertEquals('Dua belas rupiah', TerbilangService::make(12));
        $this->assertEquals('Seratus rupiah', TerbilangService::make(100));
        $this->assertEquals('Seratus sebelas rupiah', TerbilangService::make(111));
        $this->assertEquals('Seribu rupiah', TerbilangService::make(1000));
        $this->assertEquals('Satu juta dua ratus ribu rupiah', TerbilangService::make(1200000));
        $this->assertEquals('Dua juta lima ratus ribu rupiah', TerbilangService::make(2500000));
        $this->assertEquals('Satu miliar rupiah', TerbilangService::make(1000000000));

        $this->actingAs($this->guruUser);

        // Submit form pengajuan multi-item dengan norek, ongkir, dll
        $submitData = [
            'type'           => 'Pembelian Bahan Ajar',
            'item_name'      => 'Buku & Flashcard Mandarin',
            'date'           => now()->toDateString(),
            'account_number' => '1234567890',
            'account_name'   => 'Guru Test Pratama',
            'description'    => 'Bahan ajar untuk kelas pemula',
            'items'          => [
                [
                    'name'     => 'Flashcard Hanzi',
                    'qty'      => 2,
                    'price'    => 50000,
                    'shipping' => 10000,
                    'class'    => 'Mandarin Kids A',
                    'link'     => 'https://tokopedia.com/flashcard',
                ],
                [
                    'name'     => 'Buku Tulis Karakter',
                    'qty'      => 3,
                    'price'    => 20000,
                    'shipping' => 5000,
                    'class'    => 'Mandarin Kids A',
                ],
            ],
        ];

        $postRes = $this->post(route('teacher.pengajuan.store'), $submitData);
        $postRes->assertRedirect(route('teacher.pengajuan'));

        // Cek data tersimpan di DB
        $sub = Submission::where('account_number', '1234567890')->first();
        $this->assertNotNull($sub);
        // Total = (2*50000+10000) + (3*20000+5000) = 110000 + 65000 = 175000
        $this->assertEquals(175000, (int) $sub->amount);

        // Buka halaman cetak pengajuan
        $printRes = $this->get(route('kurikulum.review-pengajuan.print-submission', $sub));
        $printRes->assertStatus(200);
        $printRes->assertSee('FORM PENGAJUAN PEMBELIAN BAHAN AJAR');
        $printRes->assertSee($this->teacher->name);
        $printRes->assertSee('1234567890');
        $printRes->assertSee('Seratus tujuh puluh lima ribu rupiah');

        // Blok TTD: Belum di-approve Kurikulum/Owner/Admin -> tanda tangan belum muncul
        $printRes->assertDontSee('TERVERIFIKASI');

        // Simulasikan persetujuan Kurikulum & Owner
        $sub->update([
            'status'                 => 'Disetujui',
            'curriculum_approved_at' => now(),
            'curriculum_id'          => $this->kurikulumUser->id,
            'owner_approved_at'      => now(),
            'owner_id'               => $this->ownerUser->id,
            'admin_verified_at'      => now(),
            'admin_id'               => $this->adminUser->id,
        ]);

        $printResApproved = $this->get(route('kurikulum.review-pengajuan.print-submission', $sub));
        $printResApproved->assertStatus(200);
        $printResApproved->assertSee('DISETUJUI DIGITAL');
    }

    /**
     * GURU 4: Jadwal Konsultasi (Booking ke Kurikulum & Owner sekaligus, lock double booking, cegah slot lewat, dan Catatan Pertemuan)
     */
    public function test_jadwal_konsultasi_booking_both_and_rules(): void
    {
        $this->actingAs($this->guruUser);

        // Buat slot Kurikulum dan Owner
        $slotCurr = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->kurikulumUser->id,
            'owner_role'         => 'Curriculum',
            'date'               => Carbon::tomorrow()->toDateString(),
            'start_time'         => '10:00:00',
            'end_time'           => '11:00:00',
            'status'             => 'Available',
        ]);

        $slotOwner = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->ownerUser->id,
            'owner_role'         => 'Owner',
            'date'               => Carbon::tomorrow()->toDateString(),
            'start_time'         => '11:00:00',
            'end_time'           => '12:00:00',
            'status'             => 'Available',
        ]);

        // 1. Buka halaman jadwal konsultasi guru (cek tab catatan pertemuan ada)
        $resIndex = $this->get(route('teacher.jadwal-konsultasi.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Catatan Pertemuan (Blok 4 Sesi)');

        // 2. Booking KEDUANYA (Kurikulum dan Owner) dalam satu transaksi
        $bookRes = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'       => 'both',
            'slot_curriculum_id' => $slotCurr->id,
            'slot_owner_id'      => $slotOwner->id,
            'topic'              => 'Konsultasi Materi & Evaluasi Pembelajaran',
            'class_id'           => $this->class->id,
            'booking_notes'      => 'Mohon review bab 1 sampai 4',
        ]);
        $bookRes->assertSessionHasNoErrors();

        $slotCurr->refresh();
        $slotOwner->refresh();

        $this->assertEquals('Booked', $slotCurr->status);
        $this->assertEquals('Pending', $slotCurr->teacher_booking_status);
        $this->assertEquals($this->teacher->id, $slotCurr->booked_by_teacher_id);

        $this->assertEquals('Booked', $slotOwner->status);
        $this->assertEquals('Pending', $slotOwner->teacher_booking_status);
        $this->assertEquals($this->teacher->id, $slotOwner->booked_by_teacher_id);

        // 3. Tes double booking ditolak jika guru lain mencoba memesan slot yang sudah Booked
        $otherGuruUser = User::create([
            'username' => 'guru_other',
            'name'     => 'Guru Other',
            'email'    => 'guru_other@example.com',
            'password' => bcrypt('password'),
            'level_id' => $this->guruUser->level_id,
            'status'   => 'Active',
        ]);
        $otherTeacher = Teacher::create([
            'user_id' => $otherGuruUser->id,
            'name'    => 'Guru Other',
            'status'  => 'Active',
        ]);

        $this->actingAs($otherGuruUser);
        $doubleRes = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type' => 'single',
            'slot_id'      => $slotCurr->id,
            'topic'        => 'Mencoba Rebutan Slot',
        ]);
        $doubleRes->assertSessionHas('error');

        // 4. Batalkan pengajuan oleh guru pertama
        $this->actingAs($this->guruUser);
        $cancelRes = $this->post(route('teacher.jadwal-konsultasi.cancel', $slotOwner));
        $cancelRes->assertSessionHas('success');
        $slotOwner->refresh();
        $this->assertEquals('Available', $slotOwner->status);
        $this->assertNull($slotOwner->booked_by_teacher_id);
    }

    /**
     * GURU 5: SOP - Sembunyikan card Template Progress Report
     */
    public function test_sop_hides_progress_report_template_card(): void
    {
        $this->actingAs($this->guruUser);
        $res = $this->get(route('teacher.sop'));
        $res->assertStatus(200);
        $res->assertDontSee('Acuan format dari Kurikulum. Pengisian progress report dilakukan di menu Progress Report.');
    }

    /**
     * TAMBAHAN: Attendance & Teaching Log bisa dibuka dan digunakan
     */
    public function test_attendance_and_teaching_log_accessible_and_work(): void
    {
        $this->actingAs($this->guruUser);

        // Buka Attendance
        $attRes = $this->get(route('teacher.attendance'));
        $attRes->assertStatus(200);

        // Buka Teaching Log
        $logRes = $this->get(route('teacher.teaching-log'));
        $logRes->assertStatus(200);

        // Simpan Jurnal & Absensi (store)
        $storeData = [
            'session_date'        => '2026-11-20',
            'class_running'       => 'Berjalan',
            'is_substitute'       => 'Tidak',
            'meeting_number'      => 1,
            'material_taught'     => 'Bab 1 Perkenalan',
            'learning_activities' => 'Mendengarkan audio dan pengulangan',
            'problems'            => 'Siswa agak pemalu di awal sesi',
            'solutions'           => 'Diajak bernyanyi lagu Mandarin',
            'results'             => 'Siswa mulai aktif berbicara',
            'session_score'       => '10',
            'attendance'          => [
                $this->student->id => 'Present',
            ],
        ];

        $saveRes = $this->post(route('teacher.attendance.store', $this->schedule), $storeData);
        $saveRes->assertSessionHasNoErrors();

        $journal = TeachingJournal::where('class_id', $this->class->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals('Bab 1 Perkenalan', $journal->material_taught);
    }
}
