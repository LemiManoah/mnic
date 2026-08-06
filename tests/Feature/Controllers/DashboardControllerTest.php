<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Meeting;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\Reconciliation;

it('shows personal club officer and governance dashboard summaries', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->forMonth(2026, 8)->create();

    MemberObligation::factory()->create([
        'member_id' => $actor->id,
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'amount_paid' => 15000,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    Payment::factory()->create([
        'member_id' => $actor->id,
        'status' => PaymentStatus::Verified,
        'amount' => 80000,
        'unapplied_amount' => 20000,
    ]);

    Payment::factory()->create(['status' => PaymentStatus::Submitted]);
    Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    Expense::factory()->paid()->create();
    Proposal::factory()->create(['status' => ProposalStatus::Open]);
    Meeting::factory()->create(['scheduled_for' => now()->addDay()]);
    Reconciliation::factory()->confirmed()->create(['contribution_period_id' => $period->id]);

    $response = $this->actingAs($actor->user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard')
            ->where('personal.outstanding', 45000)
            ->where('personal.verified_total', 80000)
            ->where('personal.advance', 20000)
            ->where('club.reconciled_to', '2026-08')
            ->where('currentPeriod.label', '2026-08')
            ->where('officer.payments_awaiting_verification', 1)
            ->where('officer.expenses_awaiting_approval', 1)
            ->where('officer.expenses_awaiting_verification', 1)
            ->where('governance.open_votes', 1));
});
