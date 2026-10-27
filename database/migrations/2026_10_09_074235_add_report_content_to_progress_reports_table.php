<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $t) {
            if (!Schema::hasColumn('progress_reports', 'content'))            $t->json('content')->nullable();
            if (!Schema::hasColumn('progress_reports', 'attendance_present')) $t->unsignedSmallInteger('attendance_present')->default(0);
            if (!Schema::hasColumn('progress_reports', 'attendance_total'))   $t->unsignedSmallInteger('attendance_total')->default(0);
            if (!Schema::hasColumn('progress_reports', 'reviewed_by'))        $t->unsignedBigInteger('reviewed_by')->nullable();
            if (!Schema::hasColumn('progress_reports', 'reviewed_at'))        $t->timestamp('reviewed_at')->nullable();
            if (!Schema::hasColumn('progress_reports', 'created_at'))         $t->timestamps();
        });

        // file_path boleh kosong saat masih Draft
        Schema::table('progress_reports', function (Blueprint $t) {
            $t->string('file_path')->nullable()->change();
        });
    }

    public function down(): void {}
};