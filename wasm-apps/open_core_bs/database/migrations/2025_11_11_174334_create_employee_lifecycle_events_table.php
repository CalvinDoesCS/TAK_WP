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
        Schema::create('employee_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('event_type'); // joined, probation_started, probation_confirmed, etc.
            $table->dateTime('event_date'); // When the event actually happened
            $table->foreignId('triggered_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('old_value')->nullable(); // Previous state (JSON)
            $table->json('new_value')->nullable(); // New state (JSON)
            $table->json('metadata')->nullable(); // Event-specific data (reasons, remarks, approvals, etc.)
            $table->boolean('notification_sent')->default(false);
            $table->dateTime('notification_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->index('event_type');
            $table->index('event_date');
            $table->index(['user_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_events');
    }
};
