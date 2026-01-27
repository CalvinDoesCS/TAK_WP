<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('tenants', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('email')->unique();
      $table->string('phone')->nullable();
      $table->string('subdomain')->unique();
      $table->string('custom_domain')->nullable()->unique();
      $table->enum('status', ['pending', 'approved', 'active', 'suspended', 'cancelled'])->default('pending');
      $table->timestamp('approved_at')->nullable();
      $table->unsignedBigInteger('approved_by_id')->nullable();
      $table->enum('database_provisioning_status', ['pending', 'provisioning', 'provisioned', 'failed', 'manual'])->default('pending');
      $table->timestamp('trial_ends_at')->nullable();
      $table->json('metadata')->nullable();
      $table->text('notes')->nullable();
      $table->boolean('has_used_trial')->default(false);
      $table->timestamps();
      $table->softDeletes();

      $table->index('subdomain');
      $table->index('custom_domain');
      $table->index('status');
      $table->index('trial_ends_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tenants');
  }
};
