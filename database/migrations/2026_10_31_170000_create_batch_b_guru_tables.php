<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah class_id dan current_version ke teacher_materials
        if (Schema::hasTable('teacher_materials')) {
            Schema::table('teacher_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_materials', 'class_id')) {
                    $table->foreignId('class_id')->nullable()->after('teacher_id')->constrained('classes')->nullOnDelete();
                }
                if (!Schema::hasColumn('teacher_materials', 'current_version')) {
                    $table->unsignedInteger('current_version')->default(1)->after('status');
                }
            });
        }

        // 2. Buat tabel teacher_material_versions untuk riwayat revisi dokumen
        if (!Schema::hasTable('teacher_material_versions')) {
            Schema::create('teacher_material_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_material_id')->constrained('teacher_materials')->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                $table->string('lp_file_path')->nullable();
                $table->string('ppt_file_path')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 50)->default('Diajukan'); // Diajukan, Perlu Revisi, Disetujui
                $table->text('review_note')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Buat tabel revision_consultations untuk konsultasi revisi dokumen oleh guru
        if (!Schema::hasTable('revision_consultations')) {
            Schema::create('revision_consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('document_type', 50); // Materi, Progress Report, LP, PPT
                $table->text('obstacle'); // kendala yang dialami
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->string('status', 30)->default('Menunggu'); // Menunggu, Diterima, Selesai
                $table->foreignId('curriculum_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('curriculum_reply')->nullable();
                $table->dateTime('accepted_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_consultations');
        Schema::dropIfExists('teacher_material_versions');

        if (Schema::hasTable('teacher_materials')) {
            Schema::table('teacher_materials', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_materials', 'current_version')) {
                    $table->dropColumn('current_version');
                }
                if (Schema::hasColumn('teacher_materials', 'class_id')) {
                    $table->dropConstrainedForeignId('class_id');
                }
            });
        }
    }
};

