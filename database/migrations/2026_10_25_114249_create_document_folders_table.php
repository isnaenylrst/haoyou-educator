<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('icon')->default('fa-folder');
            // nilai yang disimpan di kolom documents.document_type
            $table->string('document_type')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('document_folders')->insert([
            ['slug' => 'cv',                   'label' => 'CV Karyawan',           'icon' => 'fa-id-card',      'document_type' => 'CV',                'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sertifikat-siswa',     'label' => 'Sertifikat Siswa',      'icon' => 'fa-award',        'document_type' => 'Certificate',       'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sop',                  'label' => 'SOP',                   'icon' => 'fa-list-check',   'document_type' => 'SOP',               'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'formulir-pendaftaran', 'label' => 'Formulir Pendaftaran',  'icon' => 'fa-file-pen',     'document_type' => 'Registration Form', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Folder baru butuh nilai document_type bebas. Jika kolom ini sebelumnya ENUM,
        // ubah jadi VARCHAR (MySQL). Aman dijalankan walau sudah VARCHAR.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY document_type VARCHAR(100) NOT NULL DEFAULT 'Other'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_folders');
    }
};