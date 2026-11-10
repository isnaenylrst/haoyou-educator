<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tujuan materi + deskripsi untuk siswa. Materi lama otomatis menjadi "Guru".
        Schema::table('materials', function (Blueprint $table) {
            $table->enum('audience', ['Guru', 'Siswa', 'Huahua'])->default('Guru')->after('level_id');
            $table->text('description')->nullable()->after('syllabus');
        });

        // 2) Unik per (level, pertemuan, tujuan).
        //    Index baru dibuat DULU supaya foreign key level_id tetap punya index.
        Schema::table('materials', function (Blueprint $table) {
            $table->unique(['level_id', 'meeting_number', 'audience'], 'materials_level_meeting_audience_unique');
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique('materials_level_id_meeting_number_unique');
        });

        // 3) Kata hasil impor Huahua tertaut ke materi sumbernya
        Schema::table('vocab_words', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->after('program_level_id')
                ->constrained('materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vocab_words', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
        });

        // Hanya materi Guru yang boleh tersisa agar unik lama bisa dikembalikan
        DB::table('materials')->where('audience', '!=', 'Guru')->delete();

        Schema::table('materials', function (Blueprint $table) {
            $table->unique(['level_id', 'meeting_number'], 'materials_level_id_meeting_number_unique');
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique('materials_level_meeting_audience_unique');
            $table->dropColumn(['audience', 'description']);
        });
    }
};