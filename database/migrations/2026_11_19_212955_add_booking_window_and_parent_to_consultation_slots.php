<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive migration:
     * - parent_slot_id: untuk sub-booking dari jendela waktu slot utama (foreignId nullable)
     * - booked_start_time: jam mulai spesifik yang dipesan guru (nullable)
     * - booked_end_time: jam selesai spesifik yang dipesan guru (nullable)
     * Slot lama tanpa kolom ini tetap valid dan dianggap memesan seluruh rentang start_time - end_time.
     */
    public function up(): void
    {
        if (Schema::hasTable('curriculum_consultation_slots')) {
            Schema::table('curriculum_consultation_slots', function (Blueprint $table) {
                if (!Schema::hasColumn('curriculum_consultation_slots', 'parent_slot_id')) {
                    $table->foreignId('parent_slot_id')->nullable()->after('curriculum_user_id')
                        ->constrained('curriculum_consultation_slots')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('curriculum_consultation_slots', 'booked_start_time')) {
                    $table->time('booked_start_time')->nullable()->after('end_time');
                }
                if (!Schema::hasColumn('curriculum_consultation_slots', 'booked_end_time')) {
                    $table->time('booked_end_time')->nullable()->after('booked_start_time');
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
                if (Schema::hasColumn('curriculum_consultation_slots', 'parent_slot_id')) {
                    $table->dropConstrainedForeignId('parent_slot_id');
                }
                if (Schema::hasColumn('curriculum_consultation_slots', 'booked_end_time')) {
                    $table->dropColumn('booked_end_time');
                }
                if (Schema::hasColumn('curriculum_consultation_slots', 'booked_start_time')) {
                    $table->dropColumn('booked_start_time');
                }
            });
        }
    }
};
