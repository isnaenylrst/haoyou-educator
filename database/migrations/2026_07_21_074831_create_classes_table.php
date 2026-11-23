
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_package_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('private_package_id')
                ->nullable()
                ->constrained('private_packages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('class_name');

            $table->enum('delivery_mode', ['Offline', 'Online']);

            $table->string('zoom_link')->nullable();
            $table->string('zoom_passcode', 50)->nullable();
            $table->string('meeting_platform', 20)->default('zoom');

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->unsignedTinyInteger('capacity');

            $table->enum('status', [
                'Open',
                'Running',
                'Completed',
                'Closed'
            ])->default('Open');

            $table->string('teacher_response', 20)->default('Pending');
            $table->text('teacher_response_note')->nullable();
            $table->timestamp('teacher_responded_at')->nullable();

            $table->timestamps();
        });

        // Kelas yang sudah memiliki guru dianggap sudah diterima.
        DB::table('classes')
            ->whereNotNull('teacher_id')
            ->update([
                'teacher_response' => 'Accepted'
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
