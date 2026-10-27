<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE documents
            MODIFY document_type ENUM(
                'CV',
                'Photo',
                'Teacher Certificate',
                'Agreement',
                'SOP',
                'Teacher Leave Letter',
                'Other',
                'SURAT_LIBUR',
                'SURAT_DINAS',
                'LOA'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE documents
            MODIFY document_type ENUM(
                'CV',
                'Photo',
                'Teacher Certificate',
                'Agreement',
                'SOP',
                'Teacher Leave Letter',
                'Other'
            ) NOT NULL
        ");
    }
};