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
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'account_number')) {
                $table->string('account_number', 60)->nullable()->after('description');
            }
            if (!Schema::hasColumn('submissions', 'account_name')) {
                $table->string('account_name', 120)->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('submissions', 'shipping_cost')) {
                $table->decimal('shipping_cost', 14, 2)->default(0)->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('submissions', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('shipping_cost')->constrained('classes')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'class_id')) {
                $table->dropConstrainedForeignId('class_id');
            }
            if (Schema::hasColumn('submissions', 'shipping_cost')) {
                $table->dropColumn('shipping_cost');
            }
            if (Schema::hasColumn('submissions', 'account_name')) {
                $table->dropColumn('account_name');
            }
            if (Schema::hasColumn('submissions', 'account_number')) {
                $table->dropColumn('account_number');
            }
        });
    }
};
