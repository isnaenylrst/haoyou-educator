<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Level;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\ScheduleJournalUnlock;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruUser;
    protected Teacher $teacher;
    protected ClassModel $class46;

    protected function setUp(): void
    {
        parent::setUp();

        $guruLevel = Level::create(['nama_level' => 'Teacher']);

        $this->guruUser = User::create([
            'username' => 'vanessa_test',
            'email'    => 'vanessa@test.com',
            'password' => bcrypt('password'),
            'level_id' => $guruLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Vanessa Tan',
            'phone'   => '08123456789',
            'status'  => 'Active',
        ]);

        $program = Program::create(['program_name' => 'Mandarin']);
        $pl = ProgramLevel::create(['program_id' => $program->id, 'level_name' => 'Basic']);
        $package = ProgramPackage::create([
            'program_id'       => $program->id,
            'level_id'         => $pl->id,
            'package_name'     => 'Paket Reguler',
            'duration_minutes' => 90,
            'total_meetings'   => 4,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 500000,
        ]);

        // Kelas start_date 21 Nov 2026, end_date 12 Des 2026
        $this->class46 = ClassModel::create([
            'program_package_id' => $package->id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Private - deeee',
            'delivery_mode'      => 'Online',
            'capacity'           => 1,
            'start_date'         => '2026-11-21',
            'end_date'           => '2026-12-12',
            'status'             => 'Running',
            'teacher_response'   => 'Accepted',
        ]);

        // Jadwal pola Sabtu 10:00 - 11:30
        ClassSchedule::create([
            'class_id'       => $this->class46->id,
            'day'            => 'Sabtu',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'room'           => 'Room 1',
            'effective_from' => '2026-11-21',
        ]);

        // Sesi nyata: Pertemuan 1 dipindah ke Jumat 20 Nov 2026 (orig 2026-11-21)
        ClassSession::create([
            'class_id'       => $this->class46->id,
            'kind'           => 'Regular',
            'session_number' => 1,
            'session_date'   => '2026-11-20',
            'original_date'  => '2026-11-21',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);

        // Sesi 2: Sabtu 28 Nov 2026
        ClassSession::create([
            'class_id'       => $this->class46->id,
            'kind'           => 'Regular',
            'session_number' => 2,
            'session_date'   => '2026-11-28',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);

        // Sesi 3: Sabtu 5 Des 2026
        ClassSession::create([
            'class_id'       => $this->class46->id,
            'kind'           => 'Regular',
            'session_number' => 3,
            'session_date'   => '2026-12-05',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);

        // Sesi 4: Sabtu 12 Des 2026
        ClassSession::create([
            'class_id'       => $this->class46->id,
            'kind'           => 'Regular',
            'session_number' => 4,
            'session_date'   => '2026-12-12',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);
    }

    public function test_teaching_log_november_displays_correct_rescheduled_dates_and_no_virtual_entries_before_start()
    {
        $response = $this->actingAs($this->guruUser)
            ->get(route('teacher.teaching-log', ['month' => '2026-11', 'class_id' => $this->class46->id]));

        $response->assertStatus(200);

        $sessions = $response->viewData('paginatedSessions');
        $sessionDates = collect($sessions->items())->map(fn ($s) => $s->date->toDateString())->values()->all();

        // Di bulan November 2026: Hanya ada 20 Nov dan 28 Nov
        // 7 Nov, 14 Nov, dan 21 Nov TIDAK boleh muncul
        $this->assertEqualsCanonicalizing(['2026-11-20', '2026-11-28'], $sessionDates);
        $this->assertNotContains('2026-11-07', $sessionDates);
        $this->assertNotContains('2026-11-14', $sessionDates);
        $this->assertNotContains('2026-11-21', $sessionDates);
    }

    public function test_teaching_log_december_displays_december_sessions()
    {
        $response = $this->actingAs($this->guruUser)
            ->get(route('teacher.teaching-log', ['month' => '2026-12', 'class_id' => $this->class46->id]));

        $response->assertStatus(200);

        $sessions = $response->viewData('paginatedSessions');
        $sessionDates = collect($sessions->items())->map(fn ($s) => $s->date->toDateString())->values()->all();

        // Di bulan Desember 2026: Sesi 3 (5 Des) dan Sesi 4 (12 Des)
        $this->assertEqualsCanonicalizing(['2026-12-05', '2026-12-12'], $sessionDates);
        // Tanggal setelah end_date (misal 19 Des, 26 Des) tidak boleh muncul
        $this->assertNotContains('2026-12-19', $sessionDates);
        $this->assertNotContains('2026-12-26', $sessionDates);
    }

    public function test_fallback_schedule_without_class_sessions_respects_start_date_and_end_date()
    {
        $program = Program::first();
        $pl = ProgramLevel::first();
        $package = ProgramPackage::first();

        // Kelas baru tanpa class_sessions sama sekali
        $classFallback = ClassModel::create([
            'program_package_id' => $package->id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Kelas Pola Fallback',
            'delivery_mode'      => 'Online',
            'capacity'           => 1,
            'start_date'         => '2026-11-15',
            'end_date'           => '2026-11-29',
            'status'             => 'Running',
            'teacher_response'   => 'Accepted',
        ]);

        ClassSchedule::create([
            'class_id'       => $classFallback->id,
            'day'            => 'Sabtu', // Di Nov 2026: 7, 14, 21, 28
            'start_time'     => '14:00:00',
            'end_time'       => '15:30:00',
            'room'           => 'Room 2',
            'effective_from' => '2026-11-15',
        ]);

        $response = $this->actingAs($this->guruUser)
            ->get(route('teacher.teaching-log', ['month' => '2026-11', 'class_id' => $classFallback->id]));

        $response->assertStatus(200);

        $sessions = $response->viewData('paginatedSessions');
        $sessionDates = collect($sessions->items())->map(fn ($s) => $s->date->toDateString())->values()->all();

        // Karena start_date 15 Nov dan end_date 29 Nov, Sabtu yang valid HANYA 21 Nov dan 28 Nov
        $this->assertEqualsCanonicalizing(['2026-11-21', '2026-11-28'], $sessionDates);
        $this->assertNotContains('2026-11-07', $sessionDates);
        $this->assertNotContains('2026-11-14', $sessionDates);
    }

    public function test_teaching_log_shows_existing_journal_as_filled()
    {
        $all = ClassSession::all();
        $this->assertNotEmpty($all, 'ClassSessions is empty! count: ' . $all->count());

        $session = ClassSession::where('class_id', $this->class46->id)
            ->whereDate('session_date', '2026-11-20')
            ->first();

        $this->assertNotNull($session, 'Session 2026-11-20 not found. Available: ' . json_encode($all->pluck('session_date')));

        $satSchedule = ClassSchedule::where('class_id', $this->class46->id)->first();

        TeachingJournal::create([
            'teacher_id'          => $this->teacher->id,
            'class_id'            => $this->class46->id,
            'class_schedule_id'   => $satSchedule->id,
            'class_session_id'    => $session->id,
            'session_date'        => '2026-11-20',
            'meeting_number'      => 1,
            'status'              => 'Pending',
            'class_status'        => 'Conducted',
            'learning_activities' => 'Kegiatan belajar teks mandarin lengkap',
            'problems'            => 'Tidak ada kendala',
            'solutions'           => 'Lancar',
            'results'             => 'Memahami materi',
            'is_last_meeting'     => false,
            'start_time'          => '10:00',
            'end_time'            => '11:30',
            'student_names'       => 'Student Test',
        ]);

        $response = $this->actingAs($this->guruUser)
            ->get(route('teacher.teaching-log', ['month' => '2026-11', 'class_id' => $this->class46->id]));

        $response->assertStatus(200);
        $sessions = collect($response->viewData('paginatedSessions')->items());
        $session20 = $sessions->firstWhere(fn ($s) => $s->date->toDateString() === '2026-11-20');

        $this->assertNotNull($session20);
        $this->assertEquals('Terisi', $session20->journal_status);
        $this->assertEquals('bg-success', $session20->journal_badge);
        $this->assertEquals(1, $session20->meeting_number);
    }
}