<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Payment;

/**
 * Derives cash figures from verified records only.
 *
 * Nothing here counts a payment that has not been verified or an expense that
 * has not been paid — the club's position must never be inflated by what
 * somebody merely typed in.
 */
final readonly class ClubCashPosition
{
    public function verifiedInflows(): int
    {
        return (int) Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->sum('amount');
    }

    public function settledOutflows(): int
    {
        return (int) Expense::query()
            ->whereIn('status', [ExpenseStatus::Paid->value, ExpenseStatus::Verified->value])
            ->sum('amount');
    }

    public function netPosition(): int
    {
        return $this->verifiedInflows() - $this->settledOutflows();
    }

    /**
     * Inflows verified with a payment date inside the given period's month.
     */
    public function inflowsForPeriod(ContributionPeriod $period): int
    {
        return (int) Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereYear('paid_on', $period->year)
            ->whereMonth('paid_on', $period->month)
            ->sum('amount');
    }

    public function withdrawalFeesForPeriod(ContributionPeriod $period): int
    {
        return (int) Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereYear('paid_on', $period->year)
            ->whereMonth('paid_on', $period->month)
            ->sum('withdrawal_fee_amount');
    }

    /**
     * Expenses actually paid out inside the given period's month.
     */
    public function outflowsForPeriod(ContributionPeriod $period): int
    {
        return (int) Expense::query()
            ->whereIn('status', [ExpenseStatus::Paid->value, ExpenseStatus::Verified->value])
            ->whereYear('paid_on', $period->year)
            ->whereMonth('paid_on', $period->month)
            ->sum('amount');
    }
}
