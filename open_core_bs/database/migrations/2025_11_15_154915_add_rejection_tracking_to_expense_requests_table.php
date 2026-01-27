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
        Schema::table('expense_requests', function (Blueprint $table) {
            $table->dateTime('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('rejected_by_id')->nullable()->after('approved_by_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_requests', function (Blueprint $table) {
            $table->dropForeign(['rejected_by_id']);
            $table->dropColumn(['rejected_at', 'rejected_by_id']);
        });
    }
};
