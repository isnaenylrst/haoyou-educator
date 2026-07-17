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
        Schema::create('template_progress_reports', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->enum('category',[
                'Daily Regular',
                'HSK',
                'Private',
                'Bisnis',
                'Mandarin Tradisional / TOCFL'
            ]);

            $table->string('template_file');

            $table->text('description')->nullable();

            $table->enum('status',[
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();

            $table->softDeletes();

            $table->index('category');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_progress_reports');
    }
};