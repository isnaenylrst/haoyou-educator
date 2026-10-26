<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_leaves', function (Blueprint $t) {
            if (!Schema::hasColumn('teacher_leaves', 'request_type')) {
                $t->string('request_type', 20)->default('Cuti');   // Cuti | Ganti Kelas
            }
            if (!Schema::hasColumn('teacher_leaves', 'leave_date')) {
                $t->date('leave_date')->nullable();
            }
            if (!Schema::hasColumn('teacher_leaves', 'review_note')) {
                $t->text('review_note')->nullable();
            }
        });
    }

    public function down(): void {}
};