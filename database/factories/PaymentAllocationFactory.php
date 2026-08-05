<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAllocation>
 */
final class PaymentAllocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'member_obligation_id' => MemberObligation::factory(),
            'amount' => 60000,
        ];
    }
}
