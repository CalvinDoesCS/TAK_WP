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
        Schema::table('basic_transactions', function (Blueprint $table) {
            // Check if columns don't exist before adding (makes migration idempotent)
            if (! Schema::hasColumn('basic_transactions', 'sourceable_type')) {
                // Polymorphic relationship to source documents (sales orders, purchase orders, etc.)
                // nullableMorphs() automatically creates an index, so no need to create it manually
                $table->nullableMorphs('sourceable');
            }

            if (! Schema::hasColumn('basic_transactions', 'sync_status')) {
                $table->string('sync_status')->default('manual')->after('payment_method'); // manual, auto_synced, pending
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basic_transactions', function (Blueprint $table) {
            $table->dropMorphs('sourceable');
            $table->dropColumn('sync_status');
        });
    }
};
