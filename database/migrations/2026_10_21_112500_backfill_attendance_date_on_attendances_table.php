<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom attendance_date dipakai Monitoring Kurikulum, tetapi sebelumnya tidak
     * ikut tersimpan (tidak ada di $fillable) sehingga selalu NULL.
     * Isi data lama dari session_date supaya hitungan "hari ini" benar.
     */
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'attendance_date') && Schema::hasColumn('attendances', 'session_date')) {
            DB::table('attendances')
                ->whereNull('attendance_date')
                ->update(['attendance_date' => DB::raw('session_date')]);
        }
    }

    public function down(): void
    {
        // data tidak dikembalikan ke NULL
    }
};