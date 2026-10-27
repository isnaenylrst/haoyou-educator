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
    Schema::table('programs', function (Blueprint $table) {   // SESUAIKAN kalau nama tabel berbeda
        $table->enum('audience', ['hsk', 'kids'])->default('hsk');
    });
}

public function down(): void
{
    Schema::table('programs', function (Blueprint $table) {
        $table->dropColumn('audience');
    });
}
};