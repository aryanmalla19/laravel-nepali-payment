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

            // Polymorphic relation for payable (User, Order, Invoice, etc)
            $table->nullableMorphs('payable', indexName: 'payments_payable_index');

            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NPR');

            // Transaction IDs
            $table->string('reference_id')->unique();
            $table->string('gateway_transaction_id')->nullable()->unique();

            // Gateway Response Storage
            $table->json('gateway_response')->nullable();

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

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
        Schema::dropIfExists('payments');
    }
};
