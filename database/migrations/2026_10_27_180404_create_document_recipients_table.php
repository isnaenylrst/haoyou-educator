<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();   // kapan guru membuka surat
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        // Isi penerima untuk surat yang sudah terkirim sebelum fitur ini ada
        $letterTypes    = ['SURAT_LIBUR', 'SURAT_DINAS', 'LOA'];
        $teacherUserIds = DB::table('teachers')
            ->where('status', 'Active')
            ->whereNotNull('user_id')
            ->pluck('user_id');

        DB::table('documents')->whereIn('document_type', $letterTypes)->orderBy('id')->each(function ($doc) use ($teacherUserIds) {
            $ids = $doc->visibility === 'Teacher'
                ? $teacherUserIds
                : collect([$doc->user_id])->filter();

            $rows = $ids->unique()->map(fn ($uid) => [
                'document_id' => $doc->id,
                'user_id'     => $uid,
                'read_at'     => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ])->values()->all();

            if ($rows) {
                DB::table('document_recipients')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_recipients');
    }
};