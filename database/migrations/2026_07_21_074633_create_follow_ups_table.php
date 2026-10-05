<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {

            $table->id();

            $table->morphs('followupable'); // followupable_id, followupable_type

            $table->foreignId('follow_up_template_id')
                ->constrained('follow_up_templates')
                ->restrictOnDelete();

            $table->date('followup_date');

            $table->string('followup_method');

            $table->text('note')->nullable();

            $table->date('next_followup')->nullable();

            $table->enum('status',[
                'Pending',
                'Done'
            ])->default('Pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};