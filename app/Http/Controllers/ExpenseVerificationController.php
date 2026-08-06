<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\VerifyExpense;
use App\Http\Requests\VerifyExpenseRequest;
use App\Models\Expense;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ExpenseVerificationController
{
    public function update(
        VerifyExpenseRequest $request,
        Expense $expense,
        #[CurrentUser] User $user,
        VerifyExpense $action,
    ): RedirectResponse {
        // ExpensePolicy::verify already guarantees a member record.
        $verifier = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($expense, $verifier, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Expense verified.'),
        ]);

        return to_route('expense.index');
    }
}
