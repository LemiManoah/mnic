<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\Proposal;
use Illuminate\Testing\TestResponse;

/**
 * Reads a streamed download into a string so its contents can be asserted.
 */
function csvBody(TestResponse $response): string
{
    return (string) $response->streamedContent();
}

it('lets a member download their own statement', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->create();

    MemberObligation::factory()->create([
        'member_id' => $actor->id,
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'amount_paid' => 20000,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    $response = $this->actingAs($actor->user)
        ->get(route('export.member-statement', $actor));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect(csvBody($response))
        ->toContain('Outstanding (UGX)')
        ->toContain('40000');
});

it('stops a member downloading somebody else statement', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $other = Member::factory()->create();

    $this->actingAs($actor->user)
        ->get(route('export.member-statement', $other))
        ->assertForbidden();
});

it('lets the secretary download any member statement', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $other = Member::factory()->create();

    $this->actingAs($actor->user)
        ->get(route('export.member-statement', $other))
        ->assertOk();
});

it('exports arrears with the days overdue', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);

    $period = ContributionPeriod::factory()->create([
        'grace_ends_on' => today()->subDays(10),
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'status' => ObligationStatus::Unpaid,
    ]);

    $response = $this->actingAs($actor->user)->get(route('export.arrears'));

    $response->assertOk();

    expect(csvBody($response))
        ->toContain('Days overdue')
        ->toContain('10');
});

it('exports one month of contributions', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $period = ContributionPeriod::factory()->create();

    MemberObligation::factory()->create(['contribution_period_id' => $period->id]);

    $response = $this->actingAs($actor->user)
        ->get(route('export.contributions', $period));

    $response->assertOk();

    expect(csvBody($response))->toContain('Member number');
});

it('exports only verified payments', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);

    Payment::factory()->create(['reference' => 'KEEP-ME', 'status' => PaymentStatus::Verified]);
    Payment::factory()->create(['reference' => 'DROP-ME', 'status' => PaymentStatus::Submitted]);

    $response = $this->actingAs($actor->user)->get(route('export.payments'));

    $response->assertOk();

    // An unverified payment is not evidence of anything, so it has no place in
    // an export people will treat as the record.
    expect(csvBody($response))
        ->toContain('KEEP-ME')
        ->not->toContain('DROP-ME');
});

it('exports expenses', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    Expense::factory()->create(['reference' => 'EXP-9001']);

    $response = $this->actingAs($actor->user)->get(route('export.expenses'));

    $response->assertOk();

    expect(csvBody($response))->toContain('EXP-9001');
});

it('exports the governance record', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    Proposal::factory()->create(['title' => 'Buy a plot in Wakiso']);

    $response = $this->actingAs($actor->user)->get(route('export.governance'));

    $response->assertOk();

    expect(csvBody($response))->toContain('Buy a plot in Wakiso');
});

it('exports the audit log for those allowed to read it', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    AuditLog::factory()->create(['event' => 'member.created']);

    $response = $this->actingAs($actor->user)->get(route('export.audit-log'));

    $response->assertOk();

    expect(csvBody($response))->toContain('member.created');
});

it('denies the audit export to a plain member', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $this->actingAs($actor->user)
        ->get(route('export.audit-log'))
        ->assertForbidden();
});
