<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\ClassTask;
use App\Models\Level;
use App\Models\PrivatePackage;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgressReport;
use App\Models\ProgressReportTemplate;
use App\Models\ProgressReportTemplateMeeting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumAndGuruEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $kurikulumUser;
    protected User $guruUser;
    protected Teacher $teacher;
    protected $class;
    protected Student $student;
    protected ClassEnrollment $enrollment;
    protected ClassSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $kLevel = Level::firstOrCreate(['nama_level' => 'Curriculum']);
        $gLevel = Level::firstOrCreate(['nama_level' => 'Teacher']);

        $this->kurikulumUser = User::create([
            'username' => 'kurikulum_test_' . uniqid(),
            'name'     => 'Kurikulum Officer',
            'email'    => 'kurikulum_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'level_id' => $kLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->guruUser = User::create([
            'username' => 'guru_test_' . uniqid(),
            'name'     => 'Laoshi Test',
            'email'    => 'guru_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'level_id' => $gLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Laoshi Test',
            'phone'   => '081234567890',
            'status'  => 'Active',
        ]);

        $program = Program::create(['program_name' => 'Program Test']);
        $cat = ProgramCategory::create([
            'program_id'    => $program->id,
            'category_name' => 'Maochong Test',
            'sort_order'    => 1,
        ]);

        $this->class = \App\Models\ClassModel::create([
            'class_name'       => 'Kelas Test Enhanced',
            'teacher_id'       => $this->teacher->id,
            'teacher_response' => 'Accepted',
            'delivery_mode'    => 'Offline',
            'capacity'         => 10,
            'start_date'       => now()->subMonth()->toDateString(),
            'end_date'         => now()->addMonth()->toDateString(),
            'status'           => 'Running',
        ]);

        $studentLevel = Level::firstOrCreate(['nama_level' => 'Student']);
        $studentUser = User::create([
            'username' => 'murid_test_' . uniqid(),
            'name'     => 'Murid Test',
            'email'    => 'murid_test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'level_id' => $studentLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->student = Student::create([
            'user_id'   => $studentUser->id,
            'name'      => 'Murid Test',
            'gender'    => 'L',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);

        $defaultPkg = PrivatePackage::create([
            'package_name'     => 'Default Package ' . uniqid(),
            'duration_minutes' => 60,
            'total_meetings'   => 4,
            'min_students'     => 1,
            'max_students'     => 4,
            'price'            => 1000000,
            'is_active'        => true,
        ]);

        $this->enrollment = ClassEnrollment::create([
            'class_id'           => $this->class->id,
            'student_id'         => $this->student->id,
            'private_package_id' => $defaultPkg->id,
            'status'             => 'Active',
            'enrollment_date'    => now()->toDateString(),
        ]);

        $this->schedule = ClassSchedule::create([
            'class_id'   => $this->class->id,
            'day'        => 'Senin',
            'start_time' => '08:00:00',
            'end_time'   => '09:30:00',
        ]);
    }

    /** POIN A: Private 2 on 1 muncul di template progress report dari sumber dinamis */
    public function test_poin_a_template_available_programs_includes_private_2_on_1(): void
    {
        $pkg = PrivatePackage::create([
            'package_name'     => '2 on 1 Semi-Private',
            'duration_minutes' => 60,
            'total_meetings'   => 8,
            'min_students'     => 2,
            'max_students'     => 2,
            'price'            => 1500000,
            'is_active'        => true,
        ]);

        $available = ProgressReportTemplate::availablePrograms();
        $this->assertTrue($available['privatePackages']->contains('id', $pkg->id));

        $res = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.report-template.index'));
        $res->assertOk();
        $res->assertSee('2 on 1 Semi-Private');
    }

    /** POIN B: Tugas Perbaikan Materi tanpa kelas tersimpan dan tampil, tugas kelas tetap wajib kelas */
    public function test_poin_b_tugas_perbaikan_materi_without_class_and_class_task_requires_class(): void
    {
        // 1. Tugas perbaikan materi tanpa kelas
        $resMateri = $this->actingAs($this->kurikulumUser)->post(route('kurikulum.tugas-kelas.store'), [
            'task_type'   => 'material_revision',
            'teacher_ids' => [$this->teacher->id],
            'title'       => 'Revisi Materi Tanpa Kelas',
            'description' => 'Mohon revisi modul kosakata',
            'priority'    => 'Medium',
        ]);
        $resMateri->assertRedirect(route('kurikulum.tugas-kelas'));

        $materiTask = ClassTask::where('title', 'Revisi Materi Tanpa Kelas')->first();
        $this->assertNotNull($materiTask);
        $this->assertNull($materiTask->class_id);
        $this->assertEquals('material_revision', $materiTask->task_type);

        // Tampil aman di halaman kurikulum dan notifikasi guru
        $resKurikulum = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.tugas-kelas'));
        $resKurikulum->assertOk();
        $resKurikulum->assertSee('Revisi Materi Tanpa Kelas');
        $resKurikulum->assertSee('Tidak terkait kelas');

        $resNotif = $this->actingAs($this->guruUser)->get(route('teacher.notifikasi'));
        $resNotif->assertOk();
        $resNotif->assertSee('Tugas perbaikan materi: Revisi Materi Tanpa Kelas');

        // 2. Tugas kelas lama tetap WAJIB kelas
        $resClass = $this->actingAs($this->kurikulumUser)->post(route('kurikulum.tugas-kelas.store'), [
            'task_type'   => 'class_task',
            'title'       => 'Tugas Kelas Tanpa Kelas Harus Gagal',
            'description' => 'Uji validasi',
        ]);
        $resClass->assertSessionHasErrors('class_id');
    }

    /** POIN C: Jurnal dengan lokasi, achievement level, description tersimpan dan prefill ke Progress Report */
    public function test_poin_c_journal_attendance_and_sync_progress_report(): void
    {
        $pkg = PrivatePackage::create([
            'package_name'     => 'VIP Exclusive Unit',
            'duration_minutes' => 60,
            'total_meetings'   => 4,
            'min_students'     => 1,
            'max_students'     => 2,
            'price'            => 1000000,
            'is_active'        => true,
        ]);
        $this->class->update(['private_package_id' => $pkg->id]);

        $tpl = ProgressReportTemplate::create([
            'name'               => 'Private VIP Unit Tpl',
            'private_package_id' => $pkg->id,
            'updated_by'         => $this->kurikulumUser->id,
        ]);
        ProgressReportTemplateMeeting::create([
            'template_id'    => $tpl->id,
            'meeting_number' => 1,
            'materials'      => 'Pengenalan Nada dan Pinyin',
        ]);

        // Simpan jurnal baru dengan lokasi dan achievement level
        $sessionDate = now()->toDateString();
        $dayIndo = match (now()->format('l')) {
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        };
        $this->schedule->update(['day' => $dayIndo]);

        $resJournal = $this->actingAs($this->guruUser)->post(route('teacher.attendance.store', $this->schedule), [
            '_sid'                  => $this->schedule->id,
            'teaching_teacher_id'   => $this->teacher->id,
            'is_substitute'         => 'Tidak',
            'student_names'         => $this->student->name,
            'session_date'          => $sessionDate,
            'is_last_meeting'       => 'Tidak',
            'meeting_number'        => 1,
            'start_time'            => '08:00',
            'end_time'              => '09:30',
            'class_running'         => 'Berjalan',
            'location'              => 'Visit ke rumah',
            'focus_materials'       => ['Speaking'],
            'material_taught'       => 'Percakapan Dasar',
            'learning_activities'   => 'Latihan dialog',
            'problems'              => 'Tidak ada',
            'solutions'             => 'Latihan rutin',
            'results'               => 'Siswa antusias',
            'attendance'            => [$this->student->id => 'Present'],
            'attendance_note'       => [$this->student->id => 'Note awal'],
            'achievement_level'     => [$this->student->id => 'Proficient'],
            'description'           => [$this->student->id => 'Mampu melafalkan nada dengan tepat'],
        ]);
        $resJournal->assertSessionHasNoErrors();

        $journal = TeachingJournal::where('class_id', $this->class->id)->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('Visit ke rumah', $journal->location);

        $att = Attendance::where('teaching_journal_id', $journal->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($att);
        $this->assertEquals('Proficient', $att->achievement_level);
        $this->assertEquals('Mampu melafalkan nada dengan tepat', $att->description);

        // Buka form progress report guru -> prefill dari jurnal otomatis
        $resEditor = $this->actingAs($this->guruUser)->get(route('teacher.progress-report.form', $this->enrollment->id));
        $resEditor->assertOk();
        $resEditor->assertSee('Proficient');
        $resEditor->assertSee('Mampu melafalkan nada dengan tepat');

        // Simpan progress report sebagai Draft
        $this->actingAs($this->guruUser)->post(route('teacher.progress-report.store', $this->enrollment->id), [
            'action'    => 'draft',
            'materials' => [
                [
                    'meeting'      => 1,
                    'meeting_date' => $sessionDate,
                    'material'     => 'Pengenalan Nada dan Pinyin',
                    'level'        => 'Excellent', // guru mengedit manual menjadi Excellent
                    'description'  => 'Edit manual oleh guru',
                ],
            ],
            'header' => [
                'title'        => 'Progress Report',
                'teacher'      => $this->teacher->name,
                'student'      => $this->student->name,
                'class'        => $this->class->class_name,
                'present'      => '1/1',
                'teacher_sign' => $this->teacher->name,
            ],
        ]);

        $report = ProgressReport::where('enrollment_id', $this->enrollment->id)->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('Draft', $report->status);
        $this->assertEquals('Excellent', $report->content[0]['level']);
        $this->assertEquals('Edit manual oleh guru', $report->content[0]['description']);

        // Mengisi jurnal lagi (update revisi) tidak menimpa edit manual di progress report
        $journal->update(['status' => 'Revision']);
        $this->actingAs($this->guruUser)->put(route('teacher.attendance.update', $journal), [
            '_sid'                  => $this->schedule->id,
            'teaching_teacher_id'   => $this->teacher->id,
            'is_substitute'         => 'Tidak',
            'student_names'         => $this->student->name,
            'session_date'          => $sessionDate,
            'is_last_meeting'       => 'Tidak',
            'meeting_number'        => 1,
            'start_time'            => '08:00',
            'end_time'              => '09:30',
            'class_running'         => 'Berjalan',
            'location'              => 'Di Haoyou',
            'focus_materials'       => ['Speaking'],
            'material_taught'       => 'Percakapan Dasar Revisi',
            'learning_activities'   => 'Latihan dialog revisi',
            'problems'              => 'Tidak ada',
            'solutions'             => 'Latihan rutin',
            'results'               => 'Siswa antusias',
            'attendance'            => [$this->student->id => 'Present'],
            'attendance_note'       => [$this->student->id => 'Note revisi'],
            'achievement_level'     => [$this->student->id => 'Sufficient'], // beda dari Excellent
            'description'           => [$this->student->id => 'Deskripsi baru dari jurnal'],
        ]);

        $report->refresh();
        // Edit manual tetap utuh!
        $this->assertEquals('Excellent', $report->content[0]['level']);
        $this->assertEquals('Edit manual oleh guru', $report->content[0]['description']);

        // Jika status report Diajukan / Disetujui (Submitted), sama sekali tidak boleh berubah
        $report->update(['status' => 'Submitted']);
        $journal->update(['status' => 'Revision']);
        $this->actingAs($this->guruUser)->put(route('teacher.attendance.update', $journal), [
            '_sid'                  => $this->schedule->id,
            'teaching_teacher_id'   => $this->teacher->id,
            'is_substitute'         => 'Tidak',
            'student_names'         => $this->student->name,
            'session_date'          => $sessionDate,
            'is_last_meeting'       => 'Tidak',
            'meeting_number'        => 1,
            'start_time'            => '08:00',
            'end_time'              => '09:30',
            'class_running'         => 'Berjalan',
            'location'              => 'Di Haoyou',
            'focus_materials'       => ['Speaking'],
            'material_taught'       => 'Percakapan Dasar Revisi 2',
            'learning_activities'   => 'Latihan dialog revisi 2',
            'problems'              => 'Tidak ada',
            'solutions'             => 'Latihan rutin',
            'results'               => 'Siswa antusias',
            'attendance'            => [$this->student->id => 'Present'],
            'attendance_note'       => [$this->student->id => 'Note revisi 2'],
            'achievement_level'     => [$this->student->id => 'Sufficient'],
            'description'           => [$this->student->id => 'Deskripsi revisi 2'],
        ]);

        $report->refresh();
        $this->assertEquals('Submitted', $report->status);
        $this->assertEquals('Excellent', $report->content[0]['level']);
    }

    /** Jurnal lama tanpa location / kolom baru tetap tampil normal tanpa error ("-") */
    public function test_legacy_journal_displays_normally(): void
    {
        $sessionDate = now()->toDateString();
        $dayIndo = match (now()->format('l')) {
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        };
        $this->schedule->update(['day' => $dayIndo]);

        $oldJournal = TeachingJournal::create([
            'class_id'            => $this->class->id,
            'class_schedule_id'   => $this->schedule->id,
            'teacher_id'          => $this->teacher->id,
            'filled_by'           => $this->teacher->id,
            'session_date'        => $sessionDate,
            'start_time'          => '10:00:00',
            'end_time'            => '11:30:00',
            'meeting_number'      => 99,
            'class_status'        => 'Conducted',
            'is_last_meeting'     => false,
            'student_names'       => $this->student->name,
            'focus_materials'     => ['Reading'],
            'material_taught'     => 'Materi Lama',
            'learning_activities' => 'Aktivitas Lama',
            'problems'            => 'Tidak ada',
            'solutions'           => 'Solusi lama',
            'results'             => 'Hasil lama',
            'status'              => 'Pending',
            'location'            => null, // Jurnal lama
        ]);

        Attendance::create([
            'teaching_journal_id' => $oldJournal->id,
            'student_id'          => $this->student->id,
            'session_date'        => $sessionDate,
            'attendance_date'     => $sessionDate,
            'status'              => 'Present',
            'note'                => 'Catatan lama legacy',
            'achievement_level'   => null,
            'description'         => null,
        ]);

        $res = $this->actingAs($this->guruUser)->get(route('teacher.attendance', ['date' => $sessionDate]));
        $res->assertOk();
        $res->assertSee('Materi Lama');
        $res->assertSee('Lokasi: -');
    }
}
