<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->text('student_names')->nullable();                 // nama murid (bisa diketik)
            $table->unsignedBigInteger('filled_by')->nullable();       // id guru yang mengisi form
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropColumn(['student_names', 'filled_by']);
        });
    }
};