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
        Schema::create('tenant_databases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('host');
            $table->string('port')->default('3306');
            $table->string('database_name');
            $table->string('username');
            $table->text('encrypted_password'); // Will be encrypted
            $table->enum('provisioning_status', ['pending', 'provisioned', 'failed', 'manual'])->default('pending');
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('provisioning_error')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            $table->index('provisioning_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_databases');
    }
};