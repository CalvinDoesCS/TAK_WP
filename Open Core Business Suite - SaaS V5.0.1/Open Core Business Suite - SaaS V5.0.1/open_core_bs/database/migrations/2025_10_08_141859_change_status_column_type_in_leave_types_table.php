<?php

use App\Enums\Status;
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
        Schema::table('leave_types', function (Blueprint $table) {
            // Change status column from enum to string
            $table->string('status', 50)->default(Status::ACTIVE->value)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Revert status column back to enum
            $table->enum('status', [Status::ACTIVE->value, Status::INACTIVE->value])
                ->default(Status::ACTIVE->value)
                ->change();
        });
    }
};
