<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
use App\Models\Expense;

it('allows a financial verifier to verify an expense they did not request or approve', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $approver = memberWithRole(ClubRole::InterimChairperson);
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $expense = Expense::factory()->paid()->create([
        'requested_by_member_id' => $requester->id,
        'approved_by_member_id' => $approver->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('expense-verification.update', $expense));

    $response->assertRedirectToRoute('expense.index');

    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Verified)
        ->and($expense->fresh()?->verified_by_member_id)->toBe($actor->id);
});

it('prevents the approver from verifying the same expense', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $expense = Expense::factory()->paid()->create([
        'requested_by_member_id' => $requester->id,
        'approved_by_member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('expense-verification.update', $expense));

    $response->assertForbidden();
});
