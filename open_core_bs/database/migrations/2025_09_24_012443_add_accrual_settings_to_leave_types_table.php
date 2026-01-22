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
        Schema::table('leave_types', function (Blueprint $table) {
            // Accrual settings
            $table->boolean('is_accrual_enabled')->default(false)->after('status');
            $table->enum('accrual_frequency', ['monthly', 'quarterly', 'yearly'])->default('yearly')->after('is_accrual_enabled');
            $table->decimal('accrual_rate', 5, 2)->default(0)->after('accrual_frequency'); // Days per frequency
            $table->decimal('max_accrual_limit', 5, 2)->nullable()->after('accrual_rate'); // Maximum days that can be accrued
            $table->boolean('allow_carry_forward')->default(false)->after('max_accrual_limit');
            $table->decimal('max_carry_forward', 5, 2)->nullable()->after('allow_carry_forward'); // Maximum days that can be carried forward
            $table->integer('carry_forward_expiry_months')->nullable()->after('max_carry_forward'); // Months after which carried forward leaves expire
            $table->boolean('allow_encashment')->default(false)->after('carry_forward_expiry_months');
            $table->decimal('max_encashment_days', 5, 2)->nullable()->after('allow_encashment');
            $table->boolean('is_comp_off_type')->default(false)->after('max_encashment_days'); // If this is used for compensatory offs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'is_accrual_enabled',
                'accrual_frequency',
                'accrual_rate',
                'max_accrual_limit',
                'allow_carry_forward',
                'max_carry_forward',
                'carry_forward_expiry_months',
                'allow_encashment',
                'max_encashment_days',
                'is_comp_off_type',
            ]);
        });
    }
};
