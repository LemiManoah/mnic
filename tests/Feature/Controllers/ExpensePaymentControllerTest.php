<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExternalAccount;

it('allows a treasurer to record payment for an approved expense', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $account = ExternalAccount::factory()->create();
    $expense = Expense::factory()->approved()->create();

    $response = $this->actingAs($actor->user)->put(route('expense-payment.update', $expense), [
        'external_account_id' => $account->id,
        'payment_reference' => 'PAY-9001',
        'paid_on' => now()->toDateString(),
    ]);

    $response->assertRedirectToRoute('expense.index');

    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Paid)
        ->and($expense->fresh()?->payment_reference)->toBe('PAY-9001');
});

it('denies a plain member from recording expense payment', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $account = ExternalAccount::factory()->create();
    $expense = Expense::factory()->approved()->create();

    $response = $this->actingAs($actor->user)->put(route('expense-payment.update', $expense), [
        'external_account_id' => $account->id,
        'payment_reference' => 'PAY-9002',
        'paid_on' => now()->toDateString(),
    ]);

    $response->assertForbidden();
});
