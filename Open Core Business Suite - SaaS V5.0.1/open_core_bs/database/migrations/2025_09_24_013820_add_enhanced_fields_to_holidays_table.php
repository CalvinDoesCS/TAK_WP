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
        Schema::table('holidays', function (Blueprint $table) {
            // Drop the existing status column if it exists
            if (Schema::hasColumn('holidays', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('holidays', function (Blueprint $table) {
            // Basic fields
            $table->year('year')->index()->after('code'); // Year for easy filtering
            $table->string('day', 20)->nullable()->after('year'); // Day of week (Monday, Tuesday, etc.)

            // Holiday type and categorization
            $table->enum('type', ['public', 'religious', 'regional', 'optional', 'company', 'special'])->default('public')->after('day');
            $table->enum('category', ['national', 'state', 'cultural', 'festival', 'company_event', 'other'])->nullable()->after('type');
            $table->boolean('is_optional')->default(false)->after('category'); // Optional holiday that employees can choose
            $table->boolean('is_restricted')->default(false)->after('is_optional'); // Restricted holiday (floater)
            $table->boolean('is_recurring')->default(false)->after('is_restricted'); // Recurring every year

            // Applicability settings
            $table->enum('applicable_for', ['all', 'department', 'location', 'employee_type', 'custom'])->default('all')->after('is_recurring');
            $table->json('departments')->nullable()->after('applicable_for'); // Array of department IDs
            $table->json('locations')->nullable()->after('departments'); // Array of location names/IDs
            $table->json('employee_types')->nullable()->after('locations'); // Array of employee types (permanent, contract, etc.)
            $table->json('branches')->nullable()->after('employee_types'); // Array of branch IDs
            $table->json('specific_employees')->nullable()->after('branches'); // Array of specific employee IDs

            // Additional details
            $table->text('description')->nullable()->after('specific_employees'); // Detailed description
            // notes already exists, skipping
            $table->string('image')->nullable()->after('notes'); // Holiday image/banner
            $table->string('color', 7)->nullable()->after('image'); // Color code for calendar display
            $table->integer('sort_order')->default(0)->after('color'); // Display order

            // Working day compensation
            $table->boolean('is_compensatory')->default(false)->after('sort_order'); // If true, employees get comp-off
            $table->date('compensatory_date')->nullable()->after('is_compensatory'); // Alternative working day if holiday falls on weekend

            // Half day settings
            $table->boolean('is_half_day')->default(false)->after('compensatory_date'); // Half day holiday
            $table->enum('half_day_type', ['morning', 'afternoon'])->nullable()->after('is_half_day'); // Which half
            $table->time('half_day_start_time')->nullable()->after('half_day_type'); // Start time for half day
            $table->time('half_day_end_time')->nullable()->after('half_day_start_time'); // End time for half day

            // Status and visibility
            $table->boolean('is_active')->default(true)->after('half_day_end_time');
            $table->boolean('is_visible_to_employees')->default(true)->after('is_active'); // Show in employee calendar

            // Notification settings
            $table->boolean('send_notification')->default(true)->after('is_visible_to_employees'); // Send notification to employees
            $table->integer('notification_days_before')->default(7)->after('send_notification'); // Days before to send notification

            // Metadata
            $table->foreignId('approved_by_id')->nullable()->after('notification_days_before')->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');

            // Additional indexes for performance
            $table->index(['date', 'year']);
            $table->index(['type', 'is_active']);
            $table->index('applicable_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['date', 'year']);
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['applicable_for']);
            $table->dropIndex(['year']);

            // Drop foreign key
            $table->dropForeign(['approved_by_id']);

            // Drop all added columns
            $table->dropColumn([
                'year',
                'day',
                'type',
                'category',
                'is_optional',
                'is_restricted',
                'is_recurring',
                'applicable_for',
                'departments',
                'locations',
                'employee_types',
                'branches',
                'specific_employees',
                'description',
                'image',
                'color',
                'sort_order',
                'is_compensatory',
                'compensatory_date',
                'is_half_day',
                'half_day_type',
                'half_day_start_time',
                'half_day_end_time',
                'is_active',
                'is_visible_to_employees',
                'send_notification',
                'notification_days_before',
                'approved_by_id',
                'approved_at',
            ]);
        });

        // Add back the status column that was removed
        Schema::table('holidays', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('notes');
        });
    }
};
