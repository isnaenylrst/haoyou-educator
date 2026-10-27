<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_packages', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')
                ->default(3)
                ->after('duration_minutes');
        });

        Schema::table('private_packages', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')
                ->default(1)
                ->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('program_packages', function (Blueprint $table) {
            $table->dropColumn('duration_months');
        });

        Schema::table('private_packages', function (Blueprint $table) {
            $table->dropColumn('duration_months');
        });
    }
};