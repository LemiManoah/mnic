<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RequestExpense;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Http\Requests\RequestExpenseRequest;
use App\Models\Expense;
use App\Models\ExternalAccount;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ExpenseController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Expense::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();
        $category = $request->string('category')->value();

        return Inertia::render('expense/index', [
            'expenses' => Expense::query()
                ->with(['requestedByMember', 'approvedByMember', 'verifiedByMember'])
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('reference', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('purpose', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('payee', 'like', sprintf('%%%s%%', $search))))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->when($category !== '', fn (Builder $query): Builder => $query
                    ->where('category', $category))
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Expense $expense): array => [
                    'id' => $expense->id,
                    'reference' => $expense->reference,
                    'purpose' => $expense->purpose,
                    'category' => $expense->category,
                    'payee' => $expense->payee,
                    'amount' => $expense->amount,
                    'incurred_on' => $expense->incurred_on->toDateString(),
                    'status' => $expense->status,
                    'requested_by' => $expense->requestedByMember?->full_name,
                    'approved_by' => $expense->approvedByMember?->full_name,
                    'verified_by' => $expense->verifiedByMember?->full_name,
                    'rejection_reason' => $expense->rejection_reason,
                    'can_approve' => $user->can('approve', $expense),
                    'can_pay' => $user->can('pay', $expense),
                    'can_verify' => $user->can('verify', $expense),
                ]),
            'canRequest' => $user->can('create', Expense::class),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
                'category' => $category === '' ? null : $category,
            ],
            'statusOptions' => array_map(
                static fn (ExpenseStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ExpenseStatus::cases(),
            ),
            'categoryOptions' => array_map(
                static fn (ExpenseCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ],
                ExpenseCategory::cases(),
            ),
            'externalAccounts' => ExternalAccount::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'masked_identifier']),
        ]);
    }

    public function store(
        RequestExpenseRequest $request,
        #[CurrentUser] User $user,
        RequestExpense $action,
    ): RedirectResponse {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->safe()->except('evidence');

        $action->handle(
            $attributes,
            Member::query()->firstWhere('user_id', $user->id),
            $request->file('evidence'),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Expense submitted for approval.'),
        ]);

        return to_route('expense.index');
    }
}
