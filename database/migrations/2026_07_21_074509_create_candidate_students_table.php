<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_students', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->enum('gender',['Male','Female']);
            $table->date('birth_date')->nullable();

            $table->string('phone');
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();

            $table->text('address')->nullable();

            $table->string('school')->nullable();

            $table->string('source')->nullable();

            $table->text('allergy')->nullable();

            $table->string('interested_program');

            $table->date('trial_date')->nullable();

            $table->enum('trial_status',[
                'Pending',
                'Completed',
                'Cancelled'
            ])->default('Pending');

            $table->enum('lead_status',[
                'Cold',
                'Warm',
                'Hot'
            ])->default('Cold');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_students');
    }
};