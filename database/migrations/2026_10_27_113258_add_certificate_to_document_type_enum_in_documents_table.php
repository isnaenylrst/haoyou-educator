<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan 'Certificate' ke ENUM documents.document_type.
     * Dipakai oleh DocumentFactory::certificate() dan DocumentSeeder (sertifikat kelulusan siswa).
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
                'LOA',
                'Certificate'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Baris bertipe 'Certificate' dihapus dulu supaya ENUM bisa dikembalikan.
        DB::table('documents')->where('document_type', 'Certificate')->delete();

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
};