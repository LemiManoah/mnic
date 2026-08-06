<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExternalAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lists expenses for any authenticated member without workflow actions', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    Expense::factory()->create([
        'reference' => 'EXP-1001',
        'purpose' => 'Meeting venue',
    ]);

    $response = $this->actingAs($actor->user)->get(route('expense.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('expense/index')
            ->where('canRequest', false)
            ->has('expenses.data', 1)
            ->where('expenses.data.0.reference', 'EXP-1001')
            ->where('expenses.data.0.can_approve', false)
            ->where('expenses.data.0.can_pay', false)
            ->where('expenses.data.0.can_verify', false)
            ->has('statusOptions', count(ExpenseStatus::cases()))
            ->has('categoryOptions', count(ExpenseCategory::cases())));
});

it('allows an officer with expense permission to request an expense with evidence', function (): void {
    Storage::fake('local');

    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->post(route('expense.store'), [
        'reference' => 'EXP-2001',
        'purpose' => 'Venue booking',
        'category' => ExpenseCategory::Meeting->value,
        'payee' => 'Venue Ltd',
        'amount' => 75000,
        'incurred_on' => now()->toDateString(),
        'evidence' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirectToRoute('expense.index');

    expect(Expense::query()->where('reference', 'EXP-2001')->first()?->evidence)->toHaveCount(1);
});

it('denies a plain member from requesting an expense', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('expense.store'), [
        'reference' => 'EXP-2002',
        'purpose' => 'Venue booking',
        'category' => ExpenseCategory::Meeting->value,
        'payee' => 'Venue Ltd',
        'amount' => 75000,
        'incurred_on' => now()->toDateString(),
    ]);

    $response->assertForbidden();

    expect(Expense::query()->count())->toBe(0);
});

it('shows the correct expense workflow actions for officer roles', function (): void {
    $chair = memberWithRole(ClubRole::InterimChairperson);
    $treasurer = memberWithRole(ClubRole::Treasurer);
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $account = ExternalAccount::factory()->create();

    Expense::factory()->create([
        'reference' => 'EXP-SUBMITTED',
        'created_at' => now()->subMinutes(3),
        'requested_by_member_id' => $treasurer->id,
    ]);

    Expense::factory()->approved()->create([
        'reference' => 'EXP-APPROVED',
        'created_at' => now()->subMinutes(2),
    ]);

    Expense::factory()->paid()->create([
        'reference' => 'EXP-PAID',
        'created_at' => now()->subMinute(),
        'external_account_id' => $account->id,
        'requested_by_member_id' => $treasurer->id,
        'approved_by_member_id' => $chair->id,
    ]);

    $this->actingAs($chair->user)->get(route('expense.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expenses.data.2.can_approve', true));

    $this->actingAs($treasurer->user)->get(route('expense.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expenses.data.1.can_pay', true));

    $this->actingAs($verifier->user)->get(route('expense.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expenses.data.0.can_verify', true));
});
