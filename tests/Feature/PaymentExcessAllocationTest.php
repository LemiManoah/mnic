<?php

declare(strict_types=1);

use App\Actions\ApplyMemberAdvances;
use App\Actions\ApprovePaymentReversal;
use App\Actions\OpenContributionPeriod;
use App\Actions\RecordPayment;
use App\Actions\RequestPaymentReversal;
use App\Actions\VerifyPayment;
use App\Enums\ClubRole;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentVerified;
use App\Services\ClubCashPosition;
use App\Services\MonthlyReportData;
use Illuminate\Validation\ValidationException;

/** @return array<string, mixed> */
function excessPaymentAttributes(Member $member, int $amount = 62000): array
{
    return [
        'member_id' => $member->id,
        'amount' => $amount,
        'paid_on' => '2026-08-31',
        'method' => PaymentMethod::MobileMoney->value,
        'external_reference' => fake()->unique()->uuid(),
    ];
}

beforeEach(function (): void {
    $this->member = Member::factory()->create();
    $this->period = ContributionPeriod::factory()->forMonth(2026, 8)->create();
    $this->obligation = MemberObligation::factory()->create([
        'member_id' => $this->member->id,
        'contribution_period_id' => $this->period->id,
    ]);
});

it('credits every shilling at or below the outstanding obligation', function (int $amount): void {
    $payment = resolve(RecordPayment::class)->handle(excessPaymentAttributes($this->member, $amount));
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($payment->withdrawal_fee_amount)->toBe(0)
        ->and($payment->contributionAmount())->toBe($amount)
        ->and($this->obligation->fresh()->amount_paid)->toBe($amount)
        ->and($payment->unapplied_amount)->toBe(0);
})->with([32000, 60000]);

it('requires an explicit excess allocation over HTTP', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $this->actingAs($actor->user)
        ->post(route('payment.store'), [...excessPaymentAttributes($this->member), 'contribution_period_id' => $this->period->id])
        ->assertSessionHasErrors('excess_allocation');

    expect(Payment::query()->count())->toBe(0);
});

it('keeps fees separate from contributions and retains gross cash', function (string $choice, int $fee): void {
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member),
        'excess_allocation' => $choice,
        'withdrawal_fee_amount' => $fee,
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($payment->withdrawal_fee_amount)->toBe($fee)
        ->and($payment->contribution_due_amount)->toBe(60000)
        ->and($payment->unapplied_amount)->toBe(2000 - $fee)
        ->and($this->obligation->fresh()->amount_paid)->toBe(60000)
        ->and((int) $payment->allocations()->sum('amount') + $payment->unapplied_amount + $fee)->toBe(62000)
        ->and(resolve(ClubCashPosition::class)->verifiedInflows())->toBe(62000);

    $report = resolve(MonthlyReportData::class)->for($this->period);
    expect($report['contributions']['collected'])->toBe(60000)
        ->and($report['cash']['inflows'])->toBe(62000)
        ->and($report['cash']['withdrawal_fees'])->toBe($fee)
        ->and($report['payments']->first()['contribution_amount'])->toBe(62000 - $fee);

    $html = view('pdf.payment-receipt', [
        'clubName' => 'Musuwa Nation',
        'payment' => $payment->load(['member', 'recordedByMember', 'reviewedByMember']),
        'allocations' => collect([['period' => 'August 2026', 'amount' => 60000]]),
    ])->render();
    expect($html)->toContain('Withdrawal fee', number_format($fee), '62,000')
        ->and(implode(' ', new PaymentVerified($payment)->bodyLines()))->toContain(number_format(62000 - $fee), number_format($fee));
})->with([['advance', 0], ['fees', 2000], ['split', 1000]]);

it('rejects fees on partial payments and inconsistent choices', function (int $amount, string $choice, int $fee): void {
    resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, $amount),
        'excess_allocation' => $choice,
        'withdrawal_fee_amount' => $fee,
    ]);
})->with([
    [32000, 'fees', 2000],
    [60000, 'fees', 1],
    [62000, 'fees', 3000],
    [62000, 'fees', 1000],
    [62000, 'advance', 1000],
    [62000, 'split', 0],
    [62000, 'split', 2000],
])->throws(ValidationException::class);

it('uses the remaining balance of the oldest month and advances to the next', function (): void {
    $this->obligation->update(['amount_paid' => 28000, 'status' => ObligationStatus::PartiallyPaid]);
    $next = MemberObligation::factory()->create([
        'member_id' => $this->member->id,
        'contribution_period_id' => ContributionPeriod::factory()->forMonth(2026, 9)->create()->id,
    ]);
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 34000),
        'excess_allocation' => 'split',
        'withdrawal_fee_amount' => 1000,
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($payment->contribution_due_amount)->toBe(32000)
        ->and($this->obligation->fresh()->amount_paid)->toBe(60000)
        ->and($next->fresh()->amount_paid)->toBe(1000)
        ->and($payment->unapplied_amount)->toBe(0);
});

it('rejects a stale balance instead of silently changing the split', function (): void {
    $this->obligation->update(['amount_paid' => 10000, 'status' => ObligationStatus::PartiallyPaid]);
    $actor = memberWithRole(ClubRole::Treasurer);
    $this->actingAs($actor->user)->post(route('payment.store'), [
        ...excessPaymentAttributes($this->member),
        'contribution_period_id' => $this->period->id,
        'contribution_due_amount' => 60000,
        'excess_allocation' => 'fees',
        'withdrawal_fee_amount' => 2000,
    ])->assertSessionHasErrors('contribution_due_amount');
});

it('preserves the agreed fees if another payment settles the month first', function (): void {
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member),
        'excess_allocation' => 'fees',
        'withdrawal_fee_amount' => 2000,
    ]);
    $other = resolve(RecordPayment::class)->handle(excessPaymentAttributes($this->member, 60000));
    $verifier = Member::factory()->create();
    resolve(VerifyPayment::class)->handle($other, $verifier);
    $payment = resolve(VerifyPayment::class)->handle($payment, $verifier);

    expect($payment->withdrawal_fee_amount)->toBe(2000)
        ->and($payment->unapplied_amount)->toBe(60000)
        ->and($this->obligation->fresh()->amount_paid)->toBe(60000);
});

it('prevents duplicate verification through a stale model', function (): void {
    $payment = resolve(RecordPayment::class)->handle(excessPaymentAttributes($this->member, 32000));
    $verifier = Member::factory()->create();
    resolve(VerifyPayment::class)->handle($payment, $verifier);
    resolve(VerifyPayment::class)->handle($payment, $verifier);
})->throws(InvalidArgumentException::class, 'Only a submitted payment can be verified.');

it('applies held credit when a month opens without adding cash or using fees', function (): void {
    seedClubSettings();
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 122000),
        'excess_allocation' => 'split',
        'withdrawal_fee_amount' => 2000,
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    $next = resolve(OpenContributionPeriod::class)->handle(2026, 9);
    resolve(ApplyMemberAdvances::class)->handle($this->member);

    $obligation = MemberObligation::query()->where('member_id', $this->member->id)->where('contribution_period_id', $next->id)->firstOrFail();
    expect($obligation->amount_paid)->toBe(60000)
        ->and($payment->fresh()->unapplied_amount)->toBe(0)
        ->and($payment->fresh()->withdrawal_fee_amount)->toBe(2000)
        ->and($payment->allocations()->count())->toBe(2)
        ->and(resolve(ClubCashPosition::class)->verifiedInflows())->toBe(122000);
});

it('holds the full payment as advance when no unpaid obligation exists', function (): void {
    $this->obligation->update(['amount_paid' => 60000, 'status' => ObligationStatus::Paid]);
    $payment = resolve(RecordPayment::class)->handle(excessPaymentAttributes($this->member, 32000));
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($payment->unapplied_amount)->toBe(32000)
        ->and($payment->withdrawal_fee_amount)->toBe(0);
});

it('reverses a split receipt without crediting fees as contributions', function (): void {
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member),
        'excess_allocation' => 'split',
        'withdrawal_fee_amount' => 1000,
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());
    $payment = resolve(RequestPaymentReversal::class)->handle($payment, Member::factory()->create(), 'Duplicate receipt');
    $payment = resolve(ApprovePaymentReversal::class)->handle($payment, Member::factory()->create());

    expect($payment->status)->toBe(PaymentStatus::Reversed)
        ->and($payment->withdrawal_fee_amount)->toBe(1000)
        ->and($payment->unapplied_amount)->toBe(0)
        ->and($payment->allocations()->count())->toBe(0)
        ->and($this->obligation->fresh()->amount_paid)->toBe(0)
        ->and(resolve(ClubCashPosition::class)->verifiedInflows())->toBe(0)
        ->and(resolve(ClubCashPosition::class)->withdrawalFeesForPeriod($this->period))->toBe(0);
});

it('exposes the allocation to reviewers and excludes fees from the member dashboard', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $memberUser = User::factory()->withoutTwoFactor()->create();
    $memberUser->assignRole(ClubRole::Member->value);

    $this->member->update(['user_id' => $memberUser->id]);
    $this->actingAs($actor->user)->post(route('payment.store'), [
        ...excessPaymentAttributes($this->member),
        'contribution_period_id' => $this->period->id,
        'contribution_due_amount' => 60000,
        'excess_allocation' => 'fees',
        'withdrawal_fee_amount' => 2000,
    ])->assertSessionHasNoErrors()->assertRedirectToRoute('payment.index');

    $payment = Payment::query()->sole();
    $this->actingAs($actor->user)->get(route('payment.index'))
        ->assertInertia(fn ($page) => $page
            ->where('payments.data.0.amount', 62000)
            ->where('payments.data.0.contribution_amount', 60000)
            ->where('payments.data.0.withdrawal_fee_amount', 2000));

    resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());
    $this->actingAs($memberUser)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('personal.verified_total', 60000)
            ->where('club.verified_inflows', 62000));

    $response = $this->actingAs($actor->user)->get(route('export.payments'));
    $response->assertOk();

    expect($response->streamedContent())->toContain('Withdrawal fees (UGX)', '62000', '60000', '2000');
});

it('renders advance only receipts even when there are no period allocations', function (): void {
    $this->obligation->update(['amount_paid' => 60000, 'status' => ObligationStatus::Paid]);
    $payment = resolve(RecordPayment::class)->handle(excessPaymentAttributes($this->member, 32000));
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    $html = view('pdf.payment-receipt', [
        'clubName' => 'Musuwa Nation',
        'payment' => $payment->load(['member', 'recordedByMember', 'reviewedByMember']),
        'allocations' => collect(),
    ])->render();
    expect($html)->toContain('Held as advance against future months', '32,000', 'Total received');
});

it('settles a selected open month before later months without touching earlier arrears', function (): void {
    $september = ContributionPeriod::factory()->forMonth(2026, 9)->create();
    $october = ContributionPeriod::factory()->forMonth(2026, 10)->create();
    $selected = MemberObligation::factory()->create(['member_id' => $this->member->id, 'contribution_period_id' => $september->id]);
    $later = MemberObligation::factory()->create(['member_id' => $this->member->id, 'contribution_period_id' => $october->id]);

    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 64000),
        'contribution_period_id' => $september->id,
        'excess_allocation' => 'split',
        'withdrawal_fee_amount' => 2000,
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($this->obligation->fresh()->amount_paid)->toBe(0)
        ->and($selected->fresh()->amount_paid)->toBe(60000)
        ->and($later->fresh()->amount_paid)->toBe(2000)
        ->and($payment->withdrawal_fee_amount)->toBe(2000)
        ->and($payment->unapplied_amount)->toBe(0);
});

it('rejects closed selected periods', function (): void {
    $this->period->update(['status' => ContributionPeriodStatus::Closed]);
    resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 32000),
        'contribution_period_id' => $this->period->id,
    ]);
})->throws(ValidationException::class);

it('rejects periods without an obligation for the member', function (): void {
    $otherPeriod = ContributionPeriod::factory()->forMonth(2026, 9)->create();
    resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 32000),
        'contribution_period_id' => $otherPeriod->id,
    ]);
})->throws(ValidationException::class);

it('requires rerecording if the selected period closes before verification', function (): void {
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 32000),
        'contribution_period_id' => $this->period->id,
    ]);
    $this->period->update(['status' => ContributionPeriodStatus::Closed]);
    resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());
})->throws(ValidationException::class);

it('does not send held selected-period advances into earlier arrears', function (): void {
    seedClubSettings();
    $september = ContributionPeriod::factory()->forMonth(2026, 9)->create();
    MemberObligation::factory()->create(['member_id' => $this->member->id, 'contribution_period_id' => $september->id]);
    $payment = resolve(RecordPayment::class)->handle([
        ...excessPaymentAttributes($this->member, 62000),
        'contribution_period_id' => $september->id,
        'excess_allocation' => 'advance',
    ]);
    $payment = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    $october = resolve(OpenContributionPeriod::class)->handle(2026, 10);
    $later = MemberObligation::query()->where('member_id', $this->member->id)->where('contribution_period_id', $october->id)->firstOrFail();

    expect($this->obligation->fresh()->amount_paid)->toBe(0)
        ->and($later->amount_paid)->toBe(2000)
        ->and($payment->fresh()->unapplied_amount)->toBe(0);
});

it('requires a selected period when submitting through the payment form', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $this->actingAs($actor->user)->post(route('payment.store'), excessPaymentAttributes($this->member, 32000))
        ->assertSessionHasErrors('contribution_period_id');
});
