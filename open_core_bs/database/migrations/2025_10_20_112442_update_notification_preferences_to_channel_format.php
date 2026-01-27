<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds channel-specific columns to the notification_preferences table
     * to support FCM, Email, Database, and Broadcast channels.
     */
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Add notification type column to identify which notification this preference is for
            $table->string('notification_type')->nullable()->after('user_id');

            // Add channel-specific boolean columns
            $table->boolean('fcm_enabled')->default(true)->after('preferences');
            $table->boolean('mail_enabled')->default(true)->after('fcm_enabled');
            $table->boolean('database_enabled')->default(true)->after('mail_enabled');
            $table->boolean('broadcast_enabled')->default(false)->after('database_enabled');

            // Add unique constraint for user_id + notification_type combination
            // This allows one preference record per user per notification type
            $table->unique(['user_id', 'notification_type'], 'user_notification_type_unique');

            // Add index for faster lookups
            $table->index(['user_id', 'notification_type'], 'user_notification_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Drop indexes first
            $table->dropUnique('user_notification_type_unique');
            $table->dropIndex('user_notification_type_index');

            // Drop columns
            $table->dropColumn([
                'notification_type',
                'fcm_enabled',
                'mail_enabled',
                'database_enabled',
                'broadcast_enabled',
            ]);
        });

        echo "✅ Removed channel-specific columns from notification_preferences table.\n";
    }
};
