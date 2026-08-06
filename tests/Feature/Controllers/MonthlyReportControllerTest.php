<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Reconciliation;

it('lists monthly report periods for authenticated members', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    ContributionPeriod::factory()->forMonth(2026, 8)->create();

    $response = $this->actingAs($actor->user)->get(route('monthly-report.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('monthly-report/index')
            ->has('periods', 1)
            ->where('periods.0.label', '2026-08'));
});

it('shows the monthly transparency report with contributions cash and reconciliation state', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $member = Member::factory()->create(['full_name' => 'Report Member']);
    $waivedMember = Member::factory()->create();
    $cancelledMember = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 8)->create();

    MemberObligation::factory()->create([
        'member_id' => $member->id,
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'amount_paid' => 45000,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    MemberObligation::factory()->create([
        'member_id' => $waivedMember->id,
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Waived,
    ]);

    MemberObligation::factory()->create([
        'member_id' => $cancelledMember->id,
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Cancelled,
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'status' => PaymentStatus::Verified,
        'amount' => 45000,
        'paid_on' => '2026-08-05',
        'reference' => 'MM-REPORT',
    ]);

    Expense::factory()->paid()->create([
        'status' => ExpenseStatus::Verified,
        'amount' => 10000,
        'paid_on' => '2026-08-06',
        'reference' => 'EXP-REPORT',
    ]);

    Reconciliation::factory()->create([
        'contribution_period_id' => $period->id,
        'status' => ReconciliationStatus::Confirmed,
        'opening_balance' => 100000,
        'expected_closing_balance' => 135000,
        'statement_closing_balance' => 135000,
        'difference' => 0,
    ]);

    $response = $this->actingAs($actor->user)->get(route('monthly-report.show', $period));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('monthly-report/show')
            ->where('period.label', '2026-08')
            ->where('contributions.expected', 60000)
            ->where('contributions.collected', 45000)
            ->where('contributions.outstanding', 15000)
            ->where('reconciliation.is_confirmed', true)
            ->where('payments.0.reference', 'MM-REPORT')
            ->where('expenses.0.reference', 'EXP-REPORT'));
});
