<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency' => 'NPR',
            'merchant_reference_id' => $this->faker->uuid(),
            'gateway_response' => [],
            'gateway_payload' => [],
            'payable_type' => null,
            'payable_id' => null,
            'initiated_at' => now(),
            'verified_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
        ];
    }

    public function khalti(): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => 'khalti',
        ]);
    }

    public function esewa(): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => 'esewa',
        ]);
    }

    public function connectips(): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => 'connectips',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::PENDING,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::PROCESSING,
            'verified_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'verified_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentStatus::REFUNDED,
            'completed_at' => now(),
            'refunded_at' => now(),
        ]);
    }

    public function withPayable(string $type, int|string $id): static
    {
        return $this->state(fn(array $attributes) => [
            'payable_type' => $type,
            'payable_id' => $id,
        ]);
    }
}
