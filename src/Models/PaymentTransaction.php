<?php

namespace JaapTech\NepaliPayment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use JaapTech\NepaliPayment\Enums\PaymentStatus;

class PaymentTransaction extends Model
{
    use HasUuids;

    protected $table = 'payment_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'gateway_response' => 'json',
        'initiated_at' => 'datetime',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Get the parent payable model (polymorphic).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all refunds for this payment.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    /**
     * Scope: Filter payments by gateway.
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope: Filter payments by status.
     */
    public function scopeByStatus($query, PaymentStatus|string $status)
    {
        $statusValue = $status instanceof PaymentStatus ? $status->value : $status;

        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Get completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    /**
     * Scope: Get failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    /**
     * Scope: Get pending/processing payments.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING]);
    }

    /**
     * Scope: Filter by reference ID.
     */
    public function scopeByReference($query, string $referenceId)
    {
        return $query->where('reference_id', $referenceId);
    }

    /**
     * Scope: Filter by transaction ID.
     */
    public function scopeByTransactionId($query, string $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Scope: Filter payments by payable type and ID (polymorphic).
     */
    public function scopeForPayable($query, string $payableType, int|string $payableId)
    {
        return $query->where('payable_type', $payableType)
            ->where('payable_id', $payableId);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if payment is pending or processing.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Mark payment as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => PaymentStatus::PROCESSING,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark payment as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => PaymentStatus::CANCELLED,
        ]);
    }

    /**
     * Mark payment as refunded.
     */
    public function markAsRefunded(): void
    {
        $this->update([
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
        ]);
    }

    /**
     * Get the total refunded amount.
     */
    public function getTotalRefundedAmount(): float
    {
        return $this->refunds()
            ->where('refund_status', 'completed')
            ->sum('refund_amount');
    }

    /**
     * Get the remaining refundable amount.
     */
    public function getRemainingRefundableAmount(): float
    {
        return max(0, $this->amount - $this->getTotalRefundedAmount());
    }

    /**
     * Check if payment has any completed refunds.
     */
    public function hasRefunds(): bool
    {
        return $this->refunds()->where('refund_status', 'completed')->exists();
    }
}
