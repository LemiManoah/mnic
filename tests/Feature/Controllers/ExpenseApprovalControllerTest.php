<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
use App\Models\Expense;

it('allows the chairperson to approve an expense they did not request', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $actor = memberWithRole(ClubRole::InterimChairperson);
    $expense = Expense::factory()->create(['requested_by_member_id' => $requester->id]);

    $response = $this->actingAs($actor->user)->post(route('expense-approval.store', $expense));

    $response->assertRedirectToRoute('expense.index');

    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Approved);
});

it('rejects an expense with a reason', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $actor = memberWithRole(ClubRole::InterimChairperson);
    $expense = Expense::factory()->create(['requested_by_member_id' => $requester->id]);

    $response = $this->actingAs($actor->user)->put(route('expense-approval.update', $expense), [
        'reason' => 'No resolution recorded',
    ]);

    $response->assertRedirectToRoute('expense.index');

    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Rejected)
        ->and($expense->fresh()?->rejection_reason)->toBe('No resolution recorded');
});

it('prevents the requester from approving their own expense', function (): void {
    $actor = memberWithRole(ClubRole::InterimChairperson);
    $expense = Expense::factory()->create(['requested_by_member_id' => $actor->id]);

    $response = $this->actingAs($actor->user)->post(route('expense-approval.store', $expense));

    $response->assertForbidden();
});
