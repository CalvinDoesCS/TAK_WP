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
    Schema::create('subscriptions', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('tenant_id');
      $table->unsignedBigInteger('plan_id');
      $table->enum('status', ['trial', 'active', 'past_due', 'expired', 'cancelled'])->default('trial');
      $table->timestamp('starts_at');
      $table->timestamp('ends_at')->nullable();
      $table->timestamp('trial_ends_at')->nullable();
      $table->timestamp('cancelled_at')->nullable();
      $table->text('cancellation_reason')->nullable();
      $table->boolean('cancel_at_period_end')->default(false);
      $table->string('payment_method')->nullable();
      $table->decimal('amount', 10, 2)->default(0);
      $table->string('currency', 3)->default('USD');
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
      $table->foreign('plan_id')->references('id')->on('plans');

      $table->index('status');
      $table->index(['tenant_id', 'status']);
      $table->index('ends_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('subscriptions');
  }
};
