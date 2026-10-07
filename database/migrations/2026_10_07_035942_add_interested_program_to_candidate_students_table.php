<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_students', function (Blueprint $table) {
            $table->string('interested_program', 255)->nullable()->after('allergy');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_students', function (Blueprint $table) {
            $table->dropColumn('interested_program');
        });
    }
};