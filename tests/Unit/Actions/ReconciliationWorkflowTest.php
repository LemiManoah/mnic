<?php

declare(strict_types=1);

use App\Actions\CloseMonth;
use App\Actions\ConfirmReconciliation;
use App\Actions\SubmitReconciliation;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ExternalAccountType;
use App\Enums\ReconciliationStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;

it('derives the expected closing balance from verified records only', function (): void {
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    // Counted: a verified payment inside the month.
    Payment::factory()->verified()->create([
        'amount' => 120000,
        'paid_on' => '2026-09-04',
    ]);

    // Ignored: still awaiting verification.
    Payment::factory()->create([
        'amount' => 999000,
        'paid_on' => '2026-09-05',
    ]);

    // Counted: a paid expense inside the month.
    Expense::factory()->paid()->create([
        'amount' => 20000,
        'paid_on' => '2026-09-06',
    ]);

    $reconciliation = Reconciliation::factory()->create([
        'contribution_period_id' => $period->id,
        'opening_balance' => 50000,
        'statement_closing_balance' => 150000,
    ]);

    $submitted = resolve(SubmitReconciliation::class)
        ->handle($reconciliation, Member::factory()->create(), '127.0.0.1');

    // 50,000 opening + 120,000 in − 20,000 out = 150,000 expected.
    expect($submitted->expected_closing_balance)->toBe(150000)
        ->and($submitted->difference)->toBe(0)
        ->and($submitted->status)->toBe(ReconciliationStatus::Submitted);
});

it('records the difference when the statement disagrees', function (): void {
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $reconciliation = Reconciliation::factory()->create([
        'contribution_period_id' => $period->id,
        'opening_balance' => 10000,
        'statement_closing_balance' => 25000,
    ]);

    $submitted = resolve(SubmitReconciliation::class)->handle($reconciliation);

    expect($submitted->expected_closing_balance)->toBe(10000)
        ->and($submitted->difference)->toBe(15000);
});

it('refuses to submit a reconciliation that is already confirmed', function (): void {
    resolve(SubmitReconciliation::class)->handle(Reconciliation::factory()->confirmed()->create());
})->throws(InvalidArgumentException::class);

it('refuses confirmation by the member who prepared it', function (): void {
    $preparer = Member::factory()->create();
    $reconciliation = Reconciliation::factory()->submitted()->create([
        'prepared_by_member_id' => $preparer->id,
    ]);

    resolve(ConfirmReconciliation::class)->handle($reconciliation, $preparer);
})->throws(InvalidArgumentException::class);

it('refuses confirmation while an item is unresolved', function (): void {
    $reconciliation = Reconciliation::factory()->submitted()->create();

    ReconciliationItem::factory()->create(['reconciliation_id' => $reconciliation->id]);

    resolve(ConfirmReconciliation::class)->handle($reconciliation, Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('confirms once every item is resolved', function (): void {
    $reconciliation = Reconciliation::factory()->submitted()->create();
    $confirmer = Member::factory()->create();

    ReconciliationItem::factory()->resolved()->create(['reconciliation_id' => $reconciliation->id]);

    $confirmed = resolve(ConfirmReconciliation::class)->handle($reconciliation, $confirmer, '127.0.0.1');

    expect($confirmed->status)->toBe(ReconciliationStatus::Confirmed)
        ->and($confirmed->confirmed_by_member_id)->toBe($confirmer->id);

    expect(AuditLog::query()->where('auditable_id', $reconciliation->id)
        ->where('event', 'reconciliation.confirmed')->exists())->toBeTrue();
});

it('refuses to confirm a reconciliation that was never submitted', function (): void {
    resolve(ConfirmReconciliation::class)
        ->handle(Reconciliation::factory()->create(), Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('locks a confirmed reconciliation and closes its period', function (): void {
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();
    $reconciliation = Reconciliation::factory()->confirmed()->create([
        'contribution_period_id' => $period->id,
    ]);

    $locked = resolve(CloseMonth::class)->handle($reconciliation, Member::factory()->create(), '127.0.0.1');

    expect($locked->status)->toBe(ReconciliationStatus::Locked)
        ->and($locked->isLocked())->toBeTrue()
        ->and($locked->locked_at)->not->toBeNull()
        ->and($period->fresh()?->status)->toBe(ContributionPeriodStatus::Closed);
});

it('refuses to lock a reconciliation that is not confirmed', function (): void {
    resolve(CloseMonth::class)->handle(Reconciliation::factory()->submitted()->create());
})->throws(InvalidArgumentException::class);

it('reports which reconciliation statuses are editable', function (): void {
    expect(ReconciliationStatus::Draft->isEditable())->toBeTrue()
        ->and(ReconciliationStatus::Rejected->isEditable())->toBeTrue()
        ->and(ReconciliationStatus::Submitted->isEditable())->toBeFalse()
        ->and(ReconciliationStatus::Confirmed->isEditable())->toBeFalse()
        ->and(ReconciliationStatus::Locked->isEditable())->toBeFalse();

    foreach (ReconciliationStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }

    foreach (ExternalAccountType::cases() as $type) {
        expect($type->label())->not->toBe('');
    }
});
