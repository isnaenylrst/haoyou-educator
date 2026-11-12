<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Perluas tabel submissions untuk multi-item, foto bahan ajar, dan verifikasi admin
        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'items')) {
                    $table->json('items')->nullable()->after('item_name');
                }
                if (!Schema::hasColumn('submissions', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('receipt_path');
                }
                if (!Schema::hasColumn('submissions', 'admin_id')) {
                    $table->foreignId('admin_id')->nullable()->after('curriculum_signature_path')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('submissions', 'admin_verified_at')) {
                    $table->dateTime('admin_verified_at')->nullable()->after('admin_id');
                }
                if (!Schema::hasColumn('submissions', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('admin_verified_at');
                }
            });
        }

        // 2. Tambah curriculum_response pada teacher_leaves untuk persetujuan paralel
        if (Schema::hasTable('teacher_leaves')) {
            Schema::table('teacher_leaves', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_leaves', 'curriculum_response')) {
                    $table->string('curriculum_response', 30)->nullable()->default('Pending')->after('replacement_responded_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_leaves')) {
            Schema::table('teacher_leaves', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_leaves', 'curriculum_response')) {
                    $table->dropColumn('curriculum_response');
                }
            });
        }

        if (Schema::hasTable('submissions')) {
            Schema::table('submissions', function (Blueprint $table) {
                if (Schema::hasColumn('submissions', 'admin_note')) {
                    $table->dropColumn('admin_note');
                }
                if (Schema::hasColumn('submissions', 'admin_verified_at')) {
                    $table->dropColumn('admin_verified_at');
                }
                if (Schema::hasColumn('submissions', 'admin_id')) {
                    $table->dropConstrainedForeignId('admin_id');
                }
                if (Schema::hasColumn('submissions', 'photo_path')) {
                    $table->dropColumn('photo_path');
                }
                if (Schema::hasColumn('submissions', 'items')) {
                    $table->dropColumn('items');
                }
            });
        }
    }
};

