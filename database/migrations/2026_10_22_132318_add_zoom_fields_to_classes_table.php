<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('zoom_link')->nullable()->after('delivery_mode');
            $table->string('zoom_passcode', 50)->nullable()->after('zoom_link');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['zoom_link', 'zoom_passcode']);
        });
    }
};