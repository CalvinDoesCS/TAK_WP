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
        Schema::table('leave_requests', function (Blueprint $table) {
            // Track if comp off is used for this leave request
            $table->boolean('use_comp_off')->default(false)->after('total_days');

            // Track how many comp off days were used
            $table->decimal('comp_off_days_used', 4, 2)->default(0)->after('use_comp_off');

            // Track which comp off IDs were used (JSON array)
            $table->json('comp_off_ids')->nullable()->after('comp_off_days_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['use_comp_off', 'comp_off_days_used', 'comp_off_ids']);
        });
    }
};
