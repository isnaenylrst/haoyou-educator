
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {

            $table->id();

            // Relasi program kategori
            $table->foreignId('program_id')
                ->nullable()
                ->constrained('program_categories')
                ->nullOnDelete();

            // Relasi level program
            $table->foreignId('level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // User yang mengunggah materi
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('audience', [
                'Guru',
                'Siswa',
                'Huahua'
            ])->default('Guru');

            $table->unsignedTinyInteger('meeting_number');

            // Judul materi
            $table->string('title');

            // Deskripsi dan silabus
            $table->text('syllabus')->nullable();

            $table->text('description')->nullable();

            // Konten materi
            $table->text('cco')->nullable();

            $table->text('lesson_plan')->nullable();

            $table->text('activity')->nullable();

            $table->string('box_code', 50)->nullable();

            $table->text('equipment_list')->nullable();

            // Lokasi file materi
            $table->string('material_file_path');

            $table->timestamps();

            // Mencegah duplikasi materi pada level, pertemuan, dan audience yang sama
            $table->unique(
                ['level_id', 'meeting_number', 'audience'],
                'materials_level_meeting_audience_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
