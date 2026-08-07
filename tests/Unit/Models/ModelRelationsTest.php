<?php

declare(strict_types=1);

use App\Enums\ObligationStatus;
use App\Models\Expense;
use App\Models\ExpenseEvidence;
use App\Models\ExternalAccount;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Minute;
use App\Models\Payment;
use App\Models\PositionHolding;

/**
 * Relations that exist for the sake of the record — who reversed this, who
 * adjusted that, which version superseded which — and are read on detail
 * screens rather than in the workflow Actions.
 */
it('relates a member to every office they have held', function (): void {
    $member = Member::factory()->create();

    PositionHolding::factory()->count(2)->create(['member_id' => $member->id]);

    expect($member->positionHoldings)->toHaveCount(2);
});

it('relates a position holding to whoever appointed it', function (): void {
    $appointer = Member::factory()->create();
    $holding = PositionHolding::factory()->create(['appointed_by_member_id' => $appointer->id]);

    expect($holding->appointedByMember?->is($appointer))->toBeTrue();
});

it('relates an obligation to the member who adjusted it', function (): void {
    $officer = Member::factory()->create();

    $obligation = MemberObligation::factory()->create([
        'status' => ObligationStatus::Waived,
        'adjusted_by_member_id' => $officer->id,
    ]);

    expect($obligation->adjustedByMember?->is($officer))->toBeTrue();
});

it('relates a payment to the member who reversed it', function (): void {
    $officer = Member::factory()->create();
    $payment = Payment::factory()->create(['reversed_by_member_id' => $officer->id]);

    expect($payment->reversedByMember?->is($officer))->toBeTrue();
});

it('relates corrected minutes back to the version they supersede', function (): void {
    $meeting = Meeting::factory()->create();

    $original = Minute::factory()->create(['meeting_id' => $meeting->id, 'version' => 1]);
    $correction = Minute::factory()->create([
        'meeting_id' => $meeting->id,
        'version' => 2,
        'corrects_minute_id' => $original->id,
    ]);

    expect($correction->correctsMinute?->is($original))->toBeTrue()
        ->and($correction->isCorrection())->toBeTrue()
        ->and($original->isCorrection())->toBeFalse();
});

it('relates an expense to the account it was paid from', function (): void {
    $account = ExternalAccount::factory()->create();
    $expense = Expense::factory()->create(['external_account_id' => $account->id]);

    expect($expense->externalAccount?->is($account))->toBeTrue();
});

it('relates expense evidence back to its expense', function (): void {
    $expense = Expense::factory()->create();
    $evidence = ExpenseEvidence::factory()->create(['expense_id' => $expense->id]);

    expect($evidence->expense->is($expense))->toBeTrue();
});
