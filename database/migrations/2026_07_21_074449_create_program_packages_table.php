<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_packages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('program_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('level_id')
                ->nullable()
                ->constrained('program_levels')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('package_name');

            $table->integer('duration_minutes');
            $table->unsignedSmallInteger('duration_months')->default(3);

            $table->integer('total_meetings');
            $table->integer('min_students');
            $table->integer('max_students');

            $table->decimal('price', 12, 2);
            $table->decimal('installment_price', 12, 2)->nullable();
            $table->unsignedTinyInteger('installment_count')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['program_id', 'package_name']);
        });

        // Data lama: 1 termin per bulan.
        // Jumlah termin mengikuti durasi paket.
        DB::table('program_packages')
            ->whereNotNull('installment_price')
            ->where('installment_price', '>', 0)
            ->update([
                'installment_count' => DB::raw('duration_months'),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('program_packages');
    }
};