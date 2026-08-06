<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveExpense;
use App\Actions\RejectExpense;
use App\Http\Requests\ApproveExpenseRequest;
use App\Http\Requests\RejectExpenseRequest;
use App\Models\Expense;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ExpenseApprovalController
{
    public function store(
        ApproveExpenseRequest $request,
        Expense $expense,
        #[CurrentUser] User $user,
        ApproveExpense $action,
    ): RedirectResponse {
        // ExpensePolicy::approve already guarantees a member record.
        $approver = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($expense, $approver, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Expense approved.'),
        ]);

        return to_route('expense.index');
    }

    public function update(
        RejectExpenseRequest $request,
        Expense $expense,
        #[CurrentUser] User $user,
        RejectExpense $action,
    ): RedirectResponse {
        $approver = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($expense, $approver, $request->string('reason')->value(), $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Expense rejected.'),
        ]);

        return to_route('expense.index');
    }
}
