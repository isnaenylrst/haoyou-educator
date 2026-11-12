<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('progress_report_templates')) {
            Schema::create('progress_report_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('program_category_id')->nullable(); // Feixiang / Maochong / HSK ...
                $table->unsignedBigInteger('program_level_id')->nullable();    // opsional: khusus satu level
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index('program_category_id');
                $table->index('program_level_id');
            });
        }

        if (!Schema::hasTable('progress_report_template_meetings')) {
            Schema::create('progress_report_template_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('progress_report_templates')->cascadeOnDelete();
                $table->unsignedSmallInteger('meeting_number');
                $table->text('materials');
                $table->timestamps();

                $table->unique(['template_id', 'meeting_number'], 'prt_meetings_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_report_template_meetings');
        Schema::dropIfExists('progress_report_templates');
    }
};