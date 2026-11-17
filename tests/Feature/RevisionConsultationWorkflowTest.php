<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Level;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\RevisionConsultation;
use App\Models\RevisionConsultationMessage;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisionConsultationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruUser;
    protected User $kurikulumUser;
    protected Teacher $teacher;
    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $guruLevel = Level::create(['nama_level' => 'Teacher']);
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);

        $this->guruUser = User::create([
            'username' => 'guru_rev',
            'email'    => 'guru_rev@test.com',
            'password' => bcrypt('password'),
            'level_id' => $guruLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->kurikulumUser = User::create([
            'username' => 'kurikulum_rev',
            'email'    => 'kurikulum_rev@test.com',
            'password' => bcrypt('password'),
            'level_id' => $kurikulumLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Guru Penguji Revisi',
            'phone'   => '0811223344',
            'status'  => 'Active',
        ]);

        $program = Program::create(['program_name' => 'Mandarin Kids']);
        $pl = ProgramLevel::create(['program_id' => $program->id, 'level_name' => 'Level 1']);
        $package = ProgramPackage::create([
            'program_id'       => $program->id,
            'level_id'         => $pl->id,
            'package_name'     => 'Paket Kids',
            'duration_minutes' => 60,
            'total_meetings'   => 4,
            'min_students'     => 1,
            'max_students'     => 5,
            'price'            => 400000,
        ]);

        $this->class = ClassModel::create([
            'program_package_id' => $package->id,
            'teacher_id'         => $this->teacher->id,
            'class_name'         => 'Kelas Revisi Test',
            'delivery_mode'      => 'Online',
            'capacity'           => 5,
            'start_date'         => '2026-11-01',
            'end_date'           => '2026-11-30',
            'status'             => 'Running',
            'teacher_response'   => 'Accepted',
        ]);
    }

    public function test_full_revision_consultation_cycle_with_notifications_and_messages()
    {
        Notification::fake();

        // 1. Guru mengajukan konsultasi revisi dokumen
        $file = UploadedFile::fake()->create('dokumen_awal.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->guruUser)
            ->post(route('teacher.hasil-konsultasi.revisi.store'), [
                'class_id'      => $this->class->id,
                'document_type' => 'LP',
                'obstacle'      => 'Mohon review LP bagian aktivitas interaktif.',
                'attachment'    => $file,
            ]);

        $response->assertRedirect(route('teacher.hasil-konsultasi', ['tab' => 'revisi']));

        $ticket = RevisionConsultation::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('Menunggu', $ticket->status);
        $this->assertEquals($this->teacher->id, $ticket->teacher_id);

        Notification::assertSentTo($this->kurikulumUser, AppNotification::class);

        // 2. Kurikulum memutuskan: Perlu Revisi dengan catatan
        $response = $this->actingAs($this->kurikulumUser)
            ->patch(route('kurikulum.hasil-konsultasi.revisi.decide', $ticket->id), [
                'decision' => 'Perlu Revisi',
                'note'     => 'Tambahkan latihan pelafalan pinyin di menit 15.',
            ]);

        $response->assertSessionHas('success');
        $ticket->refresh();
        $this->assertEquals('Perlu Revisi', $ticket->status);
        $this->assertEquals('Tambahkan latihan pelafalan pinyin di menit 15.', $ticket->curriculum_reply);

        // Cek pesan riwayat tersimpan
        $this->assertDatabaseHas('revision_consultation_messages', [
            'revision_consultation_id' => $ticket->id,
            'sender_type'              => 'curriculum',
            'decision'                 => 'Perlu Revisi',
            'message'                  => 'Tambahkan latihan pelafalan pinyin di menit 15.',
        ]);

        Notification::assertSentTo($this->guruUser, AppNotification::class);

        // 3. Guru mengunggah ulang revisi pada tiket yang sama
        $reuploadFile = UploadedFile::fake()->create('dokumen_revisi_v2.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->guruUser)
            ->post(route('teacher.hasil-konsultasi.revisi.reupload', $ticket->id), [
                'notes'      => 'Sudah saya tambahkan latihan pinyin sesuai arahan.',
                'attachment' => $reuploadFile,
            ]);

        $response->assertRedirect(route('teacher.hasil-konsultasi', ['tab' => 'revisi']));
        $ticket->refresh();

        // Status kembali menjadi 'Menunggu' untuk Kurikulum review
        $this->assertEquals('Menunggu', $ticket->status);
        $this->assertEquals('dokumen_revisi_v2.pdf', $ticket->attachment_name);

        $this->assertDatabaseHas('revision_consultation_messages', [
            'revision_consultation_id' => $ticket->id,
            'sender_type'              => 'teacher',
            'decision'                 => 'Upload Ulang',
            'message'                  => 'Sudah saya tambahkan latihan pinyin sesuai arahan.',
            'attachment_name'          => 'dokumen_revisi_v2.pdf',
        ]);

        // 4. Kurikulum memutuskan: Terima (ACC)
        $response = $this->actingAs($this->kurikulumUser)
            ->patch(route('kurikulum.hasil-konsultasi.revisi.decide', $ticket->id), [
                'decision' => 'Terima',
                'note'     => 'Sudah sangat baik dan sesuai kurikulum, silakan digunakan.',
            ]);

        $response->assertSessionHas('success');
        $ticket->refresh();

        $this->assertEquals('Diterima', $ticket->status);
        $this->assertNotNull($ticket->completed_at);
        $this->assertEquals('Sudah sangat baik dan sesuai kurikulum, silakan digunakan.', $ticket->curriculum_reply);

        $this->assertDatabaseHas('revision_consultation_messages', [
            'revision_consultation_id' => $ticket->id,
            'sender_type'              => 'curriculum',
            'decision'                 => 'Terima',
            'message'                  => 'Sudah sangat baik dan sesuai kurikulum, silakan digunakan.',
        ]);

        // 5. Guru & Kurikulum melihat halaman hasil konsultasi tanpa error
        $resGuru = $this->actingAs($this->guruUser)
            ->get(route('teacher.hasil-konsultasi', ['tab' => 'revisi']));
        $resGuru->assertStatus(200);
        $resGuru->assertSee('Diterima');
        $resGuru->assertSee('Sudah sangat baik dan sesuai kurikulum');

        $resKurikulum = $this->actingAs($this->kurikulumUser)
            ->get(route('kurikulum.hasil-konsultasi.index', ['tab' => 'revisi']));
        $resKurikulum->assertStatus(200);
        $resKurikulum->assertSee('Diterima');
    }

    public function test_legacy_ticket_with_status_selesai_renders_correctly()
    {
        // Tiket lama sebelum migration baru: status 'Selesai', tidak punya records di messages
        $legacy = RevisionConsultation::create([
            'teacher_id'       => $this->teacher->id,
            'class_id'         => $this->class->id,
            'document_type'    => 'Progress Report',
            'obstacle'         => 'Kendala penulisan nilai karakter.',
            'status'           => 'Selesai',
            'curriculum_id'    => $this->kurikulumUser->id,
            'curriculum_reply' => 'Sudah dicek dan diselesaikan secara langsung.',
            'completed_at'     => now(),
        ]);

        // Cek halaman guru
        $responseGuru = $this->actingAs($this->guruUser)
            ->get(route('teacher.hasil-konsultasi', ['tab' => 'revisi']));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Selesai');
        $responseGuru->assertSee('Sudah dicek dan diselesaikan secara langsung.');

        // Cek halaman kurikulum
        $responseKur = $this->actingAs($this->kurikulumUser)
            ->get(route('kurikulum.hasil-konsultasi.index', ['tab' => 'revisi']));
        $responseKur->assertStatus(200);
        $responseKur->assertSee('Selesai');
        $responseKur->assertSee('Sudah dicek dan diselesaikan secara langsung.');

        // Test alur lama completeRevision tetap berfungsi
        $newPending = RevisionConsultation::create([
            'teacher_id'    => $this->teacher->id,
            'class_id'      => $this->class->id,
            'document_type' => 'PPT',
            'obstacle'      => 'PPT font error.',
            'status'        => 'Menunggu',
        ]);

        $resp = $this->actingAs($this->kurikulumUser)
            ->patch(route('kurikulum.hasil-konsultasi.revisi.complete', $newPending->id), [
                'curriculum_reply' => 'Selesai ditangani lewat tombol lama.',
            ]);
        $resp->assertSessionHas('success');

        $newPending->refresh();
        $this->assertEquals('Selesai', $newPending->status);
        $this->assertEquals('Selesai ditangani lewat tombol lama.', $newPending->curriculum_reply);
    }
}
