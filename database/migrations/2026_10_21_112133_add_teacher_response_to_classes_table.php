<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'teacher_response')) {
                $table->string('teacher_response', 20)->default('Pending'); // Pending | Accepted | Rejected
            }
            if (!Schema::hasColumn('classes', 'teacher_response_note')) {
                $table->text('teacher_response_note')->nullable();
            }
            if (!Schema::hasColumn('classes', 'teacher_responded_at')) {
                $table->timestamp('teacher_responded_at')->nullable();
            }
        });

        // Kelas yang sudah punya guru sebelum fitur ini ada dianggap sudah diterima,
        // supaya guru tidak tiba-tiba dibanjiri permintaan konfirmasi.
        DB::table('classes')
            ->whereNotNull('teacher_id')
            ->update(['teacher_response' => 'Accepted']);
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            foreach (['teacher_response', 'teacher_response_note', 'teacher_responded_at'] as $col) {
                if (Schema::hasColumn('classes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};