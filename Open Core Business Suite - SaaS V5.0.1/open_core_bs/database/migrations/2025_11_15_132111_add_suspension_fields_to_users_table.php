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
        Schema::table('users', function (Blueprint $table) {
            $table->date('suspension_date')->nullable()->after('status');
            $table->string('suspension_reason', 1000)->nullable()->after('suspension_date');
            $table->integer('suspension_duration_days')->nullable()->after('suspension_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspension_date', 'suspension_reason', 'suspension_duration_days']);
        });
    }
};
