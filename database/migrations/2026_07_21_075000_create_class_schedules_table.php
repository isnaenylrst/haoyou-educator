
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('day');

            $table->time('start_time');
            $table->time('end_time');

            $table->string('room')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->index('effective_until');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
