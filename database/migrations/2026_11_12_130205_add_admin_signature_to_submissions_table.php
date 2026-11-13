<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'admin_signature_path')) {
                // TTD Admin digambar per pengajuan (bukan dari profil)
                $table->string('admin_signature_path')->nullable()->after('admin_verified_at');
            }
            if (! Schema::hasColumn('submissions', 'admin_signer_name')) {
                // Nama yang dicetak di bawah TTD Admin pada dokumen
                $table->string('admin_signer_name', 120)->nullable()->after('admin_signature_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['admin_signature_path', 'admin_signer_name']);
        });
    }
};