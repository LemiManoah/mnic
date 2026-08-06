<?php

declare(strict_types=1);

use App\Actions\ApproveExpense;
use App\Actions\RecordExpensePayment;
use App\Actions\RejectExpense;
use App\Actions\RequestExpense;
use App\Actions\VerifyExpense;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseEvidence;
use App\Models\ExternalAccount;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('walks an expense from request through to verification', function (): void {
    $requester = Member::factory()->create();
    $approver = Member::factory()->create();
    $verifier = Member::factory()->create();
    $account = ExternalAccount::factory()->create();

    $expense = resolve(RequestExpense::class)->handle([
        'reference' => 'EXP-0001',
        'purpose' => 'Venue hire',
        'category' => ExpenseCategory::Meeting->value,
        'payee' => 'Community Hall',
        'amount' => 50000,
        'incurred_on' => now()->toDateString(),
    ], $requester, null, '127.0.0.1');

    expect($expense->status)->toBe(ExpenseStatus::Submitted);

    resolve(ApproveExpense::class)->handle($expense, $approver, '127.0.0.1');
    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Approved);

    resolve(RecordExpensePayment::class)
        ->handle($expense, $account->id, 'PAY-9', now()->toDateString(), $approver, '127.0.0.1');
    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Paid);

    resolve(VerifyExpense::class)->handle($expense->fresh(), $verifier, '127.0.0.1');
    expect($expense->fresh()?->status)->toBe(ExpenseStatus::Verified);

    expect(AuditLog::query()->where('auditable_id', $expense->id)->count())->toBe(4);
});

it('stores expense evidence on the private disk', function (): void {
    Storage::fake('local');

    $expense = resolve(RequestExpense::class)->handle([
        'reference' => 'EXP-0002',
        'purpose' => 'Printing',
        'category' => ExpenseCategory::Other->value,
        'payee' => 'Print Shop',
        'amount' => 20000,
        'incurred_on' => now()->toDateString(),
    ], Member::factory()->create(), UploadedFile::fake()->create('invoice.pdf', 90, 'application/pdf'));

    $evidence = ExpenseEvidence::query()->where('expense_id', $expense->id)->first();

    expect($evidence?->original_name)->toBe('invoice.pdf');
    Storage::disk('local')->assertExists((string) $evidence?->path);
});

it('refuses approval by the member who requested the expense', function (): void {
    $requester = Member::factory()->create();
    $expense = Expense::factory()->create(['requested_by_member_id' => $requester->id]);

    resolve(ApproveExpense::class)->handle($expense, $requester);
})->throws(InvalidArgumentException::class);

it('refuses verification by the member who requested the expense', function (): void {
    $requester = Member::factory()->create();
    $expense = Expense::factory()->paid()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    resolve(VerifyExpense::class)->handle($expense, $requester);
})->throws(InvalidArgumentException::class);

it('refuses verification by the member who approved the expense', function (): void {
    $approver = Member::factory()->create();
    $expense = Expense::factory()->paid()->create([
        'approved_by_member_id' => $approver->id,
    ]);

    resolve(VerifyExpense::class)->handle($expense, $approver);
})->throws(InvalidArgumentException::class);

it('refuses to pay an expense that is not approved', function (): void {
    $expense = Expense::factory()->create();

    resolve(RecordExpensePayment::class)
        ->handle($expense, ExternalAccount::factory()->create()->id, 'PAY-1', now()->toDateString());
})->throws(InvalidArgumentException::class);

it('refuses to verify an expense that has not been paid', function (): void {
    $expense = Expense::factory()->approved()->create();

    resolve(VerifyExpense::class)->handle($expense, Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('rejects a submitted expense with a reason', function (): void {
    $expense = Expense::factory()->create();
    $approver = Member::factory()->create();

    $rejected = resolve(RejectExpense::class)->handle($expense, $approver, 'Not budgeted', '127.0.0.1');

    expect($rejected->status)->toBe(ExpenseStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('Not budgeted');
});

it('refuses rejection by the requester and of a non-submitted expense', function (): void {
    $requester = Member::factory()->create();
    $expense = Expense::factory()->create(['requested_by_member_id' => $requester->id]);

    resolve(RejectExpense::class)->handle($expense, $requester, 'No');
})->throws(InvalidArgumentException::class);

it('refuses to approve an expense that is not submitted', function (): void {
    $expense = Expense::factory()->approved()->create();

    resolve(ApproveExpense::class)->handle($expense, Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('refuses to reject an expense that is not submitted', function (): void {
    $expense = Expense::factory()->approved()->create();

    resolve(RejectExpense::class)->handle($expense, Member::factory()->create(), 'Too late');
})->throws(InvalidArgumentException::class);

it('labels every expense enum case', function (): void {
    foreach (ExpenseStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }

    foreach (ExpenseCategory::cases() as $category) {
        expect($category->label())->not->toBe('');
    }
});
