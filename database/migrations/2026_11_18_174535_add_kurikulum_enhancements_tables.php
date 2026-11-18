<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive migrations for Kurikulum enhancements:
     * 1. File paths for CCO, Lesson Plan, and Module in materials
     * 2. Material revision support in class_tasks
     * 3. Curriculum consultation availability slots
     */
    public function up(): void
    {
        // 1. KURIKULUM 1: File uploads for CCO, Lesson Plan, and Module in materials
        if (Schema::hasTable('materials')) {
            Schema::table('materials', function (Blueprint $table) {
                if (!Schema::hasColumn('materials', 'cco_file_path')) {
                    $table->string('cco_file_path')->nullable()->after('cco');
                    $table->string('cco_file_name')->nullable()->after('cco_file_path');
                }
                if (!Schema::hasColumn('materials', 'lesson_plan_file_path')) {
                    $table->string('lesson_plan_file_path')->nullable()->after('lesson_plan');
                    $table->string('lesson_plan_file_name')->nullable()->after('lesson_plan_file_path');
                }
                if (!Schema::hasColumn('materials', 'module_file_path')) {
                    $table->string('module_file_path')->nullable()->after('syllabus');
                    $table->string('module_file_name')->nullable()->after('module_file_path');
                }
            });
        }

        // 2. KURIKULUM 2: Material revision task support in class_tasks
        if (Schema::hasTable('class_tasks')) {
            Schema::table('class_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('class_tasks', 'task_type')) {
                    $table->string('task_type', 30)->default('class_task')->after('teacher_id');
                }
                if (!Schema::hasColumn('class_tasks', 'material_id')) {
                    $table->foreignId('material_id')->nullable()->after('task_type')
                        ->constrained('materials')->nullOnDelete();
                }
                if (!Schema::hasColumn('class_tasks', 'priority')) {
                    $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium')->after('status');
                }
            });
        }

        // 3. KURIKULUM 3: Slot Available for Curriculum Consultation
        if (!Schema::hasTable('curriculum_consultation_slots')) {
            Schema::create('curriculum_consultation_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curriculum_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->text('notes')->nullable();
                $table->enum('status', ['Available', 'Booked', 'Cancelled'])->default('Available');
                $table->foreignId('booked_by_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->text('booking_notes')->nullable();
                $table->timestamp('booked_at')->nullable();
                $table->enum('teacher_booking_status', ['Pending', 'Confirmed', 'Rejected'])->nullable();
                $table->timestamps();

                $table->index(['date', 'status'], 'curr_slot_date_status_idx');
                $table->index(['curriculum_user_id', 'date'], 'curr_slot_user_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_consultation_slots');

        if (Schema::hasTable('class_tasks')) {
            Schema::table('class_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('class_tasks', 'material_id')) {
                    $table->dropConstrainedForeignId('material_id');
                }
                $table->dropColumn(['task_type', 'priority']);
            });
        }

        if (Schema::hasTable('materials')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn([
                    'cco_file_path', 'cco_file_name',
                    'lesson_plan_file_path', 'lesson_plan_file_name',
                    'module_file_path', 'module_file_name',
                ]);
            });
        }
    }
};
