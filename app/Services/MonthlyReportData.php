<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\ExpenseStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PeriodAdjustment;
use App\Models\Reconciliation;

/**
 * Assembles the monthly transparency report.
 *
 * Extracted so the screen and the PDF are built from one place. Two copies of
 * this arithmetic would eventually disagree, and a report that says one thing
 * on screen and another on paper is worse than having no PDF at all.
 */
final readonly class MonthlyReportData
{
    public function __construct(private ClubCashPosition $cashPosition)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function for(ContributionPeriod $period): array
    {
        $reconciliation = Reconciliation::query()
            ->where('contribution_period_id', $period->id)
            ->latest()
            ->first();

        $activeObligations = MemberObligation::query()
            ->where('contribution_period_id', $period->id)
            ->whereNotIn('status', [
                ObligationStatus::Waived->value,
                ObligationStatus::Cancelled->value,
            ]);

        return [
            'period' => [
                'id' => $period->id,
                'label' => $period->label(),
                'due_date' => $period->due_date->toDateString(),
                'status' => $period->status,
            ],
            'contributions' => [
                'expected' => (int) (clone $activeObligations)->sum('amount'),
                'collected' => (int) (clone $activeObligations)->sum('amount_paid'),
                'outstanding' => (int) (clone $activeObligations)
                    ->get()
                    ->sum(fn (MemberObligation $obligation): int => $obligation->outstanding()),
                'members_in_arrears' => MemberObligation::query()
                    ->where('contribution_period_id', $period->id)
                    ->whereIn('status', [
                        ObligationStatus::Unpaid->value,
                        ObligationStatus::PartiallyPaid->value,
                    ])
                    ->count(),
            ],
            'cash' => [
                'inflows' => $this->cashPosition->inflowsForPeriod($period),
                'outflows' => $this->cashPosition->outflowsForPeriod($period),
            ],
            // Approved corrections to this month, shown apart from the figures
            // they correct. The month's signed-off numbers stay as signed off.
            'adjustments' => PeriodAdjustment::query()
                ->with('requestedByMember')
                ->where('contribution_period_id', $period->id)
                ->where('status', AdjustmentStatus::Approved->value)
                ->latest('reviewed_at')
                ->get()
                ->map(fn (PeriodAdjustment $adjustment): array => [
                    'id' => $adjustment->id,
                    'amount' => $adjustment->amount,
                    'reason' => $adjustment->reason,
                    'requested_by' => $adjustment->requestedByMember?->full_name,
                ]),
            'reconciliation' => $reconciliation === null ? null : [
                'status' => $reconciliation->status,
                'is_confirmed' => in_array($reconciliation->status, [
                    ReconciliationStatus::Confirmed,
                    ReconciliationStatus::Locked,
                ], true),
                'opening_balance' => $reconciliation->opening_balance,
                'expected_closing_balance' => $reconciliation->expected_closing_balance,
                'statement_closing_balance' => $reconciliation->statement_closing_balance,
                'difference' => $reconciliation->difference,
            ],
            'payments' => Payment::query()
                ->with('member')
                ->where('status', PaymentStatus::Verified->value)
                ->whereYear('paid_on', $period->year)
                ->whereMonth('paid_on', $period->month)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'member_name' => $payment->member->full_name ?? __('Unknown member'),
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'paid_on' => $payment->paid_on->toDateString(),
                ]),
            'expenses' => Expense::query()
                ->whereIn('status', [ExpenseStatus::Paid->value, ExpenseStatus::Verified->value])
                ->whereYear('paid_on', $period->year)
                ->whereMonth('paid_on', $period->month)
                ->get()
                ->map(fn (Expense $expense): array => [
                    'id' => $expense->id,
                    'reference' => $expense->reference,
                    'purpose' => $expense->purpose,
                    'payee' => $expense->payee,
                    'amount' => $expense->amount,
                    'status' => $expense->status,
                ]),
        ];
    }
}
