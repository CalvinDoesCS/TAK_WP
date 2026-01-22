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
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->bigInteger('attachment_size')->nullable()->after('attachment_original_name');
            $table->string('attachment_mime_type')->nullable()->after('attachment_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basic_transactions', function (Blueprint $table) {
            $table->dropColumn(['attachment_original_name', 'attachment_size', 'attachment_mime_type']);
        });
    }
};
