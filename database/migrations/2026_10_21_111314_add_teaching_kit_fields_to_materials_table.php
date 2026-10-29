<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'cco')) {
                $table->text('cco')->nullable()->after('syllabus');
            }
            if (!Schema::hasColumn('materials', 'lesson_plan')) {
                $table->text('lesson_plan')->nullable()->after('cco');
            }
            if (!Schema::hasColumn('materials', 'activity')) {
                $table->text('activity')->nullable()->after('lesson_plan');
            }
            if (!Schema::hasColumn('materials', 'box_code')) {
                $table->string('box_code', 50)->nullable()->after('activity');
            }
            if (!Schema::hasColumn('materials', 'equipment_list')) {
                $table->text('equipment_list')->nullable()->after('box_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            foreach (['cco', 'lesson_plan', 'activity', 'box_code', 'equipment_list'] as $col) {
                if (Schema::hasColumn('materials', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};