<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseEvidence>
 */
final class ExpenseEvidenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'path' => 'expense-evidence/'.fake()->uuid().'.pdf',
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 500000),
            'uploaded_by_member_id' => null,
        ];
    }
}
