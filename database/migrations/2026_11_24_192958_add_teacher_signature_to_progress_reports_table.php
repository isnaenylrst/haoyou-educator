<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Progress Report: 3 tanda tangan berurutan (Guru -> Kurikulum -> Owner).
 * Kurikulum memakai kolom lama (curriculum_signature_path + reviewed_by/at),
 * Owner memakai kolom lama (owner_signature_path + owner_signed_by/at).
 * Migrasi ini hanya menambah TTD Guru yang "dibekukan" saat report dikirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('progress_reports', 'teacher_signature_path')) {
                $table->string('teacher_signature_path')->nullable()->after('curriculum_signature_path');
            }
            if (! Schema::hasColumn('progress_reports', 'teacher_signed_at')) {
                $table->timestamp('teacher_signed_at')->nullable()->after('teacher_signature_path');
            }
        });

        // Backfill (opsional): report yang sudah pernah dikirim memakai TTD profil guru saat ini.
        // Hanya dijalankan bila memang ada data & kolom sumbernya ada, jadi aman di database
        // kosong (mis. SQLite untuk tes) dan tidak pernah menggagalkan migrasi.
        if (
            Schema::hasColumn('users', 'signature_path')
            && Schema::hasColumn('teachers', 'user_id')
            && DB::table('progress_reports')->whereNotNull('submitted_at')->exists()
        ) {
            try {
                DB::table('progress_reports')
                    ->join('teachers', 'teachers.id', '=', 'progress_reports.teacher_id')
                    ->join('users', 'users.id', '=', 'teachers.user_id')
                    ->whereNotNull('progress_reports.submitted_at')
                    ->whereNull('progress_reports.teacher_signed_at')
                    ->whereNotNull('users.signature_path')
                    ->update([
                        'progress_reports.teacher_signature_path' => DB::raw('users.signature_path'),
                        'progress_reports.teacher_signed_at'      => DB::raw('progress_reports.submitted_at'),
                    ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            foreach (['teacher_signed_at', 'teacher_signature_path'] as $col) {
                if (Schema::hasColumn('progress_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
