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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Gateway and Status
            $table->string('gateway')->index();
            $table->string('status')->index();

            // Polymorphic relation for payable (User, Order, Invoice, etc.)
            $table->nullableMorphs('payable', indexName: 'payments_payable_index');

            // Payment Details
            $table->decimal('amount', 10);
            $table->string('currency')->default('NPR');

            // Merchant Reference ID
            $table->string('merchant_reference_id')->unique();

            // Gateway Response Storage
            $table->json('gateway_payload')->nullable();
            $table->json('gateway_response')->nullable();

            // Lifecycle Timestamps
            $table->timestamp('initiated_at')->useCurrent();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Standard Timestamps
            $table->timestamps();

            // Indexes for common queries
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
