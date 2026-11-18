<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\ClassTask;
use App\Models\Curriculum;
use App\Models\CurriculumConsultationSlot;
use App\Models\Level;
use App\Models\Material;
use App\Models\Notification;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultationAndTaskEnhancementsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $kurikulumUser;
    protected User $ownerUser;
    protected User $guruUser1;
    protected User $guruUser2;
    protected Teacher $teacher1;
    protected Teacher $teacher2;
    protected ClassModel $class1;
    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Levels
        $kurLevel = Level::firstOrCreate(['nama_level' => 'Curriculum']);
        $ownerLevel = Level::firstOrCreate(['nama_level' => 'Owner']);
        $guruLevel = Level::firstOrCreate(['nama_level' => 'Teacher']);

        // 2. Kurikulum User
        $this->kurikulumUser = User::create([
            'level_id' => $kurLevel->id_level,
            'username' => 'kurikulum_suite_test',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        Curriculum::create([
            'user_id' => $this->kurikulumUser->id,
            'name'    => 'Petugas Kurikulum',
            'email'   => 'kurikulum_suite@haoyou.test',
            'phone'   => '08129999001',
        ]);

        // 3. Owner User
        $this->ownerUser = User::create([
            'level_id' => $ownerLevel->id_level,
            'username' => 'owner_suite_test',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);

        // 4. Guru Users & Teachers
        $this->guruUser1 = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_suite_1',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        $this->teacher1 = Teacher::create([
            'user_id'   => $this->guruUser1->id,
            'name'      => 'Guru Suite 1',
            'phone'     => '08129999002',
            'gender'    => 'Female',
            'status'    => 'Active',
            'join_date' => '2026-01-01',
        ]);

        $this->guruUser2 = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_suite_2',
            'password' => bcrypt('password'),
            'status'   => 'Active',
        ]);
        $this->teacher2 = Teacher::create([
            'user_id'   => $this->guruUser2->id,
            'name'      => 'Guru Suite 2',
            'phone'     => '08129999003',
            'gender'    => 'Male',
            'status'    => 'Active',
            'join_date' => '2026-01-01',
        ]);

        // 5. Kelas & Material
        $this->class1 = ClassModel::create([
            'class_name'    => 'Kelas Mandarin HSK 1',
            'teacher_id'    => $this->teacher1->id,
            'delivery_mode' => 'Online',
            'capacity'      => 10,
            'status'        => 'Running',
            'start_date'    => now()->toDateString(),
        ]);

        $this->material = Material::create([
            'title'              => 'Modul 1 Hanzi Dasar',
            'meeting_number'     => 1,
            'audience'           => 'Guru',
            'material_file_path' => 'materials/dummy.ppt',
            'uploaded_by'        => $this->kurikulumUser->id,
        ]);
    }

    /**
     * POIN A: Tugas Perbaikan Materi ke SATU atau BANYAK guru
     */
    public function test_tugas_perbaikan_materi_bisa_satu_atau_banyak_guru_dan_notifikasi(): void
    {
        $this->actingAs($this->kurikulumUser);

        // 1. Buat tugas untuk 2 guru sekaligus
        $response = $this->post(route('kurikulum.tugas-kelas.store'), [
            'task_type'   => 'material_revision',
            'teacher_ids' => [$this->teacher1->id, $this->teacher2->id],
            'material_id' => $this->material->id,
            'title'       => 'Revisi Latihan Mandiri Bab 1',
            'description' => 'Mohon sesuaikan kuis pinyin pada slide 10.',
            'priority'    => 'High',
            'due_date'    => now()->addDays(3)->toDateString(),
        ]);

        $response->assertRedirect(route('kurikulum.tugas-kelas'));

        // Pastikan masing-masing guru memiliki entri tugas mandiri
        $this->assertDatabaseHas('class_tasks', [
            'teacher_id'  => $this->teacher1->id,
            'material_id' => $this->material->id,
            'title'       => 'Revisi Latihan Mandiri Bab 1',
            'priority'    => 'High',
            'status'      => 'Open',
        ]);
        $this->assertDatabaseHas('class_tasks', [
            'teacher_id'  => $this->teacher2->id,
            'material_id' => $this->material->id,
            'title'       => 'Revisi Latihan Mandiri Bab 1',
            'priority'    => 'High',
            'status'      => 'Open',
        ]);

        // Notifikasi ke kedua guru terkirim
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->guruUser1->id,
            'type'          => 'App\Notifications\AppNotification',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->guruUser2->id,
            'type'          => 'App\Notifications\AppNotification',
        ]);

        // 2. Tugas untuk satu guru tetap bekerja
        $singleRes = $this->post(route('kurikulum.tugas-kelas.store'), [
            'task_type'   => 'material_revision',
            'teacher_ids' => [$this->teacher1->id],
            'material_id' => $this->material->id,
            'title'       => 'Tugas Khusus Guru 1',
            'description' => 'Instruksi khusus guru 1.',
            'priority'    => 'Medium',
        ]);
        $singleRes->assertRedirect(route('kurikulum.tugas-kelas'));
        $this->assertDatabaseHas('class_tasks', [
            'teacher_id' => $this->teacher1->id,
            'title'      => 'Tugas Khusus Guru 1',
        ]);
    }

    /**
     * POIN B & C: Slot Available Kurikulum & Owner, Hak Akses & Read-only Boundary
     */
    public function test_slot_available_kurikulum_dan_owner_isolation(): void
    {
        // 1. Kurikulum buat slot
        $this->actingAs($this->kurikulumUser);
        $resK = $this->post(route('kurikulum.jadwal-konsultasi.slots.store'), [
            'date'       => now()->addDays(5)->toDateString(),
            'start_time' => '09:00',
            'end_time'   => '10:00',
            'notes'      => 'Ruang Konsultasi Kurikulum',
        ]);
        $resK->assertRedirect();

        $curSlot = CurriculumConsultationSlot::where('curriculum_user_id', $this->kurikulumUser->id)->first();
        $this->assertNotNull($curSlot);
        $this->assertEquals('Curriculum', $curSlot->effective_role);

        // 2. Owner buat slot
        $this->actingAs($this->ownerUser);
        $resO = $this->post(route('owner.konsultasi.slots.store'), [
            'date'       => now()->addDays(5)->toDateString(),
            'start_time' => '14:00',
            'end_time'   => '15:00',
            'notes'      => 'Ruang Kerja Owner',
        ]);
        $resO->assertRedirect();

        $ownerSlot = CurriculumConsultationSlot::where('curriculum_user_id', $this->ownerUser->id)->first();
        $this->assertNotNull($ownerSlot);
        $this->assertEquals('Owner', $ownerSlot->effective_role);

        // 3. Kurikulum tidak bisa mengubah slot Owner (Read-Only)
        $this->actingAs($this->kurikulumUser);
        $resKurHack = $this->put(route('kurikulum.jadwal-konsultasi.slots.update', $ownerSlot), [
            'date'       => now()->addDays(5)->toDateString(),
            'start_time' => '15:00',
            'end_time'   => '16:00',
            'status'     => 'Available',
        ]);
        $resKurHack->assertSessionHas('error');
        $ownerSlot->refresh();
        $this->assertEquals('14:00', substr($ownerSlot->start_time, 0, 5));

        // Kurikulum tidak bisa menghapus slot Owner
        $resKurDel = $this->delete(route('kurikulum.jadwal-konsultasi.slots.destroy', $ownerSlot));
        $resKurDel->assertSessionHas('error');
        $this->assertDatabaseHas('curriculum_consultation_slots', ['id' => $ownerSlot->id]);

        // 4. Owner tidak bisa mengubah slot Kurikulum (Read-Only)
        $this->actingAs($this->ownerUser);
        $resOwnHack = $this->put(route('owner.konsultasi.slots.update', $curSlot), [
            'date'       => now()->addDays(5)->toDateString(),
            'start_time' => '08:00',
            'end_time'   => '09:00',
            'status'     => 'Available',
        ]);
        $resOwnHack->assertSessionHas('error');
        $curSlot->refresh();
        $this->assertEquals('09:00', substr($curSlot->start_time, 0, 5));

        // 5. Slot lama dengan owner_role null otomatis terbaca sebagai Kurikulum
        $legacySlot = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->kurikulumUser->id,
            'owner_role'         => null,
            'date'               => now()->addDays(6)->toDateString(),
            'start_time'         => '10:00:00',
            'end_time'           => '11:00:00',
            'status'             => 'Available',
        ]);
        $this->assertEquals('Curriculum', $legacySlot->effective_role);
    }

    /**
     * POIN C & D: Booking Guru, Pencegahan Double Booking, Slot Lewat, dan Reschedule (Ganti Hari/Jam)
     */
    public function test_booking_guru_pencegahan_double_booking_dan_reschedule(): void
    {
        // 1. Buat 2 slot available masa depan (satu Kurikulum, satu Owner)
        $slot1 = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->kurikulumUser->id,
            'owner_role'         => 'Curriculum',
            'date'               => now()->addDays(3)->toDateString(),
            'start_time'         => '09:00:00',
            'end_time'           => '10:00:00',
            'status'             => 'Available',
        ]);

        $slot2 = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->ownerUser->id,
            'owner_role'         => 'Owner',
            'date'               => now()->addDays(4)->toDateString(),
            'start_time'         => '13:00:00',
            'end_time'           => '14:00:00',
            'status'             => 'Available',
        ]);

        // Slot kemarin (sudah lewat)
        $pastSlot = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->kurikulumUser->id,
            'owner_role'         => 'Curriculum',
            'date'               => now()->subDay()->toDateString(),
            'start_time'         => '09:00:00',
            'end_time'           => '10:00:00',
            'status'             => 'Available',
        ]);

        // 2. Guru 1 memesan slot 1
        $this->actingAs($this->guruUser1);
        $resBook1 = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'slot_id'       => $slot1->id,
            'topic'         => 'Konsultasi Modul 1 Hanzi',
            'class_id'      => $this->class1->id,
            'booking_notes' => 'Catatan persiapan',
        ]);
        $resBook1->assertSessionHas('success');

        $slot1->refresh();
        $this->assertEquals('Booked', $slot1->status);
        $this->assertEquals($this->teacher1->id, $slot1->booked_by_teacher_id);
        $this->assertEquals('Pending', $slot1->teacher_booking_status);

        // 3. Guru 2 mencoba memesan slot 1 yang sudah dipesan -> double booking ditolak
        $this->actingAs($this->guruUser2);
        $resBookConflict = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'slot_id'  => $slot1->id,
            'topic'    => 'Guru 2 coba rebut slot',
        ]);
        $resBookConflict->assertSessionHas('error');

        // Guru 2 mencoba memesan slot yang sudah lewat -> ditolak
        $resPast = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'slot_id' => $pastSlot->id,
            'topic'   => 'Coba pesan slot kemarin',
        ]);
        $resPast->assertSessionHas('error');

        // 4. Guru 1 melakukan Ganti Hari/Jam (Reschedule) dari Slot 1 ke Slot 2 (Owner)
        $this->actingAs($this->guruUser1);
        $resReschedule = $this->post(route('teacher.jadwal-konsultasi.reschedule', $slot1), [
            'new_slot_id' => $slot2->id,
            'reason'      => 'Ada agenda mengajar mendadak pada slot lama.',
        ]);
        $resReschedule->assertSessionHas('success');

        $slot1->refresh();
        $slot2->refresh();

        // Slot 1 lama harus kembali 'Available'
        $this->assertEquals('Available', $slot1->status);
        $this->assertNull($slot1->booked_by_teacher_id);

        // Slot 2 baru harus menjadi 'Booked' oleh Guru 1
        $this->assertEquals('Booked', $slot2->status);
        $this->assertEquals($this->teacher1->id, $slot2->booked_by_teacher_id);
        $this->assertEquals('Pending', $slot2->teacher_booking_status);

        // Riwayat reschedule tercatat rapi
        $this->assertNotEmpty($slot2->reschedule_history);
        $this->assertEquals($slot1->id, $slot2->reschedule_history[0]['from_slot_id']);
        $this->assertEquals($slot2->id, $slot2->reschedule_history[0]['to_slot_id']);

        // 5. Owner melihat pengajuan dan mengonfirmasinya
        $this->actingAs($this->ownerUser);
        $resOwnerConfirm = $this->post(route('owner.konsultasi.slots.confirm', $slot2), [
            'action' => 'confirm',
            'notes'  => 'Disetujui di ruang Owner',
        ]);
        $resOwnerConfirm->assertRedirect();

        $slot2->refresh();
        $this->assertEquals('Confirmed', $slot2->teacher_booking_status);

        // Notifikasi konfirmasi sampai ke Guru 1
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->guruUser1->id,
            'type'          => 'App\Notifications\AppNotification',
        ]);
    }
}
