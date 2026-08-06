<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReconciliationStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\Reconciliation;
use App\Models\User;
use App\Services\ClubCashPosition;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function index(#[CurrentUser] User $user, ClubCashPosition $cashPosition): Response
    {
        $member = Member::query()->firstWhere('user_id', $user->id);

        $latestPeriod = ContributionPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        // The club position is only meaningful against a confirmed
        // reconciliation, so the frontend is told whether one exists.
        $latestConfirmed = Reconciliation::query()
            ->whereIn('status', [ReconciliationStatus::Confirmed->value, ReconciliationStatus::Locked->value])
            ->latest('confirmed_at')
            ->first();

        return Inertia::render('dashboard', [
            'personal' => $member === null ? null : [
                'outstanding' => (int) MemberObligation::query()
                    ->where('member_id', $member->id)
                    ->whereIn('status', [
                        ObligationStatus::Unpaid->value,
                        ObligationStatus::PartiallyPaid->value,
                    ])
                    ->sum('amount')
                    - (int) MemberObligation::query()
                        ->where('member_id', $member->id)
                        ->whereIn('status', [
                            ObligationStatus::Unpaid->value,
                            ObligationStatus::PartiallyPaid->value,
                        ])
                        ->sum('amount_paid'),
                'verified_total' => (int) Payment::query()
                    ->where('member_id', $member->id)
                    ->where('status', PaymentStatus::Verified->value)
                    ->sum('amount'),
                'advance' => (int) Payment::query()
                    ->where('member_id', $member->id)
                    ->where('status', PaymentStatus::Verified->value)
                    ->sum('unapplied_amount'),
            ],
            'club' => [
                'net_position' => $cashPosition->netPosition(),
                'verified_inflows' => $cashPosition->verifiedInflows(),
                'settled_outflows' => $cashPosition->settledOutflows(),
                'reconciled_to' => $latestConfirmed?->contributionPeriod->label(),
            ],
            'currentPeriod' => $latestPeriod === null ? null : [
                'label' => $latestPeriod->label(),
                'due_date' => $latestPeriod->due_date->toDateString(),
                'expected' => (int) MemberObligation::query()
                    ->where('contribution_period_id', $latestPeriod->id)
                    ->sum('amount'),
                'collected' => (int) MemberObligation::query()
                    ->where('contribution_period_id', $latestPeriod->id)
                    ->sum('amount_paid'),
            ],
            'officer' => [
                'payments_awaiting_verification' => Payment::query()
                    ->where('status', PaymentStatus::Submitted->value)
                    ->count(),
                'expenses_awaiting_approval' => Expense::query()
                    ->where('status', ExpenseStatus::Submitted->value)
                    ->count(),
                'expenses_awaiting_verification' => Expense::query()
                    ->where('status', ExpenseStatus::Paid->value)
                    ->count(),
                'members_in_arrears' => MemberObligation::query()
                    ->whereIn('status', [
                        ObligationStatus::Unpaid->value,
                        ObligationStatus::PartiallyPaid->value,
                    ])
                    ->distinct()
                    ->count('member_id'),
            ],
            'governance' => [
                'open_votes' => Proposal::query()
                    ->where('status', ProposalStatus::Open->value)
                    ->count(),
                'next_meeting' => Meeting::query()
                    ->where('scheduled_for', '>=', now())
                    ->orderBy('scheduled_for')
                    ->first(['reference', 'title', 'scheduled_for']),
            ],
        ]);
    }
}
