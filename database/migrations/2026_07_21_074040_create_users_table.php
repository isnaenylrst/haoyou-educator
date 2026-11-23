
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('level_id')
                ->constrained('levels', 'id_level')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('username')->unique();
            $table->string('name')->nullable();
            $table->string('password');

            $table->enum('status', [
                'Active',
                'Inactive'
            ])->default('Active');

            $table->string('foto')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        // Set default nama Owner menjadi "Joy Limmunandar"
        $ownerLevelId = DB::table('levels')
            ->where('nama_level', 'Owner')
            ->value('id_level');

        if ($ownerLevelId) {
            DB::table('users')
                ->where('level_id', $ownerLevelId)
                ->update([
                    'name' => 'Joy Limmunandar',
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
