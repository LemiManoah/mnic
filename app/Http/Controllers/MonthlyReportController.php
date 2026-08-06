<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Services\ClubCashPosition;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The monthly transparency report every active member can read.
 *
 * Each report states its period and whether the month's reconciliation has
 * been confirmed, so nobody mistakes unreconciled figures for settled ones.
 */
final readonly class MonthlyReportController
{
    public function index(): Response
    {
        return Inertia::render('monthly-report/index', [
            'periods' => ContributionPeriod::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(fn (ContributionPeriod $period): array => [
                    'id' => $period->id,
                    'label' => $period->label(),
                    'status' => $period->status,
                ]),
        ]);
    }

    public function show(ContributionPeriod $contributionPeriod, ClubCashPosition $cashPosition): Response
    {
        $reconciliation = Reconciliation::query()
            ->where('contribution_period_id', $contributionPeriod->id)
            ->latest()
            ->first();

        $activeObligations = MemberObligation::query()
            ->where('contribution_period_id', $contributionPeriod->id)
            ->whereNotIn('status', [
                ObligationStatus::Waived->value,
                ObligationStatus::Cancelled->value,
            ]);

        $expected = (int) (clone $activeObligations)->sum('amount');

        $collected = (int) (clone $activeObligations)->sum('amount_paid');

        $outstanding = (int) (clone $activeObligations)
            ->get()
            ->sum(fn (MemberObligation $obligation): int => $obligation->outstanding());

        return Inertia::render('monthly-report/show', [
            'period' => [
                'label' => $contributionPeriod->label(),
                'due_date' => $contributionPeriod->due_date->toDateString(),
                'status' => $contributionPeriod->status,
            ],
            'contributions' => [
                'expected' => $expected,
                'collected' => $collected,
                'outstanding' => $outstanding,
                'members_in_arrears' => MemberObligation::query()
                    ->where('contribution_period_id', $contributionPeriod->id)
                    ->whereIn('status', [
                        ObligationStatus::Unpaid->value,
                        ObligationStatus::PartiallyPaid->value,
                    ])
                    ->count(),
            ],
            'cash' => [
                'inflows' => $cashPosition->inflowsForPeriod($contributionPeriod),
                'outflows' => $cashPosition->outflowsForPeriod($contributionPeriod),
            ],
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
                ->whereYear('paid_on', $contributionPeriod->year)
                ->whereMonth('paid_on', $contributionPeriod->month)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'member_name' => $payment->member->full_name,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'paid_on' => $payment->paid_on->toDateString(),
                ]),
            'expenses' => Expense::query()
                ->whereIn('status', [ExpenseStatus::Paid->value, ExpenseStatus::Verified->value])
                ->whereYear('paid_on', $contributionPeriod->year)
                ->whereMonth('paid_on', $contributionPeriod->month)
                ->get()
                ->map(fn (Expense $expense): array => [
                    'id' => $expense->id,
                    'reference' => $expense->reference,
                    'purpose' => $expense->purpose,
                    'payee' => $expense->payee,
                    'amount' => $expense->amount,
                    'status' => $expense->status,
                ]),
        ]);
    }
}
