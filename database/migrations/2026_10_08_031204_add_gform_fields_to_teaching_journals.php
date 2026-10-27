<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->boolean('is_last_meeting')->default(false)->after('is_substitute');
            $table->unsignedSmallInteger('meeting_number')->nullable()->after('is_last_meeting');
            $table->time('start_time')->nullable()->after('session_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('course_type', 20)->nullable();
            $table->string('course_level', 50)->nullable();
            $table->json('focus_materials')->nullable();
            $table->text('material_taught')->nullable();

            $table->unique(['class_schedule_id', 'session_date'], 'journal_schedule_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropUnique('journal_schedule_date_unique');
            $table->dropColumn([
                'is_last_meeting', 'meeting_number', 'start_time', 'end_time',
                'course_type', 'course_level', 'focus_materials', 'material_taught',
            ]);
        });
    }
};