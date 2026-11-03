<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Curriculum;
use App\Models\Level;
use App\Models\OwnerActionLog;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgressReport;
use App\Models\SignatureHistory;
use App\Models\Student;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\TrainingMaterial;
use App\Models\User;
use App\Support\OwnerMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $kurikulumUser;
    protected User $guruUser;
    protected User $siswaUser;
    protected Teacher $teacher;
    protected Student $student;
    protected ClassModel $class;
    protected ClassEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        // 1. Setup Levels
        $ownerLevel = Level::create(['nama_level' => 'Owner']);
        $adminLevel = Level::create(['nama_level' => 'Admin']);
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);
        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $siswaLevel = Level::create(['nama_level' => 'Student']);

        // 2. Setup Users
        // Owner with fake signature
        $sigFile = UploadedFile::fake()->image('owner_sig.png');
        $sigPath = $sigFile->store('signatures', 'public');

        $this->ownerUser = User::create([
            'level_id'       => $ownerLevel->id_level,
            'username'       => 'owner_test',
            'password'       => bcrypt('password'),
            'signature_path' => $sigPath,
        ]);

        $this->adminUser = User::create([
            'level_id' => $adminLevel->id_level,
            'username' => 'admin_test',
            'password' => bcrypt('password'),
        ]);

        $this->kurikulumUser = User::create([
            'level_id'       => $kurikulumLevel->id_level,
            'username'       => 'kurikulum_test',
            'password'       => bcrypt('password'),
            'signature_path' => $sigPath,
        ]);
        Curriculum::create([
            'user_id' => $this->kurikulumUser->id,
            'name'    => 'Curriculum Officer',
            'email'   => 'curriculum@haoyou.test',
            'phone'   => '08123456788',
        ]);

        $this->guruUser = User::create([
            'level_id'       => $guruLevel->id_level,
            'username'       => 'guru_test',
            'password'       => bcrypt('password'),
            'signature_path' => $sigPath,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Laoshi Test',
            'email'   => 'laoshi@haoyou.test',
            'phone'   => '08123456789',
            'status'  => 'Active',
        ]);

        $this->siswaUser = User::create([
            'level_id' => $siswaLevel->id_level,
            'username' => 'siswa_test',
            'password' => bcrypt('password'),
        ]);
        $this->student = Student::create([
            'user_id'   => $this->siswaUser->id,
            'name'      => 'Student Test',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);

        // Program & Class
        $prog = Program::create(['program_name' => 'Mandarin Basic']);
        $cat = ProgramCategory::create(['program_id' => $prog->id, 'category_name' => 'Regular']);
        $level = ProgramLevel::create([
            'program_id'  => $prog->id,
            'category_id' => $cat->id,
            'level_name'  => 'Level 1',
        ]);

        $pkg = \App\Models\ProgramPackage::create([
            'program_id'       => $prog->id,
            'category_id'      => $cat->id,
            'level_id'         => $level->id,
            'package_name'     => 'Paket Reguler 1',
            'duration_minutes' => 60,
            'total_meetings'   => 12,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 500000,
            'is_active'        => true,
        ]);

        $this->class = ClassModel::create([
            'class_name'         => 'Mandarin Kelas A',
            'level_id'           => $level->id,
            'program_package_id' => $pkg->id,
            'teacher_id'         => $this->teacher->id,
            'delivery_mode'      => 'Online',
            'start_date'         => now()->toDateString(),
            'capacity'           => 10,
            'status'             => 'Running',
        ]);

        $this->enrollment = ClassEnrollment::create([
            'student_id'         => $this->student->id,
            'class_id'           => $this->class->id,
            'program_package_id' => $pkg->id,
            'enrollment_date'    => now()->toDateString(),
            'status'             => 'Active',
        ]);
    }

    /**
     * Test 1: Owner membuka SEMUA menu sidebar Owner (status 200)
     * Termasuk audit log pencatatan aksi Owner di area role lain
     */
    public function test_owner_can_access_all_sidebar_menus_and_actions_are_logged(): void
    {
        $this->actingAs($this->ownerUser);

        // Pasang guru pilihan di session agar menu guru bisa dibuka penuh
        session(['owner_view_teacher_id' => $this->teacher->id]);

        $groups = OwnerMenu::groups();
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $url = $item['url'];
                $response = $this->get($url);

                $this->assertContains($response->getStatusCode(), [200, 302], "Gagal membuka menu {$item['label']} ({$url})");
            }
        }

        // Pastikan aksi Owner di area role lain dicatat di owner_action_logs
        $this->assertTrue(
            OwnerActionLog::where('user_id', $this->ownerUser->id)->exists(),
            'Aksi Owner di area role lain wajib dicatat di tabel owner_action_logs'
        );
    }

    /**
     * Test 2: Guru ajukan bahan ajar -> Kurikulum ACC -> Owner TTD/Setujui -> Tandai Dibayar
     */
    public function test_submission_lifecycle_guru_to_kurikulum_to_owner_and_mark_paid(): void
    {
        // 1. Guru mengajukan bahan ajar
        $this->actingAs($this->guruUser);
        $receipt = UploadedFile::fake()->image('receipt.jpg');
        $photo = UploadedFile::fake()->image('photo.jpg');

        $submission = Submission::create([
            'teacher_id'   => $this->teacher->id,
            'created_by'   => $this->guruUser->id,
            'type'         => 'Pembelian Bahan Ajar',
            'item_name'    => 'Buku Belajar Hanzi Vol 1',
            'amount'       => 150000,
            'date'         => now()->toDateString(),
            'description'  => 'Untuk keperluan modul ajar kelas',
            'receipt_path' => $receipt->store('submission-receipts', 'public'),
            'photo_path'   => $photo->store('submission-photos', 'public'),
            'status'       => 'Diajukan',
        ]);

        $this->assertEquals('Diajukan', $submission->fresh()->status);

        // 2. Kurikulum ACC pengajuan
        $this->actingAs($this->kurikulumUser);
        $resKurikulum = $this->patch("/kurikulum/review-pengajuan/submission/{$submission->id}", [
            'decision' => 'Approved',
            'note'     => 'ACC oleh tim Kurikulum',
        ]);
        $resKurikulum->assertSessionHas('success');

        $submission->refresh();
        $this->assertNotNull($submission->curriculum_approved_at);
        $this->assertEquals('Review Admin', $submission->status);

        // 3. Owner Approve + TTD
        $this->actingAs($this->ownerUser);
        $resOwner = $this->post("/owner/pengajuan/{$submission->id}/approve", [
            'decision' => 'Approved',
            'note'     => 'Disetujui dan TTD oleh Owner',
        ]);
        $resOwner->assertSessionHas('success');

        $submission->refresh();
        $this->assertEquals('Disetujui', $submission->status);
        $this->assertNotNull($submission->owner_approved_at);
        $this->assertEquals($this->ownerUser->id, $submission->owner_id);
        $this->assertNotNull($submission->owner_signature_path);

        // 4. Owner Tandai Dibayar
        $resPaid = $this->post("/owner/pengajuan/{$submission->id}/mark-paid");
        $resPaid->assertSessionHas('success');

        $submission->refresh();
        $this->assertEquals('Dibayar', $submission->status);

        // 5. Guru melihat status pengajuan sudah 'Dibayar'
        $this->actingAs($this->guruUser);
        $resGuru = $this->get('/guru/pengajuan');
        $resGuru->assertStatus(200);
        $resGuru->assertSee('Dibayar');
    }

    /**
     * Test 3: Progress report ACC Kurikulum -> tampil di Siswa -> Owner TTD -> PDF memuat TTD Owner
     */
    public function test_progress_report_acc_to_student_and_owner_signature(): void
    {
        // 1. Progress Report dibuat oleh Guru (status Submitted)
        $report = ProgressReport::create([
            'student_id'    => $this->student->id,
            'teacher_id'    => $this->teacher->id,
            'enrollment_id' => $this->enrollment->id,
            'report_type'   => 'Trimester Academic Progress Report',
            'report_period' => '2026 Q1',
            'status'        => 'Submitted',
            'content'       => [
                ['material' => 'Hanzi Dasar', 'level' => 'Proficient', 'description' => 'Bagus'],
            ],
            'attendance_present' => 10,
            'attendance_total'   => 12,
        ]);

        // Sebelum Kurikulum ACC, siswa belum bisa melihat (reviewed_at null)
        $this->actingAs($this->siswaUser);
        $resBefore = $this->get('/progress-report');
        $resBefore->assertStatus(200);
        $this->assertEmpty(ProgressReport::approved()->where('student_id', $this->student->id)->get());

        // 2. Kurikulum ACC Progress Report
        $this->actingAs($this->kurikulumUser);
        $resAcc = $this->patch("/kurikulum/review-pengajuan/progress-report/{$report->id}", [
            'decision' => 'Approved',
        ]);
        $resAcc->assertSessionHas('success');

        $report->refresh();
        $this->assertNotNull($report->reviewed_at);

        // Siswa langsung bisa melihat laporan yang sudah di-ACC
        $this->actingAs($this->siswaUser);
        $this->assertCount(1, ProgressReport::approved()->where('student_id', $this->student->id)->get());

        // 3. Masuk antrean TTD Owner -> Owner TTD
        $this->actingAs($this->ownerUser);
        $resQueue = $this->get('/owner/ttd?tab=reports');
        $resQueue->assertStatus(200);
        $resQueue->assertSee($this->student->name);

        $resSign = $this->post("/owner/ttd/report/{$report->id}");
        $resSign->assertSessionHas('success');

        $report->refresh();
        $this->assertNotNull($report->owner_signed_at);
        $this->assertEquals($this->ownerUser->id, $report->owner_signed_by);
        $this->assertNotNull($report->owner_signature_path);
        $this->assertNotNull($report->file_path);

        // Riwayat TTD tercatat
        $this->assertTrue(
            SignatureHistory::where('document_type', 'progress_report')
                ->where('document_id', $report->id)
                ->where('signer_id', $this->ownerUser->id)
                ->exists()
        );
    }

    /**
     * Test 4: Sertifikat alur Kurikulum ACC -> Owner TTD
     */
    public function test_certificate_acc_and_owner_signature(): void
    {
        $cert = Certificate::create([
            'user_id'            => $this->siswaUser->id,
            'student_id'         => $this->student->id,
            'teacher_id'         => $this->teacher->id,
            'enrollment_id'      => $this->enrollment->id,
            'course_name'        => 'Mandarin Level 1',
            'student_name'       => $this->student->name,
            'certificate_number' => 'CERT/2026/001',
            'score'              => 95.0,
            'status'             => 'Pending',
        ]);

        // Kurikulum ACC
        $this->actingAs($this->kurikulumUser);
        $resKur = $this->patch("/kurikulum/review-pengajuan/certificate/{$cert->id}", [
            'decision' => 'Approved',
        ]);
        $resKur->assertSessionHas('success');
        $cert->refresh();
        $this->assertEquals('Approved', $cert->status);

        // Owner TTD Sertifikat di antrean
        $this->actingAs($this->ownerUser);
        $resTtd = $this->post("/owner/ttd/certificate/{$cert->id}");
        $resTtd->assertSessionHas('success');

        $cert->refresh();
        $this->assertNotNull($cert->owner_signed_at);
        $this->assertEquals($this->ownerUser->id, $cert->owner_signed_by);
        $this->assertNotNull($cert->owner_signature_path);

        // Riwayat TTD sertifikat tercatat
        $this->assertTrue(
            SignatureHistory::where('document_type', 'certificate')
                ->where('document_id', $cert->id)
                ->where('signer_id', $this->ownerUser->id)
                ->exists()
        );
    }

    /**
     * Test 5: Owner membuat konsultasi -> notifikasi dikirim ke Guru dan Kurikulum
     */
    public function test_owner_create_consultation_result_and_triggers_notifications(): void
    {
        $this->actingAs($this->ownerUser);

        $payload = [
            'teacher_id'         => $this->teacher->id,
            'consultation_date'  => now()->toDateString(),
            'topic'              => 'Evaluasi Pembelajaran Kuartal 1',
            'improvement_points' => ['Perlu lebih banyak interaksi verbal', 'Modul kuis diperbarui'],
            'follow_up'          => 'Monitoring minggu depan pada sesi Rabu',
        ];

        $response = $this->post('/owner/konsultasi', $payload);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('consultation_results', [
            'teacher_id' => $this->teacher->id,
            'topic'      => 'Evaluasi Pembelajaran Kuartal 1',
        ]);

        Notification::assertSentTo($this->guruUser, \App\Notifications\AppNotification::class);
        Notification::assertSentTo($this->kurikulumUser, \App\Notifications\AppNotification::class);
    }

    /**
     * Test 6: Owner upload training -> peran terkait melihat, peran terkait tidak bisa edit/hapus
     */
    public function test_training_materials_owner_crud_and_roles_read_only(): void
    {
        // 1. Owner upload training material untuk Guru
        $this->actingAs($this->ownerUser);
        $doc = UploadedFile::fake()->create('sop_guru.pdf', 500, 'application/pdf');

        $resOwner = $this->post('/owner/training', [
            'title'       => 'SOP Mengajar Guru 2026',
            'category'    => 'SOP & Administrasi Pengajar',
            'target_role' => 'Teacher',
            'level'       => 'Dasar',
            'type'        => 'document',
            'version'     => 'v1.0',
            'description' => 'Panduan standar mengajar di kelas',
            'file'        => $doc,
        ]);
        $resOwner->assertSessionHas('success');

        $material = TrainingMaterial::where('title', 'SOP Mengajar Guru 2026')->first();
        $this->assertNotNull($material);

        // 2. Guru bisa melihat materi training miliknya
        $this->actingAs($this->guruUser);
        $resGuru = $this->get('/guru/training');
        $resGuru->assertStatus(200);
        $resGuru->assertSee('SOP Mengajar Guru 2026');

        // Guru tidak bisa mengedit atau menghapus materi training (403 atau 405)
        $resEdit = $this->put("/owner/training/{$material->id}", ['title' => 'Hacked Title']);
        $this->assertContains($resEdit->getStatusCode(), [403, 405]);

        $resDelete = $this->delete("/owner/training/{$material->id}");
        $this->assertContains($resDelete->getStatusCode(), [403, 405]);

        // 3. Admin hanya melihat materi Admin, tidak melihat materi Teacher
        $this->actingAs($this->adminUser);
        $resAdmin = $this->get('/admin/training');
        $resAdmin->assertStatus(200);
        $resAdmin->assertDontSee('SOP Mengajar Guru 2026');
    }
}
