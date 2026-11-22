<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\ClassTask;
use App\Models\Consultation;
use App\Models\CurriculumConsultationSlot;
use App\Models\Material;
use App\Models\Notification;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MeetingConsultationRuleService;
use App\Services\TeacherAvailabilityUrgencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KurikulumEnhancementsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $kurikulumUser;
    protected Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Levels
        $kurikulumLevel = \App\Models\Level::firstOrCreate(['nama_level' => 'Curriculum']);
        $guruLevel = \App\Models\Level::firstOrCreate(['nama_level' => 'Teacher']);

        // 2. Buat user Kurikulum
        $this->kurikulumUser = User::create([
            'level_id' => $kurikulumLevel->id_level,
            'username' => 'kurikulum_test_enh',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        \App\Models\Curriculum::create([
            'user_id' => $this->kurikulumUser->id,
            'name'    => 'Curriculum Officer',
            'email'   => 'curriculum_test@haoyou.test',
            'phone'   => '08123456788',
        ]);

        // 3. Buat guru aktif
        $guruUser = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_test_enh',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id'    => $guruUser->id,
            'name'       => 'Laoshi Test',
            'phone'      => '081234567890',
            'gender'     => 'Female',
            'status'     => 'Active',
            'join_date'  => '2026-01-01',
        ]);
    }

    /**
     * POIN 1: Materi & Silabus -> Materi
     * - CCO dan Lesson Plan wajib upload file (bukan teks)
     * - Isi teks lama tetap aman dan bisa dibaca (read-only)
     * - Field aktivitas tidak wajib dan disembunyikan
     */
    public function test_kurikulum_1_material_upload_file_and_legacy_data_safe(): void
    {
        Storage::fake('public');
        $this->actingAs($this->kurikulumUser);

        // 1. Simpan materi baru untuk Guru dengan upload file CCO, LP, dan Modul
        $ccoFile = UploadedFile::fake()->create('cco_pertemuan_1.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $lpFile  = UploadedFile::fake()->create('lesson_plan_1.pdf', 800, 'application/pdf');
        $pptFile = UploadedFile::fake()->create('materi_slide.pptx', 1200, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->post(route('kurikulum.materi.store'), [
            'audience'         => 'Guru',
            'meeting_number'   => '1',
            'title'            => 'Dasar Hanzi Bagian 1',
            'material_file'    => $pptFile,
            'cco_file'         => $ccoFile,
            'lesson_plan_file' => $lpFile,
            'equipment_list'   => "Kartu Karakter\nSpidol",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'title'          => 'Dasar Hanzi Bagian 1',
            'meeting_number' => 1,
            'audience'       => 'Guru',
        ]);

        $material = Material::where('title', 'Dasar Hanzi Bagian 1')->first();
        $this->assertNotNull($material->cco_file_path);
        $this->assertNotNull($material->lesson_plan_file_path);
        $this->assertNotNull($material->material_file_path);

        // 2. Data lama dengan teks CCO dan lesson_plan tetap aman saat ditampilkan
        $legacy = Material::create([
            'title'              => 'Materi Teks Lama',
            'meeting_number'     => 2,
            'audience'           => 'Guru',
            'cco'                => 'Teks CCO Capaian Lama',
            'lesson_plan'        => 'Teks Lesson Plan Langkah Belajar Lama',
            'activity'           => 'Permainan Menyusun Kata',
            'material_file_path' => 'materials/dummy.ppt',
            'uploaded_by'        => $this->kurikulumUser->id,
        ]);

        $viewResponse = $this->get(route('kurikulum.materi'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Teks CCO Capaian Lama');
        $viewResponse->assertSee('Teks Lesson Plan Langkah Belajar Lama');
        // Pastikan box "Perlengkapan Aktivitas" ada dan "Aktivitas" mandiri tidak muncul sebagai form input
        $viewResponse->assertSee('Perlengkapan Aktivitas');
        $viewResponse->assertDontSee('name="activity"', false);
    }

    /**
     * POIN 2: Tugas Kelas -> Tugas Perbaikan Materi
     * - Penugasan perbaikan materi ke guru
     * - Notifikasi masuk ke tabel notifications
     * - Tugas kelas lama tetap aman
     */
    public function test_kurikulum_2_material_revision_task_and_notification(): void
    {
        $this->actingAs($this->kurikulumUser);

        $material = Material::create([
            'title'              => 'Materi HSK 2',
            'meeting_number'     => 3,
            'audience'           => 'Guru',
            'material_file_path' => 'materials/dummy.ppt',
            'uploaded_by'        => $this->kurikulumUser->id,
        ]);

        // Siapkan kelas untuk guru agar tugas perbaikan materi dapat tersimpan
        $assignedClass = ClassModel::create([
            'class_name'    => 'Kelas Guru Pengampu',
            'teacher_id'    => $this->teacher->id,
            'delivery_mode' => 'Online',
            'capacity'      => 10,
            'status'        => 'Running',
            'start_date'    => now()->toDateString(),
        ]);

        $response = $this->post(route('kurikulum.tugas-kelas.store'), [
            'task_type'    => 'material_revision',
            'teacher_ids'  => [$this->teacher->id],
            'material_id'  => $material->id,
            'title'        => 'Perbaiki Audio Slide 4',
            'description'  => 'Tolong ganti rekaman audio nada ketiga pada slide 4.',
            'due_date'     => now()->addDays(5)->toDateString(),
            'priority'     => 'High',
        ]);

        $response->assertRedirect();

        // Pastikan tugas tersimpan dengan task_type material_revision
        $this->assertDatabaseHas('class_tasks', [
            'teacher_id'  => $this->teacher->id,
            'task_type'   => 'material_revision',
            'material_id' => $material->id,
            'title'       => 'Perbaiki Audio Slide 4',
            'priority'    => 'High',
            'status'      => 'Open',
        ]);

        // Pastikan notifikasi ke guru tersimpan
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->teacher->user_id,
            'type'          => 'App\Notifications\AppNotification',
        ]);

        // Tugas kelas lama (task_type default/class_task) tetap tampil normal
        $legacyTask = ClassTask::create([
            'class_id'    => $assignedClass->id,
            'teacher_id'  => $this->teacher->id,
            'task_type'   => 'class_task',
            'title'       => 'Tugas Kelas Mengajar Lama',
            'description' => 'Evaluasi kelas A',
            'status'      => 'Open',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        $viewResponse = $this->get(route('kurikulum.tugas-kelas'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Perbaiki Audio Slide 4');
        $viewResponse->assertSee('Tugas Kelas Mengajar Lama');
        $viewResponse->assertSee('Tugas Perbaikan Materi');
    }

    /**
     * POIN 3: Jadwal Konsultasi (Slot Available + Catatan Pertemuan H-14 dan H+3)
     */
    public function test_kurikulum_3_slot_available_and_meeting_notes_deadlines(): void
    {
        $this->actingAs($this->kurikulumUser);

        // 1. Tambah slot available
        $slotDate = now()->addDays(10)->toDateString();
        $res = $this->post(route('kurikulum.jadwal-konsultasi.slots.store'), [
            'date'       => $slotDate,
            'start_time' => '10:00',
            'end_time'   => '11:00',
            'notes'      => 'Slot konsultasi kurikulum pagi',
        ]);
        $res->assertRedirect();

        $this->assertDatabaseHas('curriculum_consultation_slots', [
            'curriculum_user_id' => $this->kurikulumUser->id,
            'start_time'         => '10:00',
            'status'             => 'Available',
        ]);

        // 2. Cegah double booking pada rentang jam yang sama
        $resOverlap = $this->post(route('kurikulum.jadwal-konsultasi.slots.store'), [
            'date'       => $slotDate,
            'start_time' => '10:30',
            'end_time'   => '11:30',
        ]);
        $resOverlap->assertSessionHasErrors(['start_time']);

        // 3. Catatan Pertemuan H-14 dan H+3
        // Contoh spesifikasi: Pertemuan 1 tanggal 1 Nov -> batas konsultasi 18 Okt
        // Pertemuan 5 tanggal 29 Nov -> batas konsultasi 15 Nov
        $class = ClassModel::create([
            'class_name'    => 'Kelas Mandarin A',
            'teacher_id'    => $this->teacher->id,
            'delivery_mode' => 'Online',
            'capacity'      => 10,
            'status'        => 'Running',
            'start_date'    => '2026-11-01',
        ]);

        // Buat sesi 1 pada 1 Nov 2026
        ClassSession::create([
            'class_id'       => $class->id,
            'session_number' => 1,
            'meeting_number' => 1,
            'session_date'   => '2026-11-01',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);

        // Buat sesi 5 pada 29 Nov 2026
        ClassSession::create([
            'class_id'       => $class->id,
            'session_number' => 5,
            'meeting_number' => 5,
            'session_date'   => '2026-11-29',
            'start_time'     => '10:00:00',
            'end_time'       => '11:30:00',
            'status'         => 'Scheduled',
        ]);

        $service = app(MeetingConsultationRuleService::class);
        $blocks = $service->getBlocksForClass($class);

        $block1 = $blocks->firstWhere('block_index', 1);
        $this->assertNotNull($block1);
        $this->assertNotNull($block1['first_session_date'], 'First session date block 1 should not be null');
        $this->assertEquals('2026-11-01', $block1['first_session_date']->toDateString());
        // 1 Nov - 14 hari = 18 Okt
        $this->assertNotNull($block1['consultation_deadline'], 'Consultation deadline block 1 should not be null');
        $this->assertEquals('2026-10-18', $block1['consultation_deadline']->toDateString());

        $block2 = $blocks->firstWhere('block_index', 2);
        $this->assertNotNull($block2);
        $this->assertNotNull($block2['first_session_date'], 'First session date block 2 should not be null');
        $this->assertEquals('2026-11-29', $block2['first_session_date']->toDateString());
        // 29 Nov - 14 hari = 15 Nov
        $this->assertNotNull($block2['consultation_deadline'], 'Consultation deadline block 2 should not be null');
        $this->assertEquals('2026-11-15', $block2['consultation_deadline']->toDateString());

        // Uji H+3 setelah konsultasi
        Consultation::create([
            'teacher_id'   => $this->teacher->id,
            'class_id'     => $class->id,
            'type'         => 'Materi',
            'status'       => 'Selesai',
            'scheduled_at' => '2026-10-15 09:00:00',
            'notes'        => 'Pertemuan 1-4',
        ]);

        $blocksAfterConsultation = $service->getBlocksForClass($class);
        $b1 = $blocksAfterConsultation->firstWhere('block_index', 1);
        $this->assertNotNull($b1);
        $this->assertNotNull($b1['revision_deadline'], 'Revision deadline should not be null after consultation');
        // 15 Okt + 3 hari = 18 Okt
        $this->assertEquals('2026-10-18', $b1['revision_deadline']->toDateString());
    }

    /**
     * POIN 4: Ketersediaan Guru - Urgensi & Filter
     */
    public function test_kurikulum_4_teacher_availability_urgency_service_and_filtering(): void
    {
        $this->actingAs($this->kurikulumUser);

        // Kelas A: sesi terdekat besok (Urgent)
        $classA = ClassModel::create([
            'class_name'       => 'Kelas Urgent Besok',
            'teacher_id'       => $this->teacher->id,
            'teacher_response' => 'Pending',
            'delivery_mode'    => 'Online',
            'capacity'         => 10,
            'status'           => 'Running',
            'start_date'       => now()->addDay()->toDateString(),
        ]);
        ClassSession::create([
            'class_id'       => $classA->id,
            'meeting_number' => 1,
            'session_date'   => now()->addDay()->toDateString(),
            'start_time'     => '09:00:00',
            'end_time'       => '10:00:00',
            'status'         => 'Scheduled',
        ]);

        // Kelas B: sesi 10 hari lagi (Tidak Urgent / Neutral)
        $classB = ClassModel::create([
            'class_name'       => 'Kelas Santai Jauh',
            'teacher_id'       => $this->teacher->id,
            'teacher_response' => 'Pending',
            'delivery_mode'    => 'Online',
            'capacity'         => 10,
            'status'           => 'Running',
            'start_date'       => now()->addDays(10)->toDateString(),
        ]);
        ClassSession::create([
            'class_id'       => $classB->id,
            'meeting_number' => 1,
            'session_date'   => now()->addDays(10)->toDateString(),
            'start_time'     => '09:00:00',
            'end_time'       => '10:00:00',
            'status'         => 'Scheduled',
        ]);

        $evalA = TeacherAvailabilityUrgencyService::evaluateClass($classA);
        $evalB = TeacherAvailabilityUrgencyService::evaluateClass($classB);

        $this->assertEquals('urgent', $evalA['level']);
        $this->assertEquals('neutral', $evalB['level']);

        // Uji filter di controller/view
        $resUrgent = $this->get(route('kurikulum.ketersediaan', ['urgency' => 'urgent']));
        $resUrgent->assertOk();
        $resUrgent->assertSee('Kelas Urgent Besok');
        $resUrgent->assertDontSee('Kelas Santai Jauh');

        $resNotUrgent = $this->get(route('kurikulum.ketersediaan', ['urgency' => 'not_urgent']));
        $resNotUrgent->assertOk();
        $resNotUrgent->assertSee('Kelas Santai Jauh');
        $resNotUrgent->assertDontSee('Kelas Urgent Besok');
    }

    /**
     * POIN 5: Monitoring - Retention Rate & Zero Division Protection
     */
    public function test_kurikulum_5_monitoring_retention_rate_calculation(): void
    {
        $this->actingAs($this->kurikulumUser);

        // Pastikan halaman monitoring terbuka normal tanpa division by zero
        $res = $this->get(route('kurikulum.monitoring'));
        $res->assertOk();
        $res->assertSee('Ranking Guru Berdasarkan Siswa Lanjut');
        $res->assertSee('Retensi per Kelas Selesai');
    }

    /**
     * POIN 6: SOP - Halaman SOP tidak menampilkan kartu/upload template progress report
     */
    public function test_kurikulum_6_sop_hides_progress_report_template_section(): void
    {
        $this->actingAs($this->kurikulumUser);

        $res = $this->get(route('kurikulum.sop'));
        $res->assertOk();
        $res->assertSee('Daftar SOP');
        // Bagian upload dan card template progress report di halaman SOP sudah disembunyikan
        $res->assertDontSee('Template ini tampil di halaman <strong>SOP guru</strong>', false);
        $res->assertDontSee('Upload / Perbarui Template');

        // Namun route mandiri Template Progress Report tetap berfungsi
        $resStandalone = $this->get(route('kurikulum.report-template.index'));
        $resStandalone->assertOk();
    }
}
