<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Signature on users (for Kurikulum, Owner, etc.)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('status');
            }
        });

        // 2. Video pembelajaran additions
        Schema::table('learning_videos', function (Blueprint $table) {
            if (!Schema::hasColumn('learning_videos', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('program_level_id')
                    ->constrained('program_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('learning_videos', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('youtube_id');
            }
            if (!Schema::hasColumn('learning_videos', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_published');
            }
        });

        // 3. Submissions table (Point 5: Reimburse & Pembelian)
        if (!Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->enum('type', ['Reimburse Bahan Ajar', 'Pembelian Bahan Ajar', 'Pembelian Sertifikat']);
                $table->string('item_name');
                $table->decimal('amount', 14, 2);
                $table->date('date');
                $table->text('description')->nullable();
                $table->string('receipt_path')->nullable();
                $table->enum('status', ['Diajukan', 'Review Kurikulum', 'Review Owner', 'Disetujui', 'Ditolak', 'Selesai'])->default('Diajukan');
                $table->foreignId('curriculum_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('curriculum_note')->nullable();
                $table->timestamp('curriculum_approved_at')->nullable();
                $table->string('curriculum_signature_path')->nullable();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('owner_note')->nullable();
                $table->timestamp('owner_approved_at')->nullable();
                $table->string('owner_signature_path')->nullable();
                $table->timestamps();
            });
        }

        // 4. Class Learning Materials template per class (Point 6)
        if (!Schema::hasTable('class_learning_materials')) {
            Schema::create('class_learning_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->string('material_name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 5. Progress Reports additions (Point 6: Suggestion Box, TTD, etc.)
        Schema::table('progress_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('progress_reports', 'suggestion_box')) {
                $table->text('suggestion_box')->nullable()->after('content');
            }
            if (!Schema::hasColumn('progress_reports', 'assessment_data')) {
                $table->json('assessment_data')->nullable()->after('suggestion_box');
            }
            if (!Schema::hasColumn('progress_reports', 'curriculum_signature_path')) {
                $table->string('curriculum_signature_path')->nullable()->after('assessment_data');
            }
            if (!Schema::hasColumn('progress_reports', 'owner_signature_path')) {
                $table->string('owner_signature_path')->nullable()->after('curriculum_signature_path');
            }
            if (!Schema::hasColumn('progress_reports', 'replacement_class_count')) {
                $table->unsignedSmallInteger('replacement_class_count')->default(0)->after('attendance_total');
            }
        });

        // 6. Letter types (Point 8)
        if (!Schema::hasTable('letter_types')) {
            Schema::create('letter_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Make documents.document_type a VARCHAR(50) so dynamic letter types work
        Schema::table('documents', function (Blueprint $table) {
            $table->string('document_type', 50)->change();
        });

        // 7. Teacher Observations (Point 9)
        if (!Schema::hasTable('teacher_observations')) {
            Schema::create('teacher_observations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('curriculum_user_id')->constrained('users')->cascadeOnDelete();
                $table->date('observation_date');
                $table->enum('sop_compliance', ['Sesuai SOP', 'Tidak Sesuai SOP', 'Perlu Perbaikan'])->default('Sesuai SOP');
                $table->text('observation_result');
                $table->text('feedback_solution');
                $table->timestamps();
            });
        }

        // 8. Student Learning Requests (Point 10)
        if (!Schema::hasTable('student_learning_requests')) {
            Schema::create('student_learning_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->string('topic');
                $table->text('consultation_notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 9. Classes broadcast for schedule acceptance (Point 11)
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'broadcast_to_all')) {
                $table->boolean('broadcast_to_all')->default(false)->after('teacher_id');
            }
        });

        // 10. Training Materials (Point 12)
        if (!Schema::hasTable('training_materials')) {
            Schema::create('training_materials', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category', 100);
                $table->text('description')->nullable();
                $table->string('file_path')->nullable();
                $table->string('video_url')->nullable();
                $table->string('video_path')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 11. Consultation Results (Point 13)
        if (!Schema::hasTable('consultation_results')) {
            Schema::create('consultation_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('curriculum_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('observation_id')->nullable()->constrained('teacher_observations')->nullOnDelete();
                $table->date('consultation_date');
                $table->string('topic');
                $table->json('improvement_points')->nullable(); // array of points
                $table->text('follow_up')->nullable();
                $table->string('file_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_results');
        Schema::dropIfExists('training_materials');
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'broadcast_to_all')) {
                $table->dropColumn('broadcast_to_all');
            }
        });
        Schema::dropIfExists('student_learning_requests');
        Schema::dropIfExists('teacher_observations');
        Schema::dropIfExists('letter_types');
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->dropColumn(['suggestion_box', 'assessment_data', 'curriculum_signature_path', 'owner_signature_path', 'replacement_class_count']);
        });
        Schema::dropIfExists('class_learning_materials');
        Schema::dropIfExists('submissions');
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['thumbnail_path', 'is_active']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};

