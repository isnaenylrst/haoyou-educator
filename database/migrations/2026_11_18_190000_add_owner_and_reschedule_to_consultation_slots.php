<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive migration:
     * - Menambahkan role_owner ('Curriculum' atau 'Owner') ke curriculum_consultation_slots
     * - Default 'Curriculum', nullable sehingga slot lama otomatis terbaca sebagai milik Kurikulum
     * - Menambahkan history penukaran jadwal konsultasi (reschedule_history) nullable
     */
    public function up(): void
    {
        if (Schema::hasTable('curriculum_consultation_slots')) {
            Schema::table('curriculum_consultation_slots', function (Blueprint $table) {
                if (!Schema::hasColumn('curriculum_consultation_slots', 'owner_role')) {
                    $table->enum('owner_role', ['Curriculum', 'Owner'])->nullable()->default('Curriculum')->after('curriculum_user_id');
                }
                if (!Schema::hasColumn('curriculum_consultation_slots', 'topic')) {
                    $table->string('topic')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('curriculum_consultation_slots', 'reschedule_history')) {
                    $table->json('reschedule_history')->nullable()->after('teacher_booking_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('curriculum_consultation_slots')) {
            Schema::table('curriculum_consultation_slots', function (Blueprint $table) {
                if (Schema::hasColumn('curriculum_consultation_slots', 'reschedule_history')) {
                    $table->dropColumn('reschedule_history');
                }
                if (Schema::hasColumn('curriculum_consultation_slots', 'topic')) {
                    $table->dropColumn('topic');
                }
                if (Schema::hasColumn('curriculum_consultation_slots', 'owner_role')) {
                    $table->dropColumn('owner_role');
                }
            });
        }
    }
};
