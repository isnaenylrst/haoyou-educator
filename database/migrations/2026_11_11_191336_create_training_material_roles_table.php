<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('training_material_roles')) {
            Schema::create('training_material_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_material_id')
                    ->constrained('training_materials')
                    ->cascadeOnDelete();
                $table->string('role', 50); // Admin, Curriculum, Teacher
                $table->timestamps();

                $table->unique(['training_material_id', 'role']);
                $table->index('role');
            });
        }

        // Backfill data lama jika tabel training_materials ada
        if (Schema::hasTable('training_materials') && Schema::hasColumn('training_materials', 'target_role')) {
            $existing = DB::table('training_materials')->select('id', 'target_role')->get();
            $now = now();
            $inserts = [];

            foreach ($existing as $m) {
                $target = $m->target_role;
                $roles = match ($target) {
                    'Admin'                 => ['Admin'],
                    'Curriculum', 'Kurikulum' => ['Curriculum'],
                    'Teacher', 'Guru'       => ['Teacher'],
                    'All'                   => ['Admin', 'Curriculum', 'Teacher'],
                    default                 => ['Admin', 'Curriculum', 'Teacher'],
                };

                foreach ($roles as $r) {
                    $inserts[] = [
                        'training_material_id' => $m->id,
                        'role'                 => $r,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
            }

            if (!empty($inserts)) {
                DB::table('training_material_roles')->upsert(
                    $inserts,
                    ['training_material_id', 'role'],
                    ['updated_at']
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_material_roles');
    }
};
