<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('classes', function (Blueprint $table) { // sesuaikan nama tabel kelas kamu
        $table->string('meeting_platform', 20)->default('zoom')->after('zoom_link');
    });
}

public function down(): void
{
    Schema::table('classes', function (Blueprint $table) {
        $table->dropColumn('meeting_platform');
    });
}
};