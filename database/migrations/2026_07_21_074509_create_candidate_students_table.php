
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_students', function (Blueprint $table) {

            $table->id();

            // Identitas calon siswa
            $table->string('name');
            $table->enum('gender', ['Male', 'Female']);
            $table->date('birth_date');

            // Kontak
            $table->string('phone');
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();

            // Informasi tambahan
            $table->text('address')->nullable();
            $table->string('school')->nullable();
            $table->string('source')->nullable();
            $table->text('allergy')->nullable();

            // Program yang diminati
            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('private_package_id')
                ->nullable()
                ->constrained('private_packages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Jadwal dan status trial
            $table->date('trial_date')->nullable();

            $table->enum('trial_status', [
                'Pending',
                'Completed',
                'Cancelled'
            ])->default('Pending');

            // Status CRM
            $table->enum('lead_status', [
                'Cold',
                'Warm',
                'Hot'
            ])->default('Cold');

            // Konsultasi
            $table->enum('consultation_status', [
                'Pending',
                'Completed',
                'Cancelled'
            ])->nullable();

            $table->date('consultation_date')->nullable();
            $table->text('consultation_notes')->nullable();

            // Biaya pendaftaran
            $table->timestamp('registration_fee_paid_at')->nullable();
            $table->string('registration_fee_proof_path')->nullable();

            $table->enum('registration_fee_status', [
                'Pending',
                'Active',
                'Expired'
            ])->default('Pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_students');
    }
};
