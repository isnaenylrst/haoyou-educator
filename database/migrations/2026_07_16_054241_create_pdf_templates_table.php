<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {

            $table->id();

            $table->string('template_name');

            $table->enum('category',[
                'Agreement',
                'Invoice',
                'Certificate',
                'Report'
            ]);

            $table->string('file_name');

            $table->string('file_path');

            $table->string('version')
                ->default('1.0');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};