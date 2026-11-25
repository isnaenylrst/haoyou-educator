<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('class_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('program_package_id')
                ->nullable()
                ->constrained('program_packages')
                ->nullOnDelete();

            $table->foreignId('private_package_id')
                ->nullable()
                ->constrained('private_packages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('enrollment_date');

            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->decimal('activity_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('requested_material')->nullable();

            $table->enum('payment_scheme', [
                'Lunas',
                'Termin'
            ])->default('Lunas');

            $table->unsignedTinyInteger('termin_count')->default(3);
            $table->json('form_manual')->nullable();

            $table->enum('status', [
                'Waiting Class',
                'Active',
                'Completed',
                'Cancelled'
            ])->default('Active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
    }
};