<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingMaterial;
use App\Models\TrainingMaterialRole;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerTrainingMaterialTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $kurikulumUser;
    protected User $guruUser;
    protected User $siswaUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        $ownerLevel = Level::create(['nama_level' => 'Owner']);
        $adminLevel = Level::create(['nama_level' => 'Admin']);
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);
        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $siswaLevel = Level::create(['nama_level' => 'Student']);

        $this->ownerUser = User::create([
            'level_id' => $ownerLevel->id_level,
            'username' => 'owner_user',
            'password' => bcrypt('password'),
        ]);

        $this->adminUser = User::create([
            'level_id' => $adminLevel->id_level,
            'username' => 'admin_user',
            'password' => bcrypt('password'),
        ]);

        $this->kurikulumUser = User::create([
            'level_id' => $kurikulumLevel->id_level,
            'username' => 'kurikulum_user',
            'password' => bcrypt('password'),
        ]);

        $this->guruUser = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_user',
            'password' => bcrypt('password'),
        ]);
        Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Laoshi Test',
            'email'   => 'laoshi@haoyou.test',
            'phone'   => '08123456789',
            'status'  => 'Active',
        ]);

        $this->siswaUser = User::create([
            'level_id' => $siswaLevel->id_level,
            'username' => 'siswa_user',
            'password' => bcrypt('password'),
        ]);
        Student::create([
            'user_id'   => $this->siswaUser->id,
            'name'      => 'Student Test',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);
    }

    /**
     * Test: Owner mengunggah dokumen untuk Guru saja, video untuk Kurikulum,
     * dan memeriksa validasi serta notifikasi yang tepat.
     */
    public function test_owner_upload_document_for_guru_and_video_for_kurikulum(): void
    {
        // 1. Upload Dokumen untuk Guru saja
        $docFile = UploadedFile::fake()->create('sop_guru.pdf', 500, 'application/pdf');

        $responseDoc = $this->actingAs($this->ownerUser)->post(route('owner.training.store'), [
            'title'        => 'SOP Pengajaran Guru',
            'category'     => 'SOP & Administrasi Pengajar',
            'target_roles' => ['Teacher'],
            'level'        => 'Dasar',
            'type'         => 'document',
            'version'      => 'v1.1',
            'description'  => 'Modul SOP khusus guru',
            'file'         => $docFile,
        ]);

        $responseDoc->assertRedirect(route('owner.training.index'));
        $this->assertDatabaseHas('training_materials', [
            'title'    => 'SOP Pengajaran Guru',
            'category' => 'SOP & Administrasi Pengajar',
            'type'     => 'document',
            'level'    => 'Dasar',
        ]);

        $matDoc = TrainingMaterial::where('title', 'SOP Pengajaran Guru')->first();
        $this->assertNotNull($matDoc);
        $this->assertDatabaseHas('training_material_roles', [
            'training_material_id' => $matDoc->id,
            'role'                 => 'Teacher',
        ]);
        $this->assertDatabaseMissing('training_material_roles', [
            'training_material_id' => $matDoc->id,
            'role'                 => 'Admin',
        ]);

        // Notifikasi SOP guru hanya ke Guru, bukan ke Kurikulum atau Admin
        Notification::assertSentTo(
            $this->guruUser,
            AppNotification::class,
            fn ($n) => str_contains($n->title, 'SOP Pengajaran Guru')
        );
        Notification::assertNotSentTo(
            [$this->kurikulumUser, $this->adminUser, $this->siswaUser],
            AppNotification::class,
            fn ($n) => str_contains($n->title, 'SOP Pengajaran Guru')
        );

        // 2. Upload Video untuk Kurikulum saja
        $videoFile = UploadedFile::fake()->create('tutorial_kurikulum.mp4', 2000, 'video/mp4');

        $responseVid = $this->actingAs($this->ownerUser)->post(route('owner.training.store'), [
            'title'        => 'Tutorial Penyusunan Silabus',
            'category'     => 'Standar Kurikulum & Silabus',
            'target_roles' => ['Curriculum'],
            'level'        => 'Menengah',
            'type'         => 'video',
            'version'      => 'v1.0',
            'video_file'   => $videoFile,
        ]);

        $responseVid->assertRedirect(route('owner.training.index'));
        $this->assertDatabaseHas('training_materials', [
            'title'    => 'Tutorial Penyusunan Silabus',
            'category' => 'Standar Kurikulum & Silabus',
            'type'     => 'video',
            'level'    => 'Menengah',
        ]);

        $matVid = TrainingMaterial::where('title', 'Tutorial Penyusunan Silabus')->first();
        $this->assertNotNull($matVid);
        $this->assertDatabaseHas('training_material_roles', [
            'training_material_id' => $matVid->id,
            'role'                 => 'Curriculum',
        ]);

        // Notifikasi Video kurikulum hanya ke Kurikulum
        Notification::assertSentTo(
            $this->kurikulumUser,
            AppNotification::class,
            fn ($n) => str_contains($n->title, 'Tutorial Penyusunan Silabus')
        );
        Notification::assertNotSentTo(
            [$this->guruUser, $this->adminUser, $this->siswaUser],
            AppNotification::class,
            fn ($n) => str_contains($n->title, 'Tutorial Penyusunan Silabus')
        );
    }

    /**
     * Test: Akses berdasarkan penerima di semua titik (Guru, Kurikulum, Admin, Siswa)
     */
    public function test_access_restriction_by_target_role_across_all_endpoints(): void
    {
        $dummyDoc = UploadedFile::fake()->create('doc_guru.pdf', 100, 'application/pdf');
        $docPath = $dummyDoc->store('training-materials/docs', 'public');

        $dummyVid = UploadedFile::fake()->create('vid_kur.mp4', 500, 'video/mp4');
        $vidPath = $dummyVid->store('training-materials/videos', 'public');

        // Materi 1: Khusus Guru
        $matGuru = TrainingMaterial::create([
            'title'       => 'Materi Rahasia Guru',
            'category'    => 'Pedagogik',
            'target_role' => 'Teacher',
            'level'       => 'Umum',
            'type'        => 'document',
            'version'     => 'v1.0',
            'file_path'   => $docPath,
            'uploaded_by' => $this->ownerUser->id,
        ]);
        TrainingMaterialRole::create(['training_material_id' => $matGuru->id, 'role' => 'Teacher']);

        // Materi 2: Khusus Kurikulum
        $matKurikulum = TrainingMaterial::create([
            'title'       => 'Materi Khusus Kurikulum',
            'category'    => 'Silabus',
            'target_role' => 'Curriculum',
            'level'       => 'Lanjutan',
            'type'        => 'video',
            'version'     => 'v1.0',
            'video_path'  => $vidPath,
            'uploaded_by' => $this->ownerUser->id,
        ]);
        TrainingMaterialRole::create(['training_material_id' => $matKurikulum->id, 'role' => 'Curriculum']);

        // A. Cek di sisi Guru:
        $respGuruIndex = $this->actingAs($this->guruUser)->get(route('teacher.training'));
        $respGuruIndex->assertOk();
        $respGuruIndex->assertSee('Materi Rahasia Guru');
        $respGuruIndex->assertDontSee('Materi Khusus Kurikulum');

        // Guru bisa download materi Guru
        $this->actingAs($this->guruUser)->get(route('teacher.training.download', $matGuru->id))->assertOk();

        // Guru TIDAK BISA download materi Kurikulum (403 Forbidden)
        $this->actingAs($this->guruUser)->get(route('teacher.training.download', $matKurikulum->id))->assertForbidden();
        $this->actingAs($this->guruUser)->get(route('teacher.training.stream', $matKurikulum->id))->assertForbidden();

        // B. Cek di sisi Kurikulum:
        $respKurIndex = $this->actingAs($this->kurikulumUser)->get(route('kurikulum.training'));
        $respKurIndex->assertOk();
        $respKurIndex->assertSee('Materi Khusus Kurikulum');
        $respKurIndex->assertDontSee('Materi Rahasia Guru');

        // Kurikulum bisa stream video miliknya
        $this->actingAs($this->kurikulumUser)->get(route('kurikulum.training.stream', $matKurikulum->id))->assertOk();

        // Kurikulum TIDAK BISA download materi Guru (403 Forbidden)
        $this->actingAs($this->kurikulumUser)->get(route('kurikulum.training.download', $matGuru->id))->assertForbidden();

        // C. Cek di sisi Admin:
        $respAdminIndex = $this->actingAs($this->adminUser)->get(route('admin.training.index'));
        $respAdminIndex->assertOk();
        $respAdminIndex->assertDontSee('Materi Rahasia Guru');
        $respAdminIndex->assertDontSee('Materi Khusus Kurikulum');

        // Admin akses materi Guru atau Kurikulum -> ditolak (403/404)
        $this->actingAs($this->adminUser)->get(route('admin.training.download', $matGuru->id))->assertForbidden();
        $this->actingAs($this->adminUser)->get(route('admin.training.download', $matKurikulum->id))->assertForbidden();

        // D. Cek di sisi Owner (Full Access):
        $respOwnerIndex = $this->actingAs($this->ownerUser)->get(route('owner.training.index'));
        $respOwnerIndex->assertOk();
        $respOwnerIndex->assertSee('Materi Rahasia Guru');
        $respOwnerIndex->assertSee('Materi Khusus Kurikulum');
        $this->actingAs($this->ownerUser)->get(route('owner.training.download', $matGuru->id))->assertOk();
        $this->actingAs($this->ownerUser)->get(route('owner.training.stream', $matKurikulum->id))->assertOk();

        // E. Cek di sisi Siswa: tidak boleh melihat materi training sama sekali
        $this->actingAs($this->siswaUser)->get('/guru/training')->assertForbidden();
        $this->actingAs($this->siswaUser)->get('/kurikulum/training')->assertForbidden();
        $this->actingAs($this->siswaUser)->get('/admin/training')->assertForbidden();
        $this->actingAs($this->siswaUser)->get('/owner/training')->assertForbidden();
    }

    /**
     * Test: Update materi training dan penggantian berkas menghapus file lama.
     */
    public function test_owner_update_material_and_sync_roles_and_cleanup_old_files(): void
    {
        $oldDoc = UploadedFile::fake()->create('old_doc.pdf', 100, 'application/pdf');
        $oldPath = $oldDoc->store('training-materials/docs', 'public');

        $mat = TrainingMaterial::create([
            'title'       => 'Materi Versi 1',
            'category'    => 'Pedagogik',
            'target_role' => 'Teacher',
            'level'       => 'Dasar',
            'type'        => 'document',
            'version'     => 'v1.0',
            'file_path'   => $oldPath,
            'uploaded_by' => $this->ownerUser->id,
        ]);
        TrainingMaterialRole::create(['training_material_id' => $mat->id, 'role' => 'Teacher']);

        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        $newDoc = UploadedFile::fake()->create('new_doc.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->ownerUser)->put(route('owner.training.update', $mat->id), [
            'title'        => 'Materi Versi 2 Diperbarui',
            'category'     => 'Pedagogik',
            'target_roles' => ['Teacher', 'Curriculum', 'Admin'],
            'level'        => 'Lanjutan',
            'type'         => 'document',
            'version'      => 'v2.0',
            'file'         => $newDoc,
        ]);

        $response->assertRedirect(route('owner.training.index'));

        // Cek file lama terhapus
        $this->assertFalse(Storage::disk('public')->exists($oldPath));

        // Cek role tersinkronisasi menjadi 3 peran
        $mat->refresh();
        $rolesList = $mat->target_roles_list;
        sort($rolesList);
        $this->assertEquals(['Admin', 'Curriculum', 'Teacher'], $rolesList);
        $this->assertCount(3, $mat->roles);

        // Hapus materi -> file baru juga harus terhapus
        $newPath = $mat->file_path;
        $this->assertTrue(Storage::disk('public')->exists($newPath));

        $this->actingAs($this->ownerUser)->delete(route('owner.training.destroy', $mat->id));
        $this->assertFalse(Storage::disk('public')->exists($newPath));
        $this->assertDatabaseMissing('training_materials', ['id' => $mat->id]);
        $this->assertDatabaseMissing('training_material_roles', ['training_material_id' => $mat->id]);
    }
}
