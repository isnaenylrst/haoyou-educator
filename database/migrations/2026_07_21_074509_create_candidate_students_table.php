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

            $table->string('name');
            $table->enum('gender',['Male','Female']);
            $table->date('birth_date');

            $table->string('phone');
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();

            $table->text('address')->nullable();

            $table->string('school')->nullable();

            $table->string('source')->nullable();

            $table->text('allergy')->nullable();

            // Nullable: calon siswa tertarik ke program reguler (isi program_id)
            // ATAU program privat (isi private_package_id) — salah satu wajib
            // terisi, divalidasi di controller/service, bukan di database.
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

            $table->date('trial_date')->nullable();

            $table->enum('trial_status',[
                'Pending',
                'Completed',
                'Cancelled'
            ])->default('Pending');

            $table->text('trial_notes')->nullable();

            $table->enum('lead_status',[
                'Cold',
                'Warm',
                'Hot'
            ])->default('Cold');

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