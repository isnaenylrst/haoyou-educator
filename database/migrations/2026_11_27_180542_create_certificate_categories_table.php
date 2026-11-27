<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();      // disimpan di certificates.category
            $table->string('name', 100);               // nama di dropdown
            $table->string('sub', 150)->nullable();    // subjudul di PDF sertifikat
            $table->json('skills');                    // {"listening":"Listening","reading":"Reading"}
            $table->unsignedSmallInteger('passing');   // nilai lulus minimal
            $table->timestamps();
        });

        // Kategori bawaan (kode sama dengan yang sudah dipakai sertifikat sebelumnya)
        $now = now();
        $rows = [
            [
                'code' => 'hsk12', 'name' => 'HSK 1-2 (Listening + Reading)', 'sub' => 'MOCK TEST HSK 1-2',
                'skills' => ['listening' => 'Listening', 'reading' => 'Reading'], 'passing' => 120,
            ],
            [
                'code' => 'hsk36', 'name' => 'HSK 3-6 (Listening + Reading + Writing)', 'sub' => 'MOCK TEST HSK 3-6',
                'skills' => ['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing'], 'passing' => 180,
            ],
            [
                'code' => 'reg_rw', 'name' => 'Reguler (Membaca + Menulis)', 'sub' => 'MOCK TEST KELAS REGULER · MEMBACA & MENULIS',
                'skills' => ['reading' => 'Membaca', 'writing' => 'Menulis'], 'passing' => 120,
            ],
            [
                'code' => 'reg_ls', 'name' => 'Reguler (Mendengar + Berbicara)', 'sub' => 'MOCK TEST KELAS REGULER · MENDENGAR & BERBICARA',
                'skills' => ['listening' => 'Mendengar', 'speaking' => 'Berbicara'], 'passing' => 120,
            ],
        ];

        foreach ($rows as $r) {
            DB::table('certificate_categories')->insert([
                'code'       => $r['code'],
                'name'       => $r['name'],
                'sub'        => $r['sub'],
                'skills'     => json_encode($r['skills'], JSON_UNESCAPED_UNICODE),
                'passing'    => $r['passing'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_categories');
    }
};