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
        Schema::create('payment_refunds', function (Blueprint $table) {

            $table->uuid('id')->primary();
            // Foreign key to payments table
            $table->uuid('payment_id');
            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->cascadeOnDelete();

            // Refund Details
            $table->decimal('refund_amount', 10, 2);
            $table->enum('refund_reason', ['user_request', 'duplicate', 'error', 'other']);
            $table->enum('refund_status', ['pending', 'processing', 'completed', 'failed'])->index();

            // Gateway Response Storage
            $table->string('gateway_refund_id')->nullable();
            $table->json('gateway_response')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();

            // Lifecycle Timestamps
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

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
        Schema::dropIfExists('payment_refunds');
    }
};
