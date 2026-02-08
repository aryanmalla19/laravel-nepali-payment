<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentRefundFactory extends Factory
{
    protected $model = PaymentRefund::class;

    public function definition(): array
    {
        return [
            'payment_id' => PaymentTransaction::factory(),
            'refund_amount' => $this->faker->randomFloat(2, 10, 1000),
            'refund_reason' => $this->faker->sentence(),
            'refund_status' => 'pending',
            'gateway_refund_id' => null,
            'gateway_response' => [],
            'notes' => null,
            'requested_by' => null,
            'processed_by' => null,
            'requested_at' => now(),
            'processed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_status' => 'pending',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_status' => 'processing',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_status' => 'completed',
            'processed_at' => now(),
            'gateway_refund_id' => $this->faker->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_status' => 'failed',
            'processed_at' => now(),
            'notes' => 'Refund processing failed',
        ]);
    }

    public function forPayment(PaymentTransaction $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => $payment->id,
        ]);
    }
}
