<?php

use App\Enums\LeaveRequestStatus;
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

            // Emergency contact details and abroad info
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 50)->nullable();
            $table->boolean('is_abroad')->default(false);
            $table->string('abroad_location', 200)->nullable();

            // Half-day support
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_type', ['first_half', 'second_half'])->nullable();
            $table->decimal('total_days', 4, 2)->default(1); // Support 0.5 for half days

            $table->bigInteger('cancelled_by_id')->nullable()->after('cancelled_at');

            // Change status from enum to string
            $table->string('status')->default(LeaveRequestStatus::PENDING->value)->change();

            // Add indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['from_date', 'to_date']);
            $table->index('leave_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Drop indexes if they exist
            if (Schema::hasIndex('leave_requests', 'leave_requests_user_id_status_index')) {
                $table->dropIndex('leave_requests_user_id_status_index');
            }
            if (Schema::hasIndex('leave_requests', 'leave_requests_from_date_to_date_index')) {
                $table->dropIndex('leave_requests_from_date_to_date_index');
            }
            if (Schema::hasIndex('leave_requests', 'leave_requests_leave_type_id_index')) {
                $table->dropIndex('leave_requests_leave_type_id_index');
            }

            // Drop column if it exists
            if (Schema::hasColumn('leave_requests', 'cancelled_by_id')) {
                $table->dropColumn('cancelled_by_id');
            }
        });
    }
};
