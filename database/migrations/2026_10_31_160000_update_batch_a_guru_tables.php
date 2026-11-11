<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah overall_achievement_level pada progress_reports
        if (Schema::hasTable('progress_reports')) {
            Schema::table('progress_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('progress_reports', 'overall_achievement_level')) {
                    $table->string('overall_achievement_level', 50)->nullable()->after('replacement_class_count');
                }
            });
        }

        // 2. Tambah is_late pada teaching_journals
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_journals', 'is_late')) {
                    $table->boolean('is_late')->default(false)->after('status');
                }
            });
        }

        // 3. Buat tabel schedule_journal_unlocks untuk pembukaan kunci jurnal terlambat oleh Kurikulum
        if (!Schema::hasTable('schedule_journal_unlocks')) {
            Schema::create('schedule_journal_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
                $table->date('session_date');
                $table->dateTime('unlocked_until');
                $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['class_schedule_id', 'session_date'], 'sched_unlock_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_journal_unlocks');

        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (Schema::hasColumn('teaching_journals', 'is_late')) {
                    $table->dropColumn('is_late');
                }
            });
        }

        if (Schema::hasTable('progress_reports')) {
            Schema::table('progress_reports', function (Blueprint $table) {
                if (Schema::hasColumn('progress_reports', 'overall_achievement_level')) {
                    $table->dropColumn('overall_achievement_level');
                }
            });
        }
    }
};

