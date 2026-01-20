<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite indexes to improve Timeline View query performance.
     * These indexes target the most frequently queried columns in the
     * getDeviceLocationAjax, getActivityAjax, and getStatsForTimeLineAjax methods.
     */
    public function up(): void
    {
        // Index for device_status_logs: frequently filtered by user_id and date
        try {
            Schema::table('device_status_logs', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_device_logs_user_date');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        // Index for activities: frequently filtered by created_by_id and date
        try {
            Schema::table('activities', function (Blueprint $table) {
                $table->index(['created_by_id', 'created_at'], 'idx_activities_user_date');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        // Index for visits: frequently filtered by attendance_log_id
        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->index('attendance_log_id', 'idx_visits_attendance_log');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }

        // Index for field_orders: frequently filtered by attendance_log_id
        try {
            Schema::table('field_orders', function (Blueprint $table) {
                $table->index('attendance_log_id', 'idx_orders_attendance_log');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('device_status_logs', function (Blueprint $table) {
                $table->dropIndex('idx_device_logs_user_date');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropIndex('idx_activities_user_date');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropIndex('idx_visits_attendance_log');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }

        try {
            Schema::table('field_orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_attendance_log');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, skip
        }
    }
};
