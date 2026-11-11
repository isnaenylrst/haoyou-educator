<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassModel;
use App\Models\Level;
use App\Models\Material;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruAUser;
    protected User $guruBUser;
    protected Teacher $teacherA;
    protected Teacher $teacherB;

    protected User $siswaHskUser;
    protected Student $studentHsk;

    protected User $siswaFeixiangUser;
    protected Student $studentFeixiang;

    protected ProgramCategory $catHsk;
    protected ProgramLevel $levelHsk1;
    protected ProgramLevel $levelHsk2;

    protected ProgramCategory $catFeixiang;
    protected ProgramLevel $levelFeixiang1;

    protected Material $matGuruHsk;
    protected Material $matGuruFeixiang;
    protected Material $matGuruUmum;

    protected Material $matSiswaHsk2;
    protected Material $matSiswaFeixiang1;
    protected Material $matSiswaUmum;

    protected Material $matHuahuaHsk2;
    protected Material $matHuahuaFeixiang1;
    protected Material $matHuahuaUmum;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Setup Levels
        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $siswaLevel = Level::create(['nama_level' => 'Student']);

        // Programs & Categories & ProgramLevels
        $prog = Program::create(['program_name' => 'Mandarin Program']);

        $this->catHsk = ProgramCategory::create([
            'program_id'    => $prog->id,
            'category_name' => 'HSK',
        ]);
        $this->levelHsk1 = ProgramLevel::create([
            'program_id'  => $prog->id,
            'category_id' => $this->catHsk->id,
            'level_name'  => 'HSK 1',
        ]);
        $this->levelHsk2 = ProgramLevel::create([
            'program_id'  => $prog->id,
            'category_id' => $this->catHsk->id,
            'level_name'  => 'HSK 2',
        ]);

        $this->catFeixiang = ProgramCategory::create([
            'program_id'    => $prog->id,
            'category_name' => 'Feixiang',
        ]);
        $this->levelFeixiang1 = ProgramLevel::create([
            'program_id'  => $prog->id,
            'category_id' => $this->catFeixiang->id,
            'level_name'  => 'Feixiang 1',
        ]);

        $pkgHsk2 = ProgramPackage::create([
            'program_id'       => $prog->id,
            'category_id'      => $this->catHsk->id,
            'level_id'         => $this->levelHsk2->id,
            'package_name'     => 'Paket HSK 2',
            'duration_minutes' => 60,
            'total_meetings'   => 12,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 500000,
            'is_active'        => true,
        ]);

        $pkgFeixiang1 = ProgramPackage::create([
            'program_id'       => $prog->id,
            'category_id'      => $this->catFeixiang->id,
            'level_id'         => $this->levelFeixiang1->id,
            'package_name'     => 'Paket Feixiang 1',
            'duration_minutes' => 60,
            'total_meetings'   => 12,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 500000,
            'is_active'        => true,
        ]);

        // Guru A mengajar HSK 2
        $this->guruAUser = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_a',
            'password' => bcrypt('password'),
        ]);
        $this->teacherA = Teacher::create([
            'user_id' => $this->guruAUser->id,
            'name'    => 'Guru A (HSK)',
            'email'   => 'guru_a@haoyou.test',
            'phone'   => '08123456701',
            'status'  => 'Active',
        ]);
        $classA = ClassModel::create([
            'class_name'         => 'Kelas HSK 2',
            'level_id'           => $this->levelHsk2->id,
            'program_package_id' => $pkgHsk2->id,
            'teacher_id'         => $this->teacherA->id,
            'delivery_mode'      => 'Online',
            'start_date'         => now()->toDateString(),
            'capacity'           => 10,
            'status'             => 'Running',
        ]);

        // Guru B mengajar Feixiang 1
        $this->guruBUser = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_b',
            'password' => bcrypt('password'),
        ]);
        $this->teacherB = Teacher::create([
            'user_id' => $this->guruBUser->id,
            'name'    => 'Guru B (Feixiang)',
            'email'   => 'guru_b@haoyou.test',
            'phone'   => '08123456702',
            'status'  => 'Active',
        ]);
        $classB = ClassModel::create([
            'class_name'         => 'Kelas Feixiang 1',
            'level_id'           => $this->levelFeixiang1->id,
            'program_package_id' => $pkgFeixiang1->id,
            'teacher_id'         => $this->teacherB->id,
            'delivery_mode'      => 'Online',
            'start_date'         => now()->toDateString(),
            'capacity'           => 10,
            'status'             => 'Running',
        ]);

        // Siswa HSK 2
        $this->siswaHskUser = User::create([
            'level_id' => $siswaLevel->id_level,
            'username' => 'siswa_hsk',
            'password' => bcrypt('password'),
        ]);
        $this->studentHsk = Student::create([
            'user_id'   => $this->siswaHskUser->id,
            'name'      => 'Siswa HSK',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);
        ClassEnrollment::create([
            'student_id'         => $this->studentHsk->id,
            'class_id'           => $classA->id,
            'program_package_id' => $pkgHsk2->id,
            'enrollment_date'    => now()->toDateString(),
            'status'             => 'Active',
        ]);

        // Siswa Feixiang 1
        $this->siswaFeixiangUser = User::create([
            'level_id' => $siswaLevel->id_level,
            'username' => 'siswa_feixiang',
            'password' => bcrypt('password'),
        ]);
        $this->studentFeixiang = Student::create([
            'user_id'   => $this->siswaFeixiangUser->id,
            'name'      => 'Siswa Feixiang',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);
        ClassEnrollment::create([
            'student_id'         => $this->studentFeixiang->id,
            'class_id'           => $classB->id,
            'program_package_id' => $pkgFeixiang1->id,
            'enrollment_date'    => now()->toDateString(),
            'status'             => 'Active',
        ]);

        // Buat file fake di public storage
        $dummyPdf = UploadedFile::fake()->create('dummy.pdf', 10, 'application/pdf');
        $pdfPath = $dummyPdf->store('materials', 'public');

        // Kurikulum user as uploader
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);
        $kurikulumUser = User::create([
            'level_id' => $kurikulumLevel->id_level,
            'username' => 'kurikulum_uploader',
            'password' => bcrypt('password'),
        ]);

        // Material Audience = Guru
        $this->matGuruHsk = Material::create([
            'title'              => 'Silabus Guru HSK',
            'audience'           => 'Guru',
            'program_id'         => $this->catHsk->id,
            'level_id'           => $this->levelHsk2->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matGuruFeixiang = Material::create([
            'title'              => 'Silabus Guru Feixiang',
            'audience'           => 'Guru',
            'program_id'         => $this->catFeixiang->id,
            'level_id'           => $this->levelFeixiang1->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matGuruUmum = Material::create([
            'title'              => 'Materi Guru Umum',
            'audience'           => 'Guru',
            'program_id'         => null,
            'level_id'           => null,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);

        // Material Audience = Siswa
        $this->matSiswaHsk2 = Material::create([
            'title'              => 'Materi Siswa HSK 2',
            'audience'           => 'Siswa',
            'program_id'         => $this->catHsk->id,
            'level_id'           => $this->levelHsk2->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matSiswaFeixiang1 = Material::create([
            'title'              => 'Materi Siswa Feixiang 1',
            'audience'           => 'Siswa',
            'program_id'         => $this->catFeixiang->id,
            'level_id'           => $this->levelFeixiang1->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matSiswaUmum = Material::create([
            'title'              => 'Materi Siswa Umum',
            'audience'           => 'Siswa',
            'program_id'         => null,
            'level_id'           => null,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);

        // Material Audience = Huahua
        $this->matHuahuaHsk2 = Material::create([
            'title'              => 'Materi Huahua HSK 2',
            'audience'           => 'Huahua',
            'program_id'         => $this->catHsk->id,
            'level_id'           => $this->levelHsk2->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matHuahuaFeixiang1 = Material::create([
            'title'              => 'Materi Huahua Feixiang 1',
            'audience'           => 'Huahua',
            'program_id'         => $this->catFeixiang->id,
            'level_id'           => $this->levelFeixiang1->id,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
        $this->matHuahuaUmum = Material::create([
            'title'              => 'Materi Huahua Umum',
            'audience'           => 'Huahua',
            'program_id'         => null,
            'level_id'           => null,
            'meeting_number'     => 1,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);
    }

    /**
     * Guru A (kelas HSK) dan Guru B (kelas Feixiang): keduanya melihat materi Guru dari HSK, Feixiang, dan Umum.
     */
    public function test_guru_a_and_guru_b_can_see_all_guru_materials(): void
    {
        // Guru A
        $responseA = $this->actingAs($this->guruAUser)->get(route('teacher.materi'));
        $responseA->assertOk();
        $responseA->assertSee('Silabus Guru HSK');
        $responseA->assertSee('Silabus Guru Feixiang');
        $responseA->assertSee('Materi Guru Umum');

        // Guru B
        $responseB = $this->actingAs($this->guruBUser)->get(route('teacher.materi'));
        $responseB->assertOk();
        $responseB->assertSee('Silabus Guru HSK');
        $responseB->assertSee('Silabus Guru Feixiang');
        $responseB->assertSee('Materi Guru Umum');
    }

    /**
     * Materi Siswa dan Huahua tidak muncul di daftar Guru, dan URL lihat/unduhnya 404 untuk Guru.
     */
    public function test_materials_for_siswa_and_huahua_not_in_guru_list_and_return_404_on_view_download(): void
    {
        $response = $this->actingAs($this->guruAUser)->get(route('teacher.materi'));
        $response->assertDontSee('Materi Siswa HSK 2');
        $response->assertDontSee('Materi Huahua HSK 2');

        // URL lihat & unduh materi Siswa oleh Guru -> 404
        $this->actingAs($this->guruAUser)->get(route('teacher.materi.show', $this->matSiswaHsk2))->assertNotFound();
        $this->actingAs($this->guruAUser)->get(route('teacher.materi.download', $this->matSiswaHsk2))->assertNotFound();

        // URL lihat & unduh materi Huahua oleh Guru -> 404
        $this->actingAs($this->guruAUser)->get(route('teacher.materi.show', $this->matHuahuaHsk2))->assertNotFound();
        $this->actingAs($this->guruAUser)->get(route('teacher.materi.download', $this->matHuahuaHsk2))->assertNotFound();
    }

    /**
     * Siswa di level HSK 2 hanya melihat materi Siswa untuk HSK 2 (bukan Feixiang, bukan level lain), dan URL materi lain ditolak.
     */
    public function test_siswa_hsk2_only_sees_hsk2_and_common_siswa_materials_and_other_is_rejected(): void
    {
        $response = $this->actingAs($this->siswaHskUser)->get(route('program.index'));
        $response->assertOk();
        $response->assertSee('Materi Siswa HSK 2');
        $response->assertSee('Materi Siswa Umum');
        $response->assertDontSee('Materi Siswa Feixiang 1');

        // Buka materi sendiri -> OK (200)
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.lihat', $this->matSiswaHsk2))->assertOk();
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.unduh', $this->matSiswaHsk2))->assertOk();

        // Buka materi Umum -> OK (200)
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.lihat', $this->matSiswaUmum))->assertOk();
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.unduh', $this->matSiswaUmum))->assertOk();

        // Buka materi Feixiang 1 -> 403 Forbidden
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.lihat', $this->matSiswaFeixiang1))->assertForbidden();
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.unduh', $this->matSiswaFeixiang1))->assertForbidden();
    }

    /**
     * Siswa Huahua hanya melihat materi Huahua yang sesuai program/level miliknya, dan URL lihat/unduh materi lain ditolak.
     */
    public function test_siswa_huahua_only_sees_matching_program_level_huahua_materials(): void
    {
        $response = $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi'));
        $response->assertOk();
        $response->assertSee('Materi Huahua HSK 2');
        $response->assertSee('Materi Huahua Umum');
        $response->assertDontSee('Materi Huahua Feixiang 1');

        // Akses materi Huahua HSK 2 -> OK (200)
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.lihat', $this->matHuahuaHsk2))->assertOk();
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.unduh', $this->matHuahuaHsk2))->assertOk();

        // Akses materi Huahua Umum -> OK (200)
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.lihat', $this->matHuahuaUmum))->assertOk();
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.unduh', $this->matHuahuaUmum))->assertOk();

        // Akses materi Huahua Feixiang 1 -> 403 Forbidden
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.lihat', $this->matHuahuaFeixiang1))->assertForbidden();
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.unduh', $this->matHuahuaFeixiang1))->assertForbidden();
    }

    /**
     * Materi Guru tidak terlihat Siswa dan Huahua.
     */
    public function test_guru_materials_are_not_visible_or_accessible_to_siswa_and_huahua(): void
    {
        // Di Program Belajar Siswa
        $responseSiswa = $this->actingAs($this->siswaHskUser)->get(route('program.index'));
        $responseSiswa->assertDontSee('Silabus Guru HSK');
        $responseSiswa->assertDontSee('Materi Guru Umum');

        // Di Materi Huahua
        $responseHuahua = $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi'));
        $responseHuahua->assertDontSee('Silabus Guru HSK');
        $responseHuahua->assertDontSee('Materi Guru Umum');

        // URL lihat/unduh materi Guru ditolak jika dicoba dibuka lewat route siswa/huahua (404)
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.lihat', $this->matGuruHsk))->assertNotFound();
        $this->actingAs($this->siswaHskUser)->get(route('program.materi.unduh', $this->matGuruHsk))->assertNotFound();
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.lihat', $this->matGuruHsk))->assertNotFound();
        $this->actingAs($this->siswaHskUser)->get(route('student.vocab.materi.unduh', $this->matGuruHsk))->assertNotFound();
    }

    /**
     * Notifikasi materi baru terkirim ke penerima yang benar saja.
     */
    public function test_new_material_notifications_are_sent_only_to_valid_recipients(): void
    {
        Notification::fake();

        // Buat mock controller untuk memanggil protected sendNewMaterialNotification
        $controller = new class extends \App\Http\Controllers\Kurikulum\MaterialController {
            public function testNotify(Material $m): void
            {
                $this->sendNewMaterialNotification($m);
            }
        };

        // 1. Notifikasi materi Guru: dikirim ke semua guru aktif ($guruAUser, $guruBUser), BUKAN ke siswa
        $dummyPdf = UploadedFile::fake()->create('dummy2.pdf', 10, 'application/pdf');
        $pdfPath = $dummyPdf->store('materials', 'public');

        $kurikulumUser = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->first();

        $newGuruMat = Material::create([
            'title'              => 'Materi Guru Baru',
            'audience'           => 'Guru',
            'program_id'         => $this->catHsk->id,
            'level_id'           => $this->levelHsk2->id,
            'meeting_number'     => 2,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);

        $controller->testNotify($newGuruMat);

        Notification::assertSentTo(
            [$this->guruAUser, $this->guruBUser],
            AppNotification::class,
            fn ($notification) => str_contains($notification->title, 'Materi Guru Baru')
        );
        Notification::assertNotSentTo(
            [$this->siswaHskUser, $this->siswaFeixiangUser],
            AppNotification::class,
            fn ($notification) => str_contains($notification->title, 'Materi Guru Baru')
        );

        // 2. Notifikasi materi Siswa HSK 2: dikirim ke siswa HSK ($siswaHskUser), BUKAN ke siswa Feixiang
        $newSiswaMat = Material::create([
            'title'              => 'Materi Siswa HSK Baru',
            'audience'           => 'Siswa',
            'program_id'         => $this->catHsk->id,
            'level_id'           => $this->levelHsk2->id,
            'meeting_number'     => 2,
            'material_file_path' => $pdfPath,
            'uploaded_by'        => $kurikulumUser->id,
        ]);

        $controller->testNotify($newSiswaMat);

        Notification::assertSentTo(
            $this->siswaHskUser,
            AppNotification::class,
            fn ($notification) => str_contains($notification->title, 'Materi Siswa HSK Baru')
        );
        Notification::assertNotSentTo(
            $this->siswaFeixiangUser,
            AppNotification::class,
            fn ($notification) => str_contains($notification->title, 'Materi Siswa HSK Baru')
        );
    }
}
