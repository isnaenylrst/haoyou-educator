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
        Schema::table('schedule_journal_unlocks', function (Blueprint $table) {
            $table->dateTime('unlocked_until')->nullable()->change();
            $table->string('status', 20)->default('Unlocked')->after('session_date'); // Pending, Unlocked, Rejected
            $table->text('request_reason')->nullable()->after('status');
            $table->dateTime('requested_at')->nullable()->after('request_reason');
            $table->foreignId('requested_by')->nullable()->after('requested_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_note')->nullable()->after('unlocked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_journal_unlocks', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['status', 'request_reason', 'requested_at', 'requested_by', 'rejection_note']);
        });
    }
};
