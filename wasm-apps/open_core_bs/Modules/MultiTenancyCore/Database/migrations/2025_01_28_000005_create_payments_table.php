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
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('tenant_id');
      $table->unsignedBigInteger('subscription_id')->nullable();
      $table->unsignedBigInteger('new_plan_id')->nullable();
      $table->decimal('amount', 10, 2);
      $table->string('currency', 3)->default('USD');
      $table->string('payment_method', 50)->default('offline');
      $table->string('status', 50)->default('pending');
      $table->string('reference_number')->nullable();
      $table->string('invoice_number')->nullable();
      $table->text('description')->nullable();
      $table->string('proof_document_path')->nullable();
      $table->text('proof_of_payment')->nullable(); // For offline payment instructions
      $table->string('gateway_payment_id')->nullable();
      $table->string('gateway_transaction_id')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->unsignedBigInteger('approved_by_id')->nullable();
      $table->timestamp('approved_at')->nullable();
      $table->timestamp('rejected_at')->nullable();
      $table->text('rejection_reason')->nullable();
      $table->json('gateway_response')->nullable();
      $table->json('metadata')->nullable();

      $table->timestamps();

      $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
      $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();

      $table->index('status');
      $table->index(['tenant_id', 'status']);
      $table->index('reference_number');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
