<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_leaves', function (Blueprint $table) {
            // NULL = tanpa guru pengganti | Pending | Accepted | Rejected
            if (!Schema::hasColumn('teacher_leaves', 'replacement_response')) {
                $table->string('replacement_response', 20)->nullable();
            }
            if (!Schema::hasColumn('teacher_leaves', 'replacement_response_note')) {
                $table->text('replacement_response_note')->nullable();
            }
            if (!Schema::hasColumn('teacher_leaves', 'replacement_responded_at')) {
                $table->timestamp('replacement_responded_at')->nullable();
            }
        });

        // Data lama: yang sudah disetujui dianggap pengganti sudah menerima,
        // sisanya menunggu jawaban pengganti.
        DB::table('teacher_leaves')
            ->whereNotNull('replacement_teacher_id')
            ->where('status', 'Approved')
            ->update(['replacement_response' => 'Accepted']);

        DB::table('teacher_leaves')
            ->whereNotNull('replacement_teacher_id')
            ->where('status', '!=', 'Approved')
            ->update(['replacement_response' => 'Pending']);
    }

    public function down(): void
    {
        Schema::table('teacher_leaves', function (Blueprint $table) {
            foreach (['replacement_response', 'replacement_response_note', 'replacement_responded_at'] as $col) {
                if (Schema::hasColumn('teacher_leaves', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};