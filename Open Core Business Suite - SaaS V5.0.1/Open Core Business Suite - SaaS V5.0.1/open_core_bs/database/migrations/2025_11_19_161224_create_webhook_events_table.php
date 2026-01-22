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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50); // paypal, stripe, razorpay
            $table->string('event_id')->unique(); // Gateway's unique event ID for idempotency
            $table->string('event_type'); // PAYMENT.CAPTURE.COMPLETED, checkout.session.completed, etc.
            $table->json('payload'); // Full webhook payload
            $table->string('status', 20)->default('pending'); // pending, processed, failed
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['gateway', 'event_type']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
