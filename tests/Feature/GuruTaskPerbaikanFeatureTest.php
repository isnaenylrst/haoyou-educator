<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\ClassTask;
use App\Models\Level;
use App\Models\Material;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Teacher;
use App\Models\TeacherMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruTaskPerbaikanFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $kurikulumUser;
    protected User $guru1User;
    protected Teacher $guru1;
    protected User $guru2User;
    protected Teacher $guru2;
    protected ClassModel $kelas1;
    protected Material $materi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $curriculumLevel = Level::firstOrCreate(['nama_level' => 'Curriculum']);
        $teacherLevel = Level::firstOrCreate(['nama_level' => 'Teacher']);

        $this->kurikulumUser = User::create([
            'level_id' => $curriculumLevel->id_level,
            'username' => 'kurikulum_test',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);

        $this->guru1User = User::create([
            'level_id' => $teacherLevel->id_level,
            'username' => 'guru_test_1',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        $this->guru1 = Teacher::create([
            'user_id'   => $this->guru1User->id,
            'status'    => 'Active',
            'name'      => 'Laoshi Satu',
            'phone'     => '081234567891',
            'gender'    => 'Female',
            'join_date' => '2026-01-01',
        ]);

        $this->guru2User = User::create([
            'level_id' => $teacherLevel->id_level,
            'username' => 'guru_test_2',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        $this->guru2 = Teacher::create([
            'user_id'   => $this->guru2User->id,
            'status'    => 'Active',
            'name'      => 'Laoshi Dua',
            'phone'     => '081234567892',
            'gender'    => 'Male',
            'join_date' => '2026-01-01',
        ]);

        $program = Program::firstOrCreate(['program_name' => 'Program Test']);
        $category = ProgramCategory::firstOrCreate([
            'program_id'    => $program->id,
            'category_name' => 'Reguler',
        ]);
        $progLevel = ProgramLevel::firstOrCreate([
            'program_id'  => $program->id,
            'category_id' => $category->id,
            'level_name'  => 'Dasar 1',
        ]);
        $package = ProgramPackage::firstOrCreate([
            'program_id'       => $program->id,
            'category_id'      => $category->id,
            'level_id'         => $progLevel->id,
            'package_name'     => 'Paket Reguler Dasar',
            'class_type'       => 'Group',
            'duration_minutes' => 60,
            'total_meetings'   => 12,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 500000,
        ]);

        $this->kelas1 = ClassModel::create([
            'class_name'         => 'Mandarin Dasar A',
            'program_package_id' => $package->id,
            'level_id'           => $progLevel->id,
            'teacher_id'         => $this->guru1->id,
            'delivery_mode'      => 'Online',
            'capacity'           => 10,
            'status'             => 'Running',
            'start_date'         => now()->toDateString(),
            'teacher_response'   => 'Accepted',
        ]);

        $this->materi = Material::create([
            'level_id'           => $progLevel->id,
            'title'              => 'Pertemuan 1 - Salam & Sapaan',
            'meeting_number'     => 1,
            'audience'           => 'Guru',
            'uploaded_by'        => $this->kurikulumUser->id,
            'material_file_path' => 'materials/dummy.ppt',
        ]);
    }

    /**
     * 1. Halaman /guru/kelas menampilkan tab dan tugas materi & tugas kelas yang sesuai untuk guru login
     */
    public function test_guru_can_view_tasks_in_tabs_with_badge_and_isolation(): void
    {
        // Buat tugas materi untuk guru 1
        $tugasMateri = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'material_id' => $this->materi->id,
            'title'       => 'Revisi Poin Kosakata Bab 1',
            'description' => 'Tolong lengkapi pinyin dan contoh kalimat.',
            'status'      => 'Open',
            'priority'    => 'High',
            'due_date'    => now()->addDays(3),
            'created_by'  => $this->kurikulumUser->id,
        ]);

        // Buat tugas kelas untuk guru 1
        $tugasKelas = ClassTask::create([
            'class_id'    => $this->kelas1->id,
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'class_task',
            'title'       => 'Penyesuaian Silabus Kelas Mandarin Dasar A',
            'description' => 'Mohon sesuaikan target mingguan siswa.',
            'status'      => 'Open',
            'priority'    => 'Medium',
            'due_date'    => now()->addDays(5),
            'created_by'  => $this->kurikulumUser->id,
        ]);

        // Buat tugas untuk guru 2 (guru 1 TIDAK boleh melihat tugas ini)
        $tugasGuru2 = ClassTask::create([
            'teacher_id'  => $this->guru2->id,
            'task_type'   => 'material_revision',
            'title'       => 'Tugas Rahasia Guru Dua',
            'description' => 'Hanya untuk guru 2.',
            'status'      => 'Open',
            'priority'    => 'Low',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        // Akses halaman /guru/kelas sebagai Guru 1
        $response = $this->actingAs($this->guru1User)->get(route('teacher.kelas', ['tab' => 'tugas-materi']));
        $response->assertStatus(200);
        $response->assertSee('Revisi Poin Kosakata Bab 1');
        $response->assertSee('Tidak terkait kelas');
        $response->assertDontSee('Tugas Rahasia Guru Dua');

        // Tab tugas kelas
        $responseClass = $this->actingAs($this->guru1User)->get(route('teacher.kelas', ['tab' => 'tugas-kelas']));
        $responseClass->assertStatus(200);
        $responseClass->assertSee('Penyesuaian Silabus Kelas Mandarin Dasar A');
        $responseClass->assertSee('Mandarin Dasar A');
        $responseClass->assertDontSee('Tugas Rahasia Guru Dua');

        // Akses sebagai Guru 2
        $responseG2 = $this->actingAs($this->guru2User)->get(route('teacher.kelas', ['tab' => 'tugas-materi']));
        $responseG2->assertStatus(200);
        $responseG2->assertSee('Tugas Rahasia Guru Dua');
        $responseG2->assertDontSee('Revisi Poin Kosakata Bab 1');
        $responseG2->assertDontSee('Penyesuaian Silabus Kelas Mandarin Dasar A');
    }

    /**
     * 2. Guru mengunggah berkas perbaikan untuk tugas materi (memakai ulang store dengan task_id)
     */
    public function test_guru_can_upload_repair_for_material_task(): void
    {
        $task = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'material_id' => $this->materi->id,
            'title'       => 'Perbaikan Slide PPT Bab 1',
            'description' => 'Tambahkan animasi karakter hanzi.',
            'status'      => 'Open',
            'priority'    => 'Medium',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        $lp = UploadedFile::fake()->create('revisi_lp.pdf', 500, 'application/pdf');
        $ppt = UploadedFile::fake()->create('revisi_ppt.pptx', 1000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $res = $this->actingAs($this->guru1User)->post(route('teacher.kelas.store'), [
            'task_id'  => $task->id,
            'lp_file'  => $lp,
            'ppt_file' => $ppt,
            'notes'    => 'Sudah saya perbaiki sesuai instruksi.',
        ]);

        $res->assertRedirect(route('teacher.kelas', ['tab' => 'tugas-materi']));
        $res->assertSessionHas('success');

        // Periksa pengajuan terhubung ke task_id
        $this->assertDatabaseHas('teacher_materials', [
            'task_id'    => $task->id,
            'teacher_id' => $this->guru1->id,
            'status'     => 'Diajukan',
        ]);

        $submission = TeacherMaterial::where('task_id', $task->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals('Diajukan', $task->fresh()->effective_status);

        // Periksa di halaman review Kurikulum tab material ada penanda "Perbaikan dari tugas"
        $kurikulumRes = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.review-pengajuan', ['tab' => 'material']));
        $kurikulumRes->assertStatus(200);
        $kurikulumRes->assertSee('Perbaikan dari tugas: Perbaikan Slide PPT Bab 1');
    }

    /**
     * 3. Guru lain dicegah mengunggah perbaikan untuk tugas milik guru lain
     */
    public function test_other_teacher_forbidden_to_upload_for_task(): void
    {
        $task = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'title'       => 'Tugas Khusus Guru Satu',
            'description' => 'Hanya untuk guru 1.',
            'status'      => 'Open',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        $lp = UploadedFile::fake()->create('lp.pdf', 500, 'application/pdf');
        $ppt = UploadedFile::fake()->create('ppt.pdf', 500, 'application/pdf');

        $res = $this->actingAs($this->guru2User)->post(route('teacher.kelas.store'), [
            'task_id'  => $task->id,
            'lp_file'  => $lp,
            'ppt_file' => $ppt,
        ]);

        $res->assertStatus(403);
    }

    /**
     * 4. Kurikulum mereview pengajuan tugas: Approved -> status tugas jadi Selesai (Done)
     */
    public function test_kurikulum_approves_task_submission_updates_task_status_to_done(): void
    {
        $task = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'material_id' => $this->materi->id,
            'title'       => 'Tugas Review Kurikulum',
            'status'      => 'Open',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        $submission = TeacherMaterial::create([
            'material_id'     => $this->materi->id,
            'teacher_id'      => $this->guru1->id,
            'task_id'         => $task->id,
            'ppt_title'       => 'Tugas Review Kurikulum',
            'lp_file_path'    => 'dummy/lp.pdf',
            'ppt_file_path'   => 'dummy/ppt.pdf',
            'status'          => 'Diajukan',
            'current_version' => 1,
        ]);

        $revRes = $this->actingAs($this->kurikulumUser)->patch(route('kurikulum.review-pengajuan.material', $submission), [
            'decision' => 'Approved',
        ]);

        $revRes->assertSessionHas('success');
        $this->assertEquals('Done', $task->fresh()->status);
        $this->assertEquals('Selesai', $task->fresh()->effective_status);
        $this->assertNotNull($task->fresh()->done_at);
    }

    /**
     * 5. Kurikulum meminta revisi: Rejected -> status tugas Perlu Revisi, lalu guru bisa reupload
     */
    public function test_kurikulum_rejects_task_submission_and_teacher_can_reupload(): void
    {
        $task = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'material_id' => $this->materi->id,
            'title'       => 'Tugas Perlu Revisi',
            'status'      => 'Open',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        $submission = TeacherMaterial::create([
            'material_id'     => $this->materi->id,
            'teacher_id'      => $this->guru1->id,
            'task_id'         => $task->id,
            'ppt_title'       => 'Tugas Perlu Revisi',
            'lp_file_path'    => 'dummy/lp.pdf',
            'ppt_file_path'   => 'dummy/ppt.pdf',
            'status'          => 'Diajukan',
            'current_version' => 1,
        ]);

        $revRes = $this->actingAs($this->kurikulumUser)->patch(route('kurikulum.review-pengajuan.material', $submission), [
            'decision' => 'Rejected',
            'note'     => 'Slide 4 masih kurang tepat terjemahannya.',
        ]);

        $revRes->assertSessionHas('success');
        $this->assertEquals('Perlu Revisi', $task->fresh()->effective_status);
        $this->assertEquals('Open', $task->fresh()->status);

        // Guru mengunggah revisi versi 2 melalui reupload
        $newPpt = UploadedFile::fake()->create('revisi_v2.pdf', 500, 'application/pdf');
        $reuploadRes = $this->actingAs($this->guru1User)->post(route('teacher.kelas.reupload', $submission), [
            'ppt_file' => $newPpt,
            'notes'    => 'Terjemahan slide 4 sudah diperbaiki.',
        ]);

        $reuploadRes->assertRedirect(route('teacher.kelas', ['tab' => 'tugas-materi']));
        $this->assertEquals(2, $submission->fresh()->current_version);
        $this->assertEquals('Diajukan', $submission->fresh()->status);
        $this->assertEquals('Diajukan', $task->fresh()->effective_status);
    }

    /**
     * 6. Validasi file: LP harus PDF maks 10MB, PPT PDF/PPT/PPTX maks 20MB
     */
    public function test_file_validation_rules_enforced(): void
    {
        $task = ClassTask::create([
            'teacher_id'  => $this->guru1->id,
            'task_type'   => 'material_revision',
            'title'       => 'Tugas Validasi File',
            'status'      => 'Open',
            'created_by'  => $this->kurikulumUser->id,
        ]);

        // File LP salah format (txt)
        $badLp = UploadedFile::fake()->create('lp.txt', 200, 'text/plain');
        $ppt = UploadedFile::fake()->create('ppt.pdf', 500, 'application/pdf');

        $res = $this->actingAs($this->guru1User)->post(route('teacher.kelas.store'), [
            'task_id'  => $task->id,
            'lp_file'  => $badLp,
            'ppt_file' => $ppt,
        ]);

        $res->assertSessionHasErrors(['lp_file']);
    }

    /**
     * 7. Tugas lama (tanpa task_id) dan pengajuan biasa tetap tampil dan berfungsi normal
     */
    public function test_legacy_submissions_and_regular_submissions_remain_functional(): void
    {
        // Pengajuan LP/PPT biasa tanpa tugas
        $lp = UploadedFile::fake()->create('biasa_lp.pdf', 500, 'application/pdf');
        $ppt = UploadedFile::fake()->create('biasa_ppt.pptx', 1000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $res = $this->actingAs($this->guru1User)->post(route('teacher.kelas.store'), [
            'class_id'    => $this->kelas1->id,
            'material_id' => $this->materi->id,
            'lp_file'     => $lp,
            'ppt_file'    => $ppt,
            'notes'       => 'Pengajuan biasa',
        ]);

        $res->assertRedirect(route('teacher.kelas', ['tab' => 'buat-lp']));
        $this->assertDatabaseHas('teacher_materials', [
            'class_id'    => $this->kelas1->id,
            'material_id' => $this->materi->id,
            'task_id'     => null,
            'status'      => 'Diajukan',
        ]);

        // Buka halaman guru dan review kurikulum
        $resGuru = $this->actingAs($this->guru1User)->get(route('teacher.kelas'));
        $resGuru->assertStatus(200);

        $resKurikulum = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.review-pengajuan', ['tab' => 'material']));
        $resKurikulum->assertStatus(200);
        $resKurikulum->assertSee($this->materi->title);
    }
}
