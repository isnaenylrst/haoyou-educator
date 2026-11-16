<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Level;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeachingJournal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleRescheduleSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $guruUser;
    protected User $kurikulumUser;
    protected Teacher $teacher;
    protected Student $student;
    protected ClassModel $class;
    protected ClassSchedule $schedule;
    protected ClassSession $session1;
    protected ClassSession $session2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminLevel = Level::create(['nama_level' => 'Admin']);
        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);
        $siswaLevel = Level::create(['nama_level' => 'Student']);

        $this->adminUser = User::create([
            'username' => 'admin_test',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'level_id' => $adminLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->guruUser = User::create([
            'username' => 'guru_test',
            'email'    => 'guru@test.com',
            'password' => bcrypt('password'),
            'level_id' => $guruLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->kurikulumUser = User::create([
            'username' => 'kurikulum_test',
            'email'    => 'kurikulum@test.com',
            'password' => bcrypt('password'),
            'level_id' => $kurikulumLevel->id_level,
            'status'   => 'Active',
        ]);

        $siswaUser = User::create([
            'username' => 'siswa_test',
            'email'    => 'siswa@test.com',
            'password' => bcrypt('password'),
            'level_id' => $siswaLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Laoshi Test',
            'phone'   => '081234567890',
            'status'  => 'Active',
        ]);

        $program = Program::create([
            'program_name' => 'Feixiang',
            'code'         => 'FX',
        ]);

        $progLevel = ProgramLevel::create([
            'program_id' => $program->id,
            'level_name' => 'Level 1',
        ]);

        $package = ProgramPackage::create([
            'program_id'       => $program->id,
            'package_name'     => 'Reguler 20 Sesi',
            'duration_minutes' => 90,
            'price'            => 500000,
            'total_meetings'   => 20,
            'min_students'     => 1,
            'max_students'     => 10,
        ]);

        $this->student = Student::create([
            'user_id'   => $siswaUser->id,
            'name'      => 'Murid Budi',
            'join_date' => '2026-11-01',
            'status'    => 'Active',
        ]);

        // Buat kelas hari Senin 10:00 - 11:30
        $this->class = ClassModel::create([
            'class_name'         => 'Feixiang Senin',
            'program_package_id' => $package->id,
            'level_id'           => $progLevel->id,
            'teacher_id'         => $this->teacher->id,
            'teacher_response'   => 'Accepted',
            'delivery_mode'      => 'Online',
            'status'             => 'Running',
            'start_date'         => '2026-11-16', // Senin
            'end_date'           => '2026-12-31',
            'capacity'           => 10,
        ]);

        ClassEnrollment::create([
            'class_id'           => $this->class->id,
            'student_id'         => $this->student->id,
            'program_package_id' => $package->id,
            'enrollment_date'    => '2026-11-01',
            'status'             => 'Active',
        ]);

        // Ketersediaan guru: Senin, Selasa, Rabu 08:00 - 18:00
        foreach (['Senin', 'Selasa', 'Rabu'] as $d) {
            TeacherAvailability::create([
                'teacher_id' => $this->teacher->id,
                'day'        => $d,
                'start_time' => '08:00:00',
                'end_time'   => '18:00:00',
            ]);
        }

        $this->schedule = ClassSchedule::create([
            'class_id'        => $this->class->id,
            'day'             => 'Senin',
            'start_time'      => '10:00:00',
            'end_time'        => '11:30:00',
            'room'            => 'Online',
            'effective_from'  => '2026-11-16',
            'effective_until' => null,
        ]);

        // Pertemuan 1: 2026-11-16 (Senin)
        $this->session1 = ClassSession::create([
            'class_id'          => $this->class->id,
            'class_schedule_id' => $this->schedule->id,
            'kind'              => 'Regular',
            'session_number'    => 1,
            'session_date'      => '2026-11-16',
            'start_time'        => '10:00:00',
            'end_time'          => '11:30:00',
            'room'              => 'Online',
            'teacher_id'        => $this->teacher->id,
            'status'            => 'Scheduled',
            'location_type'     => 'Online',
        ]);

        // Pertemuan 2: 2026-11-23 (Senin)
        $this->session2 = ClassSession::create([
            'class_id'          => $this->class->id,
            'class_schedule_id' => $this->schedule->id,
            'kind'              => 'Regular',
            'session_number'    => 2,
            'session_date'      => '2026-11-23',
            'start_time'        => '10:00:00',
            'end_time'          => '11:30:00',
            'room'              => 'Online',
            'teacher_id'        => $this->teacher->id,
            'status'            => 'Scheduled',
            'location_type'     => 'Online',
        ]);
    }

    /**
     * Skenario A: Ubah SATU pertemuan saja (Senin 16 Nov -> Selasa 17 Nov).
     * Cek:
     * - class_sessions berpindah ke Selasa 17 Nov.
     * - class_schedules pola mingguan tetap Senin.
     * - Guru: schedule & attendance membaca Selasa 17 Nov dan TIDAK membaca Senin 16 Nov.
     * - Kurikulum: Materi Harian menampilkan Selasa 17 Nov, tidak ada di Senin 16 Nov.
     */
    public function test_skenario_a_reschedule_one_session(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $response = $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $this->session1),
            [
                'scope'        => 'one',
                'session_date' => '2026-11-17', // Selasa
                'start_time'   => '10:00',
                'room'         => 'Online',
                'reason'       => 'Reschedule sekali',
            ]
        );

        $response->assertOk();

        // 1. Database class_sessions
        $this->session1->refresh();
        $this->assertEquals('2026-11-17', $this->session1->session_date->toDateString());
        $this->assertEquals('2026-11-16', $this->session1->original_date->toDateString());

        // Pola rutin tidak berubah
        $this->assertEquals('Senin', $this->schedule->fresh()->day);

        // 2. Guru Schedule (minggu 16-22 Nov)
        $guruRes = $this->actingAs($this->guruUser)->get(route('teacher.schedule', ['week' => '2026-11-16', 'scope' => 'mine']));
        $guruRes->assertOk();
        $cells = $guruRes->viewData('cells');
        // Sesi ada di Selasa, tidak ada di Senin
        $this->assertEmpty($cells['10:00']['Senin'] ?? []);
        $this->assertNotEmpty($cells['10:00']['Selasa'] ?? []);

        // 3. Guru Attendance
        // Senin 16 Nov: kosong
        $attSenin = $this->actingAs($this->guruUser)->get(route('teacher.attendance', ['date' => '2026-11-16']));
        $attSenin->assertOk();
        $this->assertEmpty($attSenin->viewData('sessions'));

        // Selasa 17 Nov: ada sesi
        $attSelasa = $this->actingAs($this->guruUser)->get(route('teacher.attendance', ['date' => '2026-11-17']));
        $attSelasa->assertOk();
        $this->assertNotEmpty($attSelasa->viewData('sessions'));

        // 4. Kurikulum Materi Harian (diakses admin)
        $kurSenin = $this->actingAs($this->adminUser)->get(route('admin.kurikulum-materi', ['tab' => 'harian', 'date' => '2026-11-16']));
        $kurSenin->assertOk();
        $this->assertEmpty($kurSenin->viewData('sessions'));

        $kurSelasa = $this->actingAs($this->adminUser)->get(route('admin.kurikulum-materi', ['tab' => 'harian', 'date' => '2026-11-17']));
        $kurSelasa->assertOk();
        $this->assertNotEmpty($kurSelasa->viewData('sessions'));
    }

    /**
     * Skenario B: Ubah SEMUA jadwal rutin (Senin -> Selasa).
     * Cek:
     * - Pola lama Senin ditutup (effective_until terisi).
     * - Pola baru Selasa dibuat.
     * - Sesi mendatang berpindah ke Selasa.
     */
    public function test_skenario_b_reschedule_following(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $response = $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $this->session1),
            [
                'scope'      => 'following',
                'day'        => 'Selasa',
                'start_time' => '10:00',
                'room'       => 'Online',
                'reason'     => 'Pindah rutin',
            ]
        );

        $response->assertOk();

        $this->session1->refresh();
        $this->session2->refresh();

        $this->assertEquals('2026-11-17', $this->session1->session_date->toDateString());
        $this->assertEquals('2026-11-24', $this->session2->session_date->toDateString());

        // Pola baru ada di Selasa
        $newSchedule = ClassSchedule::where('class_id', $this->class->id)
            ->where('day', 'Selasa')
            ->first();
        $this->assertNotNull($newSchedule);
        $this->assertEquals($newSchedule->id, $this->session1->class_schedule_id);
        $this->assertEquals($newSchedule->id, $this->session2->class_schedule_id);

        // Monitoring Kurikulum hanya menampilkan pola aktif (Selasa), bukan ganda
        $monRes = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.monitoring'));
        $monRes->assertOk();
        $weekly = $monRes->viewData('weeklySchedules');
        $this->assertTrue($weekly->contains('day', 'Selasa'));
        $this->assertFalse($weekly->contains('day', 'Senin'));
    }

    /**
     * Jurnal yang sudah terisi ikut pindah tanggal dan isinya utuh.
     */
    public function test_journal_moves_with_session_and_remains_intact(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $journal = TeachingJournal::create([
            'teacher_id'          => $this->teacher->id,
            'filled_by'           => $this->teacher->id,
            'class_id'            => $this->class->id,
            'class_schedule_id'   => $this->schedule->id,
            'class_session_id'    => $this->session1->id,
            'session_date'        => '2026-11-16',
            'class_status'        => 'Conducted',
            'learning_activities' => 'Kegiatan belajar teks mandarin lengkap',
            'problems'            => 'Tidak ada kendala',
            'solutions'           => 'Lancar',
            'results'             => 'Memahami materi',
            'status'              => 'Pending',
            'meeting_number'      => 1,
            'start_time'          => '10:00',
            'end_time'            => '11:30',
            'student_names'       => 'Murid Budi',
            'is_last_meeting'     => false,
        ]);

        Attendance::create([
            'teaching_journal_id' => $journal->id,
            'student_id'          => $this->student->id,
            'attendance_date'     => '2026-11-16',
            'session_date'        => '2026-11-16',
            'status'              => 'Present',
        ]);

        // Reschedule
        $response = $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $this->session1),
            [
                'scope'        => 'one',
                'session_date' => '2026-11-17',
                'start_time'   => '10:00',
                'room'         => 'Online',
            ]
        );

        $response->assertOk();

        $journal->refresh();
        $this->assertEquals('2026-11-17', $journal->session_date->toDateString());
        $this->assertEquals('Kegiatan belajar teks mandarin lengkap', $journal->learning_activities);

        // Tidak ada jurnal ganda
        $this->assertEquals(1, TeachingJournal::where('class_session_id', $this->session1->id)->count());

        // Attendance juga pindah tanggal
        $att = Attendance::where('teaching_journal_id', $journal->id)->first();
        $this->assertEquals('2026-11-17', $att->attendance_date->toDateString());
    }

    /**
     * Bentrok guru ditolak dan data tidak berubah (rollback).
     */
    public function test_teacher_clash_is_rejected_and_rolled_back(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        // Buat kelas lain untuk guru yang sama pada hari Selasa 10:00 - 11:30
        $clashClass = ClassModel::create([
            'class_name'         => 'Kelas Bentrok',
            'program_package_id' => $this->class->program_package_id,
            'level_id'           => $this->class->level_id,
            'teacher_id'         => $this->teacher->id,
            'teacher_response'   => 'Accepted',
            'delivery_mode'      => 'Online',
            'status'             => 'Running',
            'start_date'         => '2026-11-17',
            'capacity'           => 10,
        ]);

        ClassSession::create([
            'class_id'       => $clashClass->id,
            'kind'           => 'Regular',
            'session_number' => 1,
            'session_date'   => '2026-11-17',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'room'           => 'Online',
            'teacher_id'     => $this->teacher->id,
            'status'         => 'Scheduled',
            'location_type'  => 'Online',
        ]);

        // Coba reschedule session1 ke Selasa 10:00
        $response = $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $this->session1),
            [
                'scope'        => 'one',
                'session_date' => '2026-11-17',
                'start_time'   => '10:00',
                'room'         => 'Online',
            ]
        );

        $response->assertStatus(422);

        // Data tidak berubah (tetap 2026-11-16)
        $this->session1->refresh();
        $this->assertEquals('2026-11-16', $this->session1->session_date->toDateString());
    }

    /**
     * Guru bisa menyimpan jurnal di hari Selasa untuk sesi yang dipindahkan.
     */
    public function test_teacher_can_store_journal_on_rescheduled_tuesday(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        // Admin reschedule sesi ke Selasa 17 Nov
        $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $this->session1),
            [
                'scope'        => 'one',
                'session_date' => '2026-11-17',
                'start_time'   => '10:00',
                'room'         => 'Online',
            ]
        )->assertOk();

        Carbon::setTestNow('2026-11-17 11:35:00');

        // Guru mengisi jurnal pada hari Selasa 17 Nov
        $response = $this->actingAs($this->guruUser)->post(
            route('teacher.attendance.store', $this->schedule),
            [
                'teaching_teacher_id' => $this->teacher->id,
                'is_substitute'       => 'Tidak',
                'student_names'       => 'Murid Budi',
                'session_date'        => '2026-11-17',
                'is_last_meeting'     => 'Tidak',
                'meeting_number'      => 1,
                'start_time'          => '10:00',
                'end_time'            => '11:30',
                'class_running'       => 'Berjalan',
                'focus_materials'     => ['Pinyin'],
                'material_taught'     => 'Pengenalan nada',
                'learning_activities' => 'Belajar pinyin dan hanzi',
                'problems'            => '-',
                'solutions'           => '-',
                'results'             => 'Bagus',
                'session_score'       => '10',
                'attendance'          => [
                    $this->student->id => 'Present',
                ],
            ]
        );

        $response->assertRedirect(route('teacher.attendance', ['date' => '2026-11-17']));

        // Verifikasi jurnal tersimpan dan terhubung ke session1
        $savedJournal = TeachingJournal::where('class_id', $this->class->id)->first();
        $this->assertNotNull($savedJournal);
        $this->assertEquals('2026-11-17', $savedJournal->session_date->toDateString());
        $this->assertEquals($this->session1->id, $savedJournal->class_session_id);
    }

    /**
     * Monitoring Kurikulum:
     * - Menampilkan sesi nyata pada minggu yang dipilih (termasuk hasil reschedule).
     * - Sesi dipindah Sab -> Jum tampil di Jumat 10:00 dengan label "Dipindah dari ...", dan TIDAK di Sabtu.
     * - Minggu lain tetap menampilkan pola normal (Senin).
     */
    public function test_monitoring_kurikulum_shows_rescheduled_session_in_target_week_and_normal_in_other_week(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        // Buat kelas Private hari Sabtu 10:00 - 11:30
        $satSchedule = ClassSchedule::create([
            'class_id'        => $this->class->id,
            'day'             => 'Sabtu',
            'start_time'      => '10:00:00',
            'end_time'        => '11:30:00',
            'room'            => 'Online',
            'effective_from'  => '2026-11-16',
            'effective_until' => null,
        ]);

        // Pertemuan pada Sabtu 21 Nov 2026
        // Update session2 ke pertemuan 3 (30 Nov) agar urutan konsisten
        $this->session2->update(['session_number' => 3, 'session_date' => '2026-11-30']);

        $satSession = ClassSession::create([
            'class_id'          => $this->class->id,
            'class_schedule_id' => $satSchedule->id,
            'kind'              => 'Regular',
            'session_number'    => 2, // Pertemuan 1 = 16 Nov, Pertemuan 2 = 21 Nov
            'session_date'      => '2026-11-21', // Sabtu
            'start_time'        => '10:00:00',
            'end_time'          => '11:30:00',
            'room'              => 'Online',
            'teacher_id'        => $this->teacher->id,
            'status'            => 'Scheduled',
            'location_type'     => 'Online',
        ]);

        // Admin reschedule sesi Sabtu 21 Nov -> Jumat 20 Nov 2026
        $res = $this->actingAs($this->adminUser)->putJson(
            route('admin.kelas.sessions.reschedule', $satSession),
            [
                'scope'                         => 'one',
                'session_date'                  => '2026-11-20', // Jumat
                'start_time'                    => '10:00',
                'room'                          => 'Online',
                'reason'                        => 'Reschedule ke Jumat',
                'confirm_outside_availability'  => 1,
            ]
        );
        $res->assertOk();

        // 1. Cek monitoring pada minggu tersebut (week=2026-11-16)
        $monTargetWeek = $this->actingAs($this->kurikulumUser)->get(
            route('kurikulum.monitoring', ['week' => '2026-11-16'])
        );
        $monTargetWeek->assertOk();
        $weeklyTarget = $monTargetWeek->viewData('weeklySchedules');

        // Harus ada entri di hari Jumat jam 10:00
        $fridayEntry = $weeklyTarget->first(fn ($e) => $e->day === 'Jumat' && substr($e->start_time, 0, 5) === '10:00');
        $this->assertNotNull($fridayEntry, 'Kartu harus muncul di hari Jumat jam 10:00');
        $this->assertStringContainsString('Dipindah dari Sab, 21 Nov', (string) $fridayEntry->moved_note);

        // Tidak boleh ada entri Sabtu jam 10:00 untuk sesi yang sama pada minggu tersebut
        $saturdayEntry = $weeklyTarget->first(fn ($e) => $e->day === 'Sabtu' && substr($e->start_time, 0, 5) === '10:00');
        $this->assertNull($saturdayEntry, 'Kartu tidak boleh ada di hari Sabtu');

        // Cek response HTML merender label pindah
        $monTargetWeek->assertSee('Dipindah dari Sab, 21 Nov');

        // 2. Cek monitoring pada minggu berikutnya (week=2026-11-23)
        // Minggu lain belum ada class_sessions untuk hari Sabtu, sehingga fallback ke pola normal class_schedules (Sabtu)
        $monOtherWeek = $this->actingAs($this->kurikulumUser)->get(
            route('kurikulum.monitoring', ['week' => '2026-11-23'])
        );
        $monOtherWeek->assertOk();
        $weeklyOther = $monOtherWeek->viewData('weeklySchedules');

        // Pada minggu lain, pola Sabtu tetap muncul dari class_schedules
        $otherSat = $weeklyOther->first(fn ($e) => $e->day === 'Sabtu' && substr($e->start_time, 0, 5) === '10:00');
        $this->assertNotNull($otherSat, 'Pola normal Sabtu tetap muncul di minggu lain');
        $this->assertNull($otherSat->moved_note);
    }
}
