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
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->index(); // Unique device identifier (UUID from device)
            $table->enum('device_type', ['android', 'ios', 'web', 'macos', 'windows', 'linux'])->default('android');
            $table->string('device_name')->nullable(); // e.g., "iPhone 14 Pro", "Samsung Galaxy S23"
            $table->text('fcm_token'); // Firebase Cloud Messaging token
            $table->string('app_version')->nullable(); // App version for debugging
            $table->boolean('is_active')->default(true); // Mark old tokens as inactive
            $table->timestamp('last_used_at')->nullable(); // Last successful push notification
            $table->timestamps();

            // Composite unique index: one token per device per user
            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
