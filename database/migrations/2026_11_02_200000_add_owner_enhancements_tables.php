<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi tabel training_materials
        if (Schema::hasTable('training_materials')) {
            Schema::table('training_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('training_materials', 'target_role')) {
                    $table->string('target_role', 50)->default('All')->after('category');
                }
                if (!Schema::hasColumn('training_materials', 'level')) {
                    $table->string('level', 50)->default('Umum')->after('target_role');
                }
                if (!Schema::hasColumn('training_materials', 'type')) {
                    $table->string('type', 20)->default('document')->after('level');
                }
                if (!Schema::hasColumn('training_materials', 'thumbnail_path')) {
                    $table->string('thumbnail_path', 255)->nullable()->after('type');
                }
                if (!Schema::hasColumn('training_materials', 'version')) {
                    $table->string('version', 20)->default('v1.0')->after('thumbnail_path');
                }
            });
        }

        // 2. Modifikasi tabel certificates
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (!Schema::hasColumn('certificates', 'curriculum_signature_path')) {
                    $table->string('curriculum_signature_path', 255)->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('certificates', 'owner_signature_path')) {
                    $table->string('owner_signature_path', 255)->nullable()->after('curriculum_signature_path');
                }
                if (!Schema::hasColumn('certificates', 'owner_signed_by')) {
                    $table->foreignId('owner_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('owner_signature_path');
                }
                if (!Schema::hasColumn('certificates', 'owner_signed_at')) {
                    $table->timestamp('owner_signed_at')->nullable()->after('owner_signed_by');
                }
            });
        }

        // 3. Modifikasi tabel progress_reports
        if (Schema::hasTable('progress_reports')) {
            Schema::table('progress_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('progress_reports', 'owner_signed_by')) {
                    $table->foreignId('owner_signed_by')->nullable()->constrained('users')->nullOnDelete()->after('owner_signature_path');
                }
                if (!Schema::hasColumn('progress_reports', 'owner_signed_at')) {
                    $table->timestamp('owner_signed_at')->nullable()->after('owner_signed_by');
                }
            });
        }

        // 4. Modifikasi tabel consultation_results
        if (Schema::hasTable('consultation_results')) {
            Schema::table('consultation_results', function (Blueprint $table) {
                if (!Schema::hasColumn('consultation_results', 'participant_names')) {
                    $table->string('participant_names', 255)->nullable()->after('teacher_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('training_materials')) {
            Schema::table('training_materials', function (Blueprint $table) {
                $table->dropColumn(['target_role', 'level', 'type', 'thumbnail_path', 'version']);
            });
        }

        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->dropForeign(['owner_signed_by']);
                $table->dropColumn(['curriculum_signature_path', 'owner_signature_path', 'owner_signed_by', 'owner_signed_at']);
            });
        }

        if (Schema::hasTable('progress_reports')) {
            Schema::table('progress_reports', function (Blueprint $table) {
                $table->dropForeign(['owner_signed_by']);
                $table->dropColumn(['owner_signed_by', 'owner_signed_at']);
            });
        }

        if (Schema::hasTable('consultation_results')) {
            Schema::table('consultation_results', function (Blueprint $table) {
                $table->dropColumn(['participant_names']);
            });
        }
    }
};

