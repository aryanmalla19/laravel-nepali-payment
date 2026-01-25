<?php

namespace JaapTech\NepaliPayment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JaapTech\NepaliPayment\Enums\RefundReason;
use JaapTech\NepaliPayment\Enums\RefundStatus;

class PaymentRefund extends Model
{
    use HasUuids;

    protected $table = 'payment_refunds';

    protected $guarded = [];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_reason' => RefundReason::class,
        'refund_status' => RefundStatus::class,
        'gateway_response' => 'json',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the payment this refund belongs to.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope: Get completed refunds.
     */
    public function scopeCompleted($query)
    {
        return $query->where('refund_status', RefundStatus::COMPLETED);
    }

    /**
     * Scope: Get pending refunds.
     */
    public function scopePending($query)
    {
        return $query->where('refund_status', RefundStatus::PENDING);
    }

    /**
     * Scope: Get failed refunds.
     */
    public function scopeFailed($query)
    {
        return $query->where('refund_status', RefundStatus::FAILED);
    }

    /**
     * Scope: Get processing refunds.
     */
    public function scopeProcessing($query)
    {
        return $query->where('refund_status', RefundStatus::PROCESSING);
    }

    /**
     * Check if refund is completed.
     */
    public function isCompleted(): bool
    {
        return $this->refund_status === RefundStatus::COMPLETED;
    }

    /**
     * Check if refund is pending.
     */
    public function isPending(): bool
    {
        return $this->refund_status === RefundStatus::PENDING;
    }

    /**
     * Check if refund is failed.
     */
    public function isFailed(): bool
    {
        return $this->refund_status === RefundStatus::FAILED;
    }

    /**
     * Mark refund as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'refund_status' => RefundStatus::PROCESSING,
        ]);
    }

    /**
     * Mark refund as completed.
     */
    public function markAsCompleted(?string $gatewayRefundId = null): void
    {
        $data = [
            'refund_status' => RefundStatus::COMPLETED,
            'processed_at' => now(),
        ];

        if ($gatewayRefundId) {
            $data['gateway_refund_id'] = $gatewayRefundId;
        }

        $this->update($data);

        // Mark parent payment as refunded if no more pending refunds
        if (!$this->payment->refunds()->pending()->exists()) {
            $this->payment->markAsRefunded();
        }
    }

    /**
     * Mark refund as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $data = [
            'refund_status' => RefundStatus::FAILED,
            'processed_at' => now(),
        ];

        if ($reason) {
            $data['notes'] = $reason;
        }

        $this->update($data);
    }
}
