<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add date field after user_id
            if (! Schema::hasColumn('attendances', 'date')) {
                $table->date('date')->after('user_id')->comment('Attendance date');
            }

            // Add break_hours field after working_hours
            if (! Schema::hasColumn('attendances', 'break_hours')) {
                $table->decimal('break_hours', 8, 2)->default(0)->after('working_hours')->comment('Total break hours');
            }

            // Add day type flags after notes
            if (! Schema::hasColumn('attendances', 'is_holiday')) {
                $table->boolean('is_holiday')->default(false)->after('notes');
            }

            if (! Schema::hasColumn('attendances', 'is_weekend')) {
                $table->boolean('is_weekend')->default(false)->after('is_holiday');
            }

            if (! Schema::hasColumn('attendances', 'is_half_day')) {
                $table->boolean('is_half_day')->default(false)->after('is_weekend');
            }
        });

        // Add indexes for performance
        Schema::table('attendances', function (Blueprint $table) {
            // Add unique constraint for user_id and date if it doesn't exist
            if (Schema::hasColumn('attendances', 'date')) {
                $indexExists = collect(DB::select("SHOW INDEXES FROM attendances WHERE Key_name = 'attendances_user_id_date_unique'"))->isNotEmpty();

                if (! $indexExists) {
                    $table->unique(['user_id', 'date'], 'attendances_user_id_date_unique');
                }
            }

            // Add date and status index
            $dateStatusIndex = collect(DB::select("SHOW INDEXES FROM attendances WHERE Key_name = 'attendances_date_status_index'"))->isNotEmpty();
            if (! $dateStatusIndex && Schema::hasColumn('attendances', 'date')) {
                $table->index(['date', 'status'], 'attendances_date_status_index');
            }

            // Add status index if not exists
            $statusIndex = collect(DB::select("SHOW INDEXES FROM attendances WHERE Key_name = 'attendances_status_index'"))->isNotEmpty();
            if (! $statusIndex) {
                $table->index('status', 'attendances_status_index');
            }

            // Add check_in_time index
            $checkInIndex = collect(DB::select("SHOW INDEXES FROM attendances WHERE Key_name = 'attendances_check_in_time_index'"))->isNotEmpty();
            if (! $checkInIndex) {
                $table->index('check_in_time', 'attendances_check_in_time_index');
            }

            // Add check_out_time index
            $checkOutIndex = collect(DB::select("SHOW INDEXES FROM attendances WHERE Key_name = 'attendances_check_out_time_index'"))->isNotEmpty();
            if (! $checkOutIndex) {
                $table->index('check_out_time', 'attendances_check_out_time_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('attendances_user_id_date_unique');
            $table->dropIndex('attendances_date_status_index');
            $table->dropIndex('attendances_status_index');
            $table->dropIndex('attendances_check_in_time_index');
            $table->dropIndex('attendances_check_out_time_index');

            // Drop columns
            if (Schema::hasColumn('attendances', 'date')) {
                $table->dropColumn('date');
            }

            if (Schema::hasColumn('attendances', 'break_hours')) {
                $table->dropColumn('break_hours');
            }

            if (Schema::hasColumn('attendances', 'is_holiday')) {
                $table->dropColumn('is_holiday');
            }

            if (Schema::hasColumn('attendances', 'is_weekend')) {
                $table->dropColumn('is_weekend');
            }

            if (Schema::hasColumn('attendances', 'is_half_day')) {
                $table->dropColumn('is_half_day');
            }
        });
    }
};
