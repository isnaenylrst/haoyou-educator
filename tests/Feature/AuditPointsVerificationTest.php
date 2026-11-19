<?php

namespace Tests\Feature;

use App\Models\CurriculumConsultationSlot;
use App\Models\Level;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ConsultationSlotGridService;
use App\Services\TeacherAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPointsVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru1;
    protected Teacher $teacher1;
    protected User $guru2;
    protected Teacher $teacher2;
    protected User $kurikulumUser;
    protected User $ownerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $tLevel = Level::create(['nama_level' => 'Teacher']);
        $cLevel = Level::create(['nama_level' => 'Curriculum']);
        $oLevel = Level::create(['nama_level' => 'Owner']);

        $this->guru1 = User::create([
            'username' => 'guru1',
            'name'     => 'Guru Satu',
            'email'    => 'guru1@test.com',
            'password' => bcrypt('password'),
            'level_id' => $tLevel->id_level,
            'status'   => 'Active',
        ]);
        $this->teacher1 = Teacher::create([
            'user_id' => $this->guru1->id,
            'name'    => 'Guru Satu',
            'phone'   => '081234567891',
            'status'  => 'Active',
        ]);

        $this->guru2 = User::create([
            'username' => 'guru2',
            'name'     => 'Guru Dua',
            'email'    => 'guru2@test.com',
            'password' => bcrypt('password'),
            'level_id' => $tLevel->id_level,
            'status'   => 'Active',
        ]);
        $this->teacher2 = Teacher::create([
            'user_id' => $this->guru2->id,
            'name'    => 'Guru Dua',
            'phone'   => '081234567892',
            'status'  => 'Active',
        ]);

        $this->kurikulumUser = User::create([
            'username' => 'kurikulum',
            'name'     => 'Kurikulum User',
            'email'    => 'kurikulum@test.com',
            'password' => bcrypt('password'),
            'level_id' => $cLevel->id_level,
            'status'   => 'Active',
        ]);

        $this->ownerUser = User::create([
            'username' => 'owner',
            'name'     => 'Owner User',
            'email'    => 'owner@test.com',
            'password' => bcrypt('password'),
            'level_id' => $oLevel->id_level,
            'status'   => 'Active',
        ]);
    }

    /**
     * POIN 1: Ketersediaan Guru 09:00 - 21:00
     */
    public function test_teacher_availability_grid_and_validation_09_to_21(): void
    {
        // 1. Grid konstan 09:00 - 21:00
        $grid = TeacherAvailabilityService::weeklyGrid(collect());
        $this->assertEquals(range(9, 20), $grid['hours']);

        // 2. Input di luar rentang ditolak (server validation Guru)
        $this->actingAs($this->guru1);
        $resBefore = $this->post(route('teacher.availability.store'), [
            'days'       => ['Senin'],
            'start_time' => '08:00',
            'end_time'   => '10:00',
        ]);
        $resBefore->assertSessionHasErrors('start_time');

        $resAfter = $this->post(route('teacher.availability.store'), [
            'days'       => ['Senin'],
            'start_time' => '19:00',
            'end_time'   => '22:00',
        ]);
        $resAfter->assertSessionHasErrors('end_time');

        // 3. Input valid 09:00-11:00 diterima
        $resValid = $this->post(route('teacher.availability.store'), [
            'days'       => ['Senin'],
            'start_time' => '09:00',
            'end_time'   => '11:00',
        ]);
        $resValid->assertSessionHasNoErrors();

        // 4. Input di sisi Kurikulum juga ditolak jika di luar 09:00-21:00
        $this->actingAs($this->kurikulumUser);
        $resKur = $this->post(route('kurikulum.ketersediaan.store'), [
            'teacher_id' => $this->teacher1->id,
            'days'       => ['Selasa'],
            'start_time' => '07:30',
            'end_time'   => '09:30',
        ]);
        $resKur->assertSessionHasErrors('start_time');
    }

    /**
     * POIN 4: Slot konsultasi bisa dipesan sebagian jam
     * Kasus: Owner/Kurikulum buat slot 10:00-12:00.
     * Guru 1 pesan 10:00-11:00, lalu Guru 2 pesan 11:00-12:00.
     * Overlap jam ditolak. Pemesanan di luar jendela ditolak.
     */
    public function test_consultation_slot_partial_booking_workflow(): void
    {
        // Owner buat slot 10:00 - 12:00 besok
        $tomorrow = Carbon::tomorrow()->toDateString();
        $slot = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->ownerUser->id,
            'owner_role'         => 'Owner',
            'date'               => $tomorrow,
            'start_time'         => '10:00:00',
            'end_time'           => '12:00:00',
            'status'             => 'Available',
        ]);

        // Cek sub-slots yang tersedia awal: 10:00-11:00 dan 11:00-12:00
        $subSlots = $slot->getAvailableSubSlots();
        $this->assertCount(2, $subSlots);
        $this->assertEquals('10:00', $subSlots[0]['start']);
        $this->assertEquals('11:00', $subSlots[0]['end']);
        $this->assertEquals('11:00', $subSlots[1]['start']);
        $this->assertEquals('12:00', $subSlots[1]['end']);

        // Guru 1 pesan sebagian: 10:00 - 11:00
        $this->actingAs($this->guru1);
        $book1 = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'      => 'single',
            'slot_id'           => $slot->id,
            'booked_start_time' => '10:00',
            'booked_end_time'   => '11:00',
            'topic'             => 'Konsultasi Evaluasi Guru 1',
        ]);
        $book1->assertSessionHasNoErrors();

        // Slot parent tetap Available karena masih ada sisa jam 11:00-12:00
        $slot->refresh();
        $this->assertEquals('Available', $slot->status);
        $this->assertEquals(60, $slot->available_minutes);
        $remainingSubs = $slot->getAvailableSubSlots();
        $this->assertCount(1, $remainingSubs);
        $this->assertEquals('11:00', $remainingSubs[0]['start']);
        $this->assertEquals('12:00', $remainingSubs[0]['end']);

        // Guru 2 mencoba overlap (misal 10:00-11:00 atau 10:30-11:30) -> ditolak
        $this->actingAs($this->guru2);
        $overlapBook = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'      => 'single',
            'slot_id'           => $slot->id,
            'booked_start_time' => '10:00',
            'booked_end_time'   => '11:00',
            'topic'             => 'Coba Rebutan Jam Yang Sama',
        ]);
        $overlapBook->assertSessionHas('error');

        // Guru 2 memesan di luar rentang jendela (09:00-10:00) -> ditolak
        $outOfWindowBook = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'      => 'single',
            'slot_id'           => $slot->id,
            'booked_start_time' => '09:00',
            'booked_end_time'   => '10:00',
            'topic'             => 'Di Luar Jendela',
        ]);
        $outOfWindowBook->assertSessionHas('error');

        // Guru 2 memesan sisa jam: 11:00 - 12:00 -> sukses
        $book2 = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'      => 'single',
            'slot_id'           => $slot->id,
            'booked_start_time' => '11:00',
            'booked_end_time'   => '12:00',
            'topic'             => 'Konsultasi Guru 2',
        ]);
        $book2->assertSessionHasNoErrors();

        // Sekarang seluruh jendela 10:00-12:00 sudah penuh
        $slot->refresh();
        $this->assertEquals('Booked', $slot->status);
        $this->assertEquals(0, $slot->available_minutes);
        $this->assertEmpty($slot->getAvailableSubSlots());

        // Cek grid mingguan
        $startOfWeek = Carbon::tomorrow()->startOfWeek(Carbon::MONDAY);
        $grid = ConsultationSlotGridService::buildWeeklyGrid($startOfWeek, collect([$slot]), $this->guru2->id, 'Teacher');
        $this->assertNotEmpty($grid['cells']);
    }

    /**
     * POIN 4b: Slot konsultasi Kurikulum juga mendukung partial booking & legacy slot penuh
     */
    public function test_curriculum_slot_and_legacy_slot_support(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        // 1. Slot legacy: dibuat tanpa parent_slot_id dan status 'Booked' langsung
        $legacySlot = CurriculumConsultationSlot::create([
            'curriculum_user_id'   => $this->kurikulumUser->id,
            'owner_role'           => 'Curriculum',
            'date'                 => $tomorrow,
            'start_time'           => '13:00:00',
            'end_time'             => '14:00:00',
            'status'               => 'Booked',
            'booked_by_teacher_id' => $this->teacher1->id,
            'topic'                => 'Legacy Booking',
        ]);
        $this->assertEquals(0, $legacySlot->available_minutes);
        $this->assertEmpty($legacySlot->getAvailableSubSlots());

        // 2. Slot Kurikulum 14:00-16:00
        $curSlot = CurriculumConsultationSlot::create([
            'curriculum_user_id' => $this->kurikulumUser->id,
            'owner_role'         => 'Curriculum',
            'date'               => $tomorrow,
            'start_time'         => '14:00:00',
            'end_time'           => '16:00:00',
            'status'             => 'Available',
        ]);
        $this->assertEquals(120, $curSlot->available_minutes);

        // Guru 1 pesan penuh sekaligus 14:00-16:00
        $this->actingAs($this->guru1);
        $bookFull = $this->post(route('teacher.jadwal-konsultasi.book'), [
            'booking_type'      => 'single',
            'slot_id'           => $curSlot->id,
            'booked_start_time' => '14:00',
            'booked_end_time'   => '16:00',
            'topic'             => 'Booking Seluruh Jendela',
        ]);
        $bookFull->assertSessionHasNoErrors();

        $curSlot->refresh();
        $this->assertEquals('Booked', $curSlot->status);
        $this->assertEquals(0, $curSlot->available_minutes);
    }
}
