<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerSubmissionRecapTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $kurikulumUser;
    protected User $guruUser;
    protected Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        $ownerLevel = Level::create(['nama_level' => 'Owner']);
        $adminLevel = Level::create(['nama_level' => 'Admin']);
        $kurikulumLevel = Level::create(['nama_level' => 'Curriculum']);
        $guruLevel = Level::create(['nama_level' => 'Teacher']);

        $sigFile = UploadedFile::fake()->image('owner_sig.png');
        $sigPath = $sigFile->store('signatures', 'public');

        $this->ownerUser = User::create([
            'level_id'       => $ownerLevel->id_level,
            'username'       => 'owner_recap',
            'password'       => bcrypt('password'),
            'signature_path' => $sigPath,
        ]);

        $this->adminUser = User::create([
            'level_id' => $adminLevel->id_level,
            'username' => 'admin_recap',
            'password' => bcrypt('password'),
        ]);

        $this->kurikulumUser = User::create([
            'level_id'       => $kurikulumLevel->id_level,
            'username'       => 'kurikulum_recap',
            'password'       => bcrypt('password'),
            'signature_path' => $sigPath,
        ]);

        $this->guruUser = User::create([
            'level_id' => $guruLevel->id_level,
            'username' => 'guru_recap',
            'password' => bcrypt('password'),
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->guruUser->id,
            'name'    => 'Guru Pengaju',
            'email'   => 'guru@test.id',
            'phone'   => '08123456789',
            'status'  => 'Active',
        ]);
    }

    /**
     * Test format_rupiah helper function
     */
    public function test_format_rupiah_helper(): void
    {
        $this->assertEquals('Rp 25.000', format_rupiah(25000));
        $this->assertEquals('Rp 0', format_rupiah(0));
        $this->assertEquals('Rp 1.500.000', format_rupiah(1500000));
        $this->assertEquals('-Rp 25.000', format_rupiah(-25000));
    }

    /**
     * Test Admin membuka /admin/pengajuan: 200, layout Admin, ada 6 kartu keuangan,
     * menampilkan nominal uji dan saldo, tidak ada tombol approve Owner.
     */
    public function test_admin_opens_pengajuan_recap_with_6_financial_cards(): void
    {
        $this->actingAs($this->adminUser);

        // Data uji:
        // Buat program, category, level, package, student & enrollment agar relasi FK terpenuhi
        $prog = \App\Models\Program::create(['program_name' => 'Mandarin']);
        $cat = \App\Models\ProgramCategory::create(['program_id' => $prog->id, 'category_name' => 'Regular']);
        $level = \App\Models\ProgramLevel::create(['program_id' => $prog->id, 'category_id' => $cat->id, 'level_name' => 'L1']);
        $pkg = \App\Models\ProgramPackage::create([
            'program_id'       => $prog->id,
            'category_id'      => $cat->id,
            'level_id'         => $level->id,
            'package_name'     => 'Pkg 1',
            'duration_minutes' => 60,
            'total_meetings'   => 10,
            'min_students'     => 1,
            'max_students'     => 10,
            'price'            => 100000,
        ]);
        $student = \App\Models\Student::create([
            'user_id'   => $this->guruUser->id,
            'name'      => 'Siswa Test',
            'join_date' => now()->toDateString(),
            'status'    => 'Active',
        ]);
        $enrollment = \App\Models\ClassEnrollment::create([
            'student_id'         => $student->id,
            'program_package_id' => $pkg->id,
            'enrollment_date'    => now()->toDateString(),
            'status'             => 'Active',
        ]);

        // 1. Pembayaran siswa 100.000
        Payment::create([
            'enrollment_id'  => $enrollment->id,
            'invoice_number' => 'INV-001',
            'payment_stage'  => 'Full',
            'payment_method' => 'Transfer Bank',
            'amount_paid'    => 100000,
            'total_bill'     => 100000,
            'remaining_bill' => 0,
            'payment_date'   => now()->toDateString(),
            'status'         => 'Paid',
        ]);

        // 2. Pengajuan Dibayar 25.000
        Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Buku Bacaan',
            'amount'                 => 25000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Dibayar',
            'curriculum_approved_at' => now(),
        ]);

        // 3. Pengajuan Disetujui 10.000
        Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Pembelian Bahan Ajar',
            'item_name'              => 'Kertas Origami',
            'amount'                 => 10000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Disetujui',
            'curriculum_approved_at' => now(),
        ]);

        // 4. Pengajuan Diajukan (masih tahap Kurikulum) - tetap muncul di Admin
        Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Spidol Papan',
            'amount'                 => 15000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Diajukan',
        ]);

        $response = $this->get('/admin/pengajuan');

        $response->assertStatus(200);
        $response->assertSee('Rekap Pengajuan Bahan Ajar &amp; Reimburse', false);
        $response->assertSee('Keuangan');
        $response->assertSee('Periode:');
        $response->assertSee('Semua Periode');
        $response->assertSee('Angka ringkasan mengikuti filter yang aktif');

        // Nilai kartu
        $response->assertSee('Rp 100.000'); // Pemasukan
        $response->assertSee('Rp 25.000');  // Pengeluaran Dibayar
        $response->assertSee('Rp 10.000');  // Disetujui Belum Dibayar
        $response->assertSee('Rp 75.000');  // Saldo
        $response->assertSee('Rp 65.000');  // Saldo Setelah Komitmen

        // Pengajuan Diajukan Kurikulum tetap muncul
        $response->assertSee('Spidol Papan');

        // Admin tidak punya tombol/aksi approve Owner
        $response->assertDontSee('data-open-modal="modalOwnerApprove');
        $response->assertDontSee('/owner/pengajuan');
    }

    /**
     * Test Owner membuka /owner/pengajuan: 200, layout Owner, hanya 4 kartu (tanpa Pemasukan/Saldo),
     * hanya memuat pengajuan yang sudah ACC Kurikulum.
     */
    public function test_owner_opens_pengajuan_recap_with_4_approval_cards_and_curriculum_acc_only(): void
    {
        $this->actingAs($this->ownerUser);

        // Pengajuan yang belum ACC Kurikulum
        Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Barang Belum ACC',
            'amount'                 => 50000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Diajukan',
        ]);

        // Pengajuan yang sudah ACC Kurikulum
        Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Barang Sudah ACC',
            'amount'                 => 30000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Review Owner',
            'curriculum_approved_at' => now(),
        ]);

        $response = $this->get('/owner/pengajuan');

        $response->assertStatus(200);
        $response->assertSee('KEUANGAN &amp; MANAJEMEN', false);
        $response->assertSee('Pengaturan TTD Profil');
        $response->assertSee('Perlu Review Owner');
        $response->assertSee('Total Pengeluaran');

        // TIDAK ADA kartu Pemasukan atau Saldo di halaman Owner
        $response->assertDontSee('Total Pemasukan');
        $response->assertDontSee('Saldo Setelah Komitmen');

        // Hanya memuat yang sudah ACC
        $response->assertSee('Barang Sudah ACC');
        $response->assertDontSee('Barang Belum ACC');
    }

    /**
     * Test Admin memanggil route approve Owner ditolak.
     */
    public function test_admin_cannot_call_owner_approve_route(): void
    {
        $this->actingAs($this->adminUser);

        $submission = Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Flashcard',
            'amount'                 => 20000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Review Owner',
            'curriculum_approved_at' => now(),
        ]);

        $response = $this->post("/owner/pengajuan/{$submission->id}/approve", [
            'decision' => 'Approved',
        ]);

        // Ditolak (403 Forbidden via Gate/Middleware)
        $this->assertContains($response->getStatusCode(), [403, 302]);
        $this->assertNotEquals('Disetujui', $submission->fresh()->status);
    }

    /**
     * Test Owner mencoba approve pengajuan yang belum ACC Kurikulum lewat URL ditolak.
     */
    public function test_owner_cannot_approve_submission_without_curriculum_acc(): void
    {
        $this->actingAs($this->ownerUser);

        $submission = Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Buku Panduan',
            'amount'                 => 20000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Diajukan',
            'curriculum_approved_at' => null,
        ]);

        $response = $this->post("/owner/pengajuan/{$submission->id}/approve", [
            'decision' => 'Approved',
        ]);

        // Ditolak oleh Gate / Controller check
        $this->assertContains($response->getStatusCode(), [403, 302]);
        if ($response->getStatusCode() === 302) {
            $response->assertSessionHas('error');
        }
        $this->assertNotEquals('Disetujui', $submission->fresh()->status);
    }

    /**
     * Test Tolak tanpa catatan wajib gagal validasi.
     */
    public function test_owner_reject_without_note_fails(): void
    {
        $this->actingAs($this->ownerUser);

        $submission = Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Kertas Karton',
            'amount'                 => 20000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Review Owner',
            'curriculum_approved_at' => now(),
        ]);

        $response = $this->post("/owner/pengajuan/{$submission->id}/approve", [
            'decision' => 'Rejected',
            'note'     => '',
        ]);

        $response->assertSessionHasErrors('note');
        $this->assertNotEquals('Ditolak', $submission->fresh()->status);
    }

    /**
     * Test Owner Approve dan Mark Paid sukses dengan notifikasi.
     */
    public function test_owner_approve_and_mark_paid_succeeds_with_notifications(): void
    {
        $this->actingAs($this->ownerUser);

        $submission = Submission::create([
            'teacher_id'             => $this->teacher->id,
            'created_by'             => $this->guruUser->id,
            'type'                   => 'Reimburse Bahan Ajar',
            'item_name'              => 'Buku Cerita',
            'amount'                 => 35000,
            'date'                   => now()->toDateString(),
            'status'                 => 'Review Owner',
            'curriculum_approved_at' => now(),
        ]);

        // Approve
        $resApprove = $this->post("/owner/pengajuan/{$submission->id}/approve", [
            'decision' => 'Approved',
            'note'     => 'Disetujui oleh Owner',
        ]);
        $resApprove->assertSessionHas('success');
        $this->assertEquals('Disetujui', $submission->fresh()->status);

        // Mark Paid
        $resPaid = $this->post("/owner/pengajuan/{$submission->id}/mark-paid");
        $resPaid->assertSessionHas('success');
        $this->assertEquals('Dibayar', $submission->fresh()->status);

        // Notifikasi dikirim
        Notification::assertSentTo($this->guruUser, \App\Notifications\AppNotification::class);
    }
}
