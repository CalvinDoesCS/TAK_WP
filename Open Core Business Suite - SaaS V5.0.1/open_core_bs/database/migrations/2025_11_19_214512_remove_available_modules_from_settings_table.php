<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove available_modules and accessible_module_routes columns from settings table.
     * These are no longer needed as module access is now determined directly from
     * subscription plans and core module configuration.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('available_modules');
            $table->dropColumn('accessible_module_routes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('available_modules')->nullable();
            $table->json('accessible_module_routes')->nullable();
        });
    }
};
