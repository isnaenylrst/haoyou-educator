<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_students', function (Blueprint $table) {
            // Catatan umum calon siswa
            $table->text('notes')->nullable()->after('lead_status');

            // Jadwal konsultasi (mengikuti pola trial_*)
            $table->enum('consultation_status', ['Pending', 'Completed', 'Cancelled'])
                ->nullable()
                ->after('trial_notes');
            $table->date('consultation_date')->nullable()->after('consultation_status');
            $table->text('consultation_notes')->nullable()->after('consultation_date');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_students', function (Blueprint $table) {
            $table->dropColumn([
                'notes',
                'consultation_status',
                'consultation_date',
                'consultation_notes',
            ]);
        });
    }
};